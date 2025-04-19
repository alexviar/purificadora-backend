<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use Illuminate\Http\Request;
use finfo;
use Illuminate\Http\UploadedFile;

class SupplyController extends Controller
{
    /**
     * Listado de insumos, con imagenes en Base64
     */
    public function index()
    {
        $supplies = Supply::all();

        return response()->json($supplies);
    }

    /**
     * Crear nuevo insumo
     */
    public function store(Request $request)
    {
        $payload = $request->validate([
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'imagen'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'      => 'boolean',
        ]);

        // Procesa la imagen si viene
        if ($request->hasFile('imagen')) {
            /** @var UploadedFile $imagen */
            $imagen = $payload['imagen'];
            $payload['imagen'] = $imagen->store('supplies', 'public');
        }

        $supply = Supply::create($payload);

        return response()->json([
            'message' => 'Insumo creado exitosamente',
            'supply'  => $supply
        ], 201);
    }

    /**
     * Mostrar detalle de un insumo
     */
    public function show(Supply $supply)
    {
        return response()->json($supply);
    }

    /**
     * Actualizar insumo existente
     */
    public function update(Request $request, Supply $supply)
    {
        $payload = $request->validate([
            'nombre'      => 'sometimes|required|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'precio'      => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'imagen'      => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'      => 'sometimes|boolean',
        ]);

        // Si hay un nuevo archivo, conviértelo a blob
        if ($request->hasFile('imagen')) {
            /** @var UploadedFile $imagen */
            $imagen = $payload['imagen'];
            $payload['imagen'] = $imagen->store('supplies', 'public');
        }

        $supply->update($payload);

        return response()->json([
            'message' => 'Insumo actualizado exitosamente',
            'supply'  => $supply
        ], 200);
    }

    /**
     * Eliminar un insumo
     */
    public function destroy(Supply $supply)
    {
        $supply->delete();
        return response()->json(['message' => 'Insumo eliminado exitosamente']);
    }
}
