<?php

namespace App\Http\Controllers;

use App\Models\SupplyPurchase;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Obtiene las estadísticas del dashboard para el superusuario.
     */
    public function index(Request $request)
    {
        // 1. Ingresos mensuales: Suma de precio_total en el mes actual para compras con estado "pagado" o "entregado"
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        $monthlyRevenue = SupplyPurchase::whereIn('estado', ['pagado', 'entregado'])
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('precio_total');

        // 2. Cantidad de mantenimientos realizados: Total de solicitudes con estado "completado"
        $completedMaintenanceCount = ServiceRequest::where('estado', 'completado')->count();

        // 3. Registro de mantenimientos efectuados en un período (agrupados por mes)
        $maintenanceOverTime = ServiceRequest::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("COUNT(*) as total")
            )
            ->where('estado', 'completado')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // 4. Insumos más vendidos:
        // Se asume la existencia de una tabla 'supply_purchase_details' que almacena el detalle de cada compra:
        // - supply_id: identificador del insumo
        // - quantity: cantidad vendida en esa transacción
        // Si no cuentas con esta tabla, puedes omitir este bloque o adaptarlo.
        $topSupplies = DB::table('supply_purchase_details')
            ->select('supply_id', DB::raw("SUM(quantity) as total_sold"))
            ->groupBy('supply_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'monthly_revenue'         => $monthlyRevenue,
            'completed_maintenances'  => $completedMaintenanceCount,
            'maintenance_over_time'   => $maintenanceOverTime,
            'top_supplies'            => $topSupplies,
        ]);
    }
}
