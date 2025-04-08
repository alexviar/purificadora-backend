<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplyController extends Controller
{
    public function index()
    {
        $supplies = Supply::all();

        // Convertir las imágenes binarias a base64 para enviarlas al frontend
        foreach ($supplies as $supply) {
            if ($supply->imagen) {
                // Convertir la imagen binaria a base64
                $supply->imagen = 'data:image/jpeg;base64,' . base64_encode($supply->imagen);
            }
        }

        return response()->json($supplies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'imagen'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'      => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Procesar la imagen como datos binarios si se sube
        if ($request->hasFile('imagen')) {
            $imagenBinaria = file_get_contents($request->file('imagen')->getRealPath());
            $request->merge(['imagen' => $imagenBinaria]);
        }

        $supply = Supply::create($request->all());

        return response()->json([
            'message' => 'Insumo creado exitosamente',
            'supply'  => $supply
        ], 201);
    }

    public function show(Supply $supply)
    {
        // Convertir la imagen binaria a base64 si existe
        if ($supply->imagen) {
            $supply->imagen = 'data:image/jpeg;base64,' . base64_encode($supply->imagen);
        }

        return response()->json($supply);
    }

    public function update(Request $request, Supply $supply)
    {
        $validator = Validator::make($request->all(), [
            'nombre'      => 'sometimes|required|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'precio'      => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'imagen'      => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'      => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Procesar la imagen como datos binarios si se sube en la actualización
        if ($request->hasFile('imagen')) {
            $imagenBinaria = file_get_contents($request->file('imagen')->getRealPath());
            $request->merge(['imagen' => $imagenBinaria]);
        }

        $supply->update($request->all());

        return response()->json([
            'message' => 'Insumo actualizado exitosamente',
            'supply'  => $supply
        ]);
    }

    public function destroy(Supply $supply)
    {
        $supply->delete();
        return response()->json(['message' => 'Insumo eliminado exitosamente']);
    }
}
