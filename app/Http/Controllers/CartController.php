<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Plant;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Buscar el ID del status 'activo' en la tabla cart_statuses
        $activeStatusId = \App\Models\CartStatus::where('name', 'activo')->first()->id;

        $cart = Cart::with(['items', 'status'])
            ->where('user_id', $user->id)
            ->where('status_id', $activeStatusId)
            ->first();

        return response()->json($cart);
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'item_type' => 'required|in:plant,supply',
        'item_id'   => 'required|integer',
        'quantity'  => 'required|integer|min:1'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = Auth::user();

    // Validar si la planta ya fue asignada a otro cliente
    if ($request->item_type === 'plant') {
        $plant = \App\Models\Plant::find($request->item_id);
        if (!$plant) {
            return response()->json(['message' => 'Planta no encontrada'], 404);
        }
        if ($plant->user_id !== null) {
            return response()->json(['message' => 'Esta planta ya fue asignada a otro cliente'], 409);
        }
    }

    // Buscar el ID del status 'activo' en la tabla cart_statuses
    $activeStatusId = \App\Models\CartStatus::where('name', 'activo')->first()->id;

    $cart = Cart::firstOrCreate([
        'user_id' => $user->id,
        'status_id' => $activeStatusId
    ]);

    $existingItem = CartItem::where('cart_id', $cart->id)
        ->where('item_type', $request->item_type)
        ->where('item_id', $request->item_id)
        ->first();

    if ($existingItem) {
        $existingItem->quantity += $request->quantity;
        $existingItem->save();

        return response()->json([
            'message' => 'Cantidad actualizada',
            'item'    => $existingItem
        ]);
    } else {
        $unitPrice = 0;

        if ($request->item_type === 'plant') {
            $plant = \App\Models\Plant::find($request->item_id);
            $unitPrice = $plant ? $plant->price : 0;
        } elseif ($request->item_type === 'supply') {
            $supply = \App\Models\Supply::find($request->item_id);
            $unitPrice = $supply ? $supply->price : 0;
        }

        $newItem = CartItem::create([
            'cart_id'    => $cart->id,
            'item_type'  => $request->item_type,
            'item_id'    => $request->item_id,
            'quantity'   => $request->quantity,
            'unit_price' => $unitPrice,
        ]);

        return response()->json([
            'message' => 'Ítem agregado al carrito',
            'item'    => $newItem
        ], 201);
    }
}


    public function update(Request $request, $item_id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $item = CartItem::findOrFail($item_id);
        $item->quantity = $request->quantity;
        $item->save();

        return response()->json([
            'message' => 'Ítem actualizado',
            'item'    => $item
        ]);
    }

    public function destroy($item_id)
    {
        $item = CartItem::findOrFail($item_id);
        $item->delete();

        return response()->json(['message' => 'Ítem eliminado del carrito']);
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cart = Cart::with('items')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'No tienes un carrito activo'], 404);
        }

        $total = 0;
        foreach ($cart->items as $item) {
            $total += $item->quantity * $item->unit_price;
        }

        // Buscar el ID del status 'pendiente' en la tabla purchase_statuses
        $pendingStatusId = \App\Models\PurchaseStatus::where('name', 'pendiente')->first()->id;

        $compra = SupplyPurchase::create([
            'cliente_id'   => $user->id,
            'status_id'    => $pendingStatusId,
            'precio_total' => $total,
            'metodo_pago'  => 'efectivo'
        ]);

        foreach ($cart->items as $item) {
            // Buscar el ID del status 'pendiente' en la tabla purchase_detail_statuses
            $pendingDetailStatusId = \App\Models\PurchaseDetailStatus::where('name', 'pendiente')->first()->id;

            SupplyPurchaseDetail::create([
                'compra_insumo_id' => $compra->id,
                'status_id'        => $pendingDetailStatusId,
                'tipo'             => $item->item_type,
                'item_id'          => $item->item_id,
                'cantidad'         => $item->quantity,
                'precio_unitario'  => $item->unit_price,
            ]);

            // Asignar planta al cliente si aplica
            if ($item->item_type === 'plant') {
                $plant = Plant::find($item->item_id);

                if ($plant && $plant->user_id === null) {
                    $plant->update([
                        'user_id' => $user->id,
                        'fecha_instalacion' => now(),
                    ]);
                }
            }
        }

        // Buscar el ID del status 'abandonado' en la tabla cart_statuses (equivalente a 'processing')
        $processingStatusId = \App\Models\CartStatus::where('name', 'abandonado')->first()->id;

        $cart->update(['status_id' => $processingStatusId]);

        return response()->json([
            'message' => 'Compra realizada con éxito',
            'compra'  => $compra->load('detalles')
        ]);
    }
}
