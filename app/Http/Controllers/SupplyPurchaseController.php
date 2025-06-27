<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatuses;
use App\Models\SupplyPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplyPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplyPurchase::with('cliente');

        if ($request->has('estado')) {
            $query->whereHas('status', fn($query) => $query->where("name", $request->input('estado')));
        } else {
            $query->where('status_id', '<>', PurchaseStatuses::CANCELLED->value);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id'   => 'required|exists:users,id',
            'estado'       => 'required|in:pendiente,pagado,entregado',
            'precio_total' => 'required|numeric|min:0',
            'metodo_pago'  => 'required|string|in:stripe,efectivo'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $purchase = SupplyPurchase::create($request->all());

        return response()->json([
            'message'  => 'Compra de insumos creada correctamente',
            'purchase' => $purchase
        ], 201);
    }

    public function show(SupplyPurchase $supplyPurchase)
    {
        return response()->json($supplyPurchase->load('cliente', 'detalles'));
    }

    public function update(Request $request, SupplyPurchase $supplyPurchase)
    {
        $validator = Validator::make($request->all(), [
            'estado'       => 'sometimes|required|in:pendiente,pagado,entregado',
            'precio_total' => 'sometimes|required|numeric|min:0',
            'metodo_pago'  => 'sometimes|required|string|in:stripe,efectivo'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $supplyPurchase->update($request->all());

        return response()->json([
            'message'  => 'Compra de insumos actualizada correctamente',
            'purchase' => $supplyPurchase
        ]);
    }

    public function destroy(SupplyPurchase $supplyPurchase)
    {
        $supplyPurchase->delete();

        return response()->json([
            'message' => 'Compra de insumos eliminada correctamente'
        ]);
    }

    /**
     * Obtener los pedidos del usuario autenticado
     */
    public function userOrders(Request $request)
    {
        $user = Auth::user();

        // Obtener pedidos del usuario actual con sus detalles
        $orders = SupplyPurchase::where('cliente_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Procesar los pedidos para incluir los ítems
        $processedOrders = $orders->map(function ($order) {
            // Obtener los detalles (ítems) de cada pedido
            $detalles = DB::table('supply_purchase_details')
                ->where('compra_insumo_id', $order->id)
                ->get();

            // Procesar los detalles para incluir nombres de suministros
            $items = $detalles->map(function ($detalle) {
                $supplyName = '';
                if ($detalle->tipo == 'supply') {
                    $supply = DB::table('supplies')->where('id', $detalle->item_id)->first();
                    $supplyName = $supply ? $supply->nombre : 'Producto #' . $detalle->item_id;
                }

                return [
                    'cantidad' => $detalle->cantidad,
                    'supply_name' => $supplyName
                ];
            });

            // Devolver el pedido con sus ítems
            return [
                'id' => $order->id,
                'total' => $order->precio_total,
                'status' => $order->status_id, // Usar status_id numerico (1=pendiente, 2=pagado, 3=entregado)
                'created_at' => $order->created_at,
                'items' => $items
            ];
        });

        return response()->json($processedOrders);
    }

    /**
     * Obtener estadísticas de ventas mensuales para el último año
     */
    public function monthlySalesStats(Request $request)
    {
        $year = $request->input('year', date('Y')); // Por defecto el año actual

        $stats = [];
        // Generar array con valores iniciales para todos los meses (1-12)
        for ($i = 1; $i <= 12; $i++) {
            $stats[$i] = 0;
        }

        // Obtener datos de ventas mensuales para el año seleccionado
        $monthlySales = SupplyPurchase::whereYear('created_at', $year)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(precio_total) as total')
            )
            ->where('status_id', 2) // Solo pedidos pagados
            ->orWhere('status_id', 3) // O pedidos entregados
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();

        // Rellenar los datos reales
        foreach ($monthlySales as $sale) {
            $stats[$sale->month] = round((float)$sale->total, 2);
        }

        // Convertir a array indexado para chart.js
        $statsArray = array_values($stats);

        return response()->json([
            'year' => $year,
            'data' => $statsArray
        ]);
    }

    /**
     * Obtener los insumos más vendidos
     */
    public function topSellingSupplies(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $limit = $request->input('limit', 5);

        // Consulta para obtener los insumos más vendidos
        $topSupplies = \DB::table('supply_purchase_details')
            ->join('supply_purchases', 'supply_purchases.id', '=', 'supply_purchase_details.compra_insumo_id')
            ->join('supplies', 'supplies.id', '=', 'supply_purchase_details.item_id')
            ->whereYear('supply_purchases.created_at', $year)
            ->where('supply_purchase_details.tipo', 'supply')
            ->where(function ($query) {
                $query->where('supply_purchases.status_id', 2) // Pagado
                    ->orWhere('supply_purchases.status_id', 3); // Entregado
            })
            ->select(
                'supplies.id',
                'supplies.nombre',
                \DB::raw('SUM(supply_purchase_details.cantidad) as total_cantidad'),
                \DB::raw('SUM(supply_purchase_details.cantidad * supply_purchase_details.precio_unitario) as total_ventas')
            )
            ->groupBy('supplies.id', 'supplies.nombre')
            ->orderBy('total_cantidad', 'desc')
            ->limit($limit)
            ->get();

        // Calcular porcentajes de venta
        $totalVentas = $topSupplies->sum('total_ventas');
        $topSupplies = $topSupplies->map(function ($item) use ($totalVentas) {
            $item->porcentaje = $totalVentas > 0 ? round(($item->total_ventas / $totalVentas) * 100, 2) : 0;
            return $item;
        });

        return response()->json([
            'year' => $year,
            'top_supplies' => $topSupplies,
            'total_ventas' => $totalVentas
        ]);
    }

    /**
     * Obtener productos con mayor demanda por mes
     */
    public function productDemandTrends(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Consulta para obtener la demanda mensual de productos
        $monthlyDemand = \DB::table('supply_purchase_details')
            ->join('supply_purchases', 'supply_purchases.id', '=', 'supply_purchase_details.compra_insumo_id')
            ->join('supplies', 'supplies.id', '=', 'supply_purchase_details.item_id')
            ->whereYear('supply_purchases.created_at', $year)
            ->where('supply_purchase_details.tipo', 'supply')
            ->where(function ($query) {
                $query->where('supply_purchases.status_id', 2) // Pagado
                    ->orWhere('supply_purchases.status_id', 3); // Entregado
            })
            ->select(
                'supplies.id',
                'supplies.nombre',
                \DB::raw('MONTH(supply_purchases.created_at) as month'),
                \DB::raw('SUM(supply_purchase_details.cantidad) as cantidad')
            )
            ->groupBy('supplies.id', 'supplies.nombre', \DB::raw('MONTH(supply_purchases.created_at)'))
            ->orderBy('supplies.id')
            ->orderBy('month')
            ->get();

        // Agrupar por producto
        $productTrends = [];
        foreach ($monthlyDemand as $item) {
            if (!isset($productTrends[$item->id])) {
                $productTrends[$item->id] = [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'demanda_mensual' => array_fill(1, 12, 0)
                ];
            }
            $productTrends[$item->id]['demanda_mensual'][$item->month] = $item->cantidad;
        }

        // Convertir a array indexado y ordenar por demanda total
        $productTrends = array_values($productTrends);
        usort($productTrends, function ($a, $b) {
            return array_sum($b['demanda_mensual']) - array_sum($a['demanda_mensual']);
        });

        // Limitar a los 10 productos más demandados
        $productTrends = array_slice($productTrends, 0, 10);

        // Convertir arrays asociativos de meses a arrays indexados
        foreach ($productTrends as &$product) {
            $product['demanda_mensual'] = array_values($product['demanda_mensual']);
        }

        return response()->json([
            'year' => $year,
            'product_trends' => $productTrends
        ]);
    }
}
