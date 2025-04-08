<?php

namespace App\Http\Controllers;

use App\Models\SupplyPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplyPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplyPurchase::with('cliente');

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
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
}
