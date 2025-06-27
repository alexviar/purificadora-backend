<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatuses;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Plant;
use App\Models\Supply;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Buscar o crear un carrito activo para el usuario
        $cart = $this->getOrCreateCart($user->id);

        // Incluir los suministros relacionados
        $cartItems = CartItem::where('cart_id', $cart->id)->get();

        // Cargar información detallada de cada ítem
        $items = $cartItems->map(function ($item) {
            $itemDetails = null;
            if ($item->item_type == 'supply') {
                $itemDetails = Supply::find($item->item_id);
            } elseif ($item->item_type == 'plant') {
                $itemDetails = Plant::find($item->item_id);
            }

            return [
                'id' => $item->id,
                'cantidad' => $item->cantidad,
                'precio_unitario' => $item->precio_unitario,
                'supply' => $itemDetails
            ];
        });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_type' => 'required|string|in:supply,plant',
                'item_id' => 'required|integer|min:1',
                'cantidad' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = Auth::user();
            $cart = $this->getOrCreateCart($user->id);

            // Verificar que el item exista
            if ($request->item_type == 'supply') {
                $item = Supply::find($request->item_id);
                if (!$item) {
                    return response()->json(['message' => 'Suministro no encontrado'], 404);
                }
                $unitPrice = $item->precio;
            } elseif ($request->item_type == 'plant') {
                $item = Plant::find($request->item_id);
                if (!$item) {
                    return response()->json(['message' => 'Planta no encontrada'], 404);
                }
                $unitPrice = 0; // O el precio que corresponda para plantas
            }

            // Buscar si el item ya está en el carrito
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('item_type', $request->item_type)
                ->where('item_id', $request->item_id)
                ->first();

            if ($cartItem) {
                // Si ya existe, aumentar la cantidad
                $cartItem->cantidad += $request->cantidad;
                $cartItem->save();
            } else {
                // Si no existe, crear nuevo item en el carrito
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'item_type' => $request->item_type,
                    'item_id' => $request->item_id,
                    'cantidad' => $request->cantidad,
                    'precio_unitario' => $unitPrice
                ]);
            }

            return response()->json([
                'message' => 'Producto agregado al carrito',
                'cart_item' => $cartItem
            ]);
        } catch (\Exception $e) {
            Log::error('Error al agregar al carrito: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cartItem = CartItem::findOrFail($itemId);
        $cartItem->cantidad = $request->cantidad;
        $cartItem->save();

        return response()->json([
            'message' => 'Cantidad actualizada',
            'cart_item' => $cartItem
        ]);
    }

    public function destroy($itemId)
    {
        $cartItem = CartItem::findOrFail($itemId);
        $cartItem->delete();

        return response()->json(['message' => 'Producto eliminado del carrito']);
    }

    public function checkout(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $cart = $this->getOrCreateCart($user->id);
        $cartItems = CartItem::where('cart_id', $cart->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío'], 400);
        }

        // Verificar que el usuario tenga una dirección
        $addressId = $request->address_id;
        $address = null;

        if ($addressId) {
            $address = Address::where('id', $addressId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            // Intentar obtener la dirección predeterminada
            $address = $user->defaultAddress();
        }

        if (!$address) {
            return response()->json(['message' => 'No se encontró una dirección de envío válida'], 400);
        }

        // Calcular el precio total correctamente
        $precioTotal = $cartItems->sum(function ($item) {
            return $item->cantidad * $item->precio_unitario;
        });

        $payload = [
            'cliente_id' => $user->id,
            'status_id' => $request->metodo_pago === 'stripe' ? PurchaseStatuses::PENDING_PAYMENT->value : PurchaseStatuses::PENDING->value,
            'precio_total' => $precioTotal,
            'metodo_pago' => $request->metodo_pago ?? 'efectivo', // Por defecto efectivo
            'direccion_entrega' => json_encode([
                'street' => $address->street,
                'number' => $address->number,
                'colony' => $address->colony,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code
            ])
        ];

        return DB::transaction(function () use ($user, $cart, $cartItems, $precioTotal, $payload) {
            //Eliminar las compras pendientes de pago
            SupplyPurchase::where('cliente_id', $user->id)->where('status_id', PurchaseStatuses::PENDING_PAYMENT->value)->update([
                'status_id' => PurchaseStatuses::CANCELLED->value,
            ]);

            //Crear la compra y los detalles de la compra
            $purchase = SupplyPurchase::create($payload);
            $purchase->detalles()->createMany($cartItems->map(function ($item) use ($purchase) {
                return [
                    'compra_insumo_id' => $purchase->id,
                    'status_id' => 1, // Status pendiente
                    'tipo' => $item->item_type,
                    'item_id' => $item->item_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario
                ];
            }));

            if ($purchase->metodo_pago === 'stripe') {
                // Stripe requiere el monto en centavos
                $amountCents = intval($precioTotal * 100);
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                $paymentIntent = \Stripe\PaymentIntent::create([
                    'metadata' => [
                        'purchase_id' => $purchase->id,
                    ],
                    'amount' => $amountCents,
                    'currency' => 'mxn',
                    'automatic_payment_methods' => ['enabled' => true],
                ]);

                $purchase->payment_intent_id = $paymentIntent->id;
                $purchase->save();

                return response()->json([
                    'message' => 'Compra realizada con éxito',
                    'purchase' => $purchase,
                    'client_secret' => $paymentIntent->client_secret
                ]);
            } else if ($purchase->metodo_pago === 'efectivo') {
                // Limpiar el carrito
                CartItem::where('cart_id', $cart->id)->delete();

                return response()->json([
                    'message' => 'Compra realizada con éxito',
                    'purchase' => $purchase
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Busca o crea un carrito activo para el usuario.
     */
    protected function getOrCreateCart($userId)
    {
        $cart = Cart::where('user_id', $userId)
            ->where('status_id', 1) // Status activo
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'status_id' => 1 // Status activo
            ]);
        }

        return $cart;
    }
}
