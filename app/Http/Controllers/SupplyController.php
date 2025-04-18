<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use finfo;

class SupplyController extends Controller
{
    /**
     * Helper: convierte datos binarios en data-URI con MIME detectado
     */
    protected function encodeImage($binary)
    {
        try {
            // Intenta detectar el tipo MIME con finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($binary);
            
            // Si no se pudo detectar o devolvió application/octet-stream, usar imagen genérica
            if (!$mime || $mime === 'application/octet-stream') {
                $mime = 'image/jpeg'; // Definir un tipo por defecto
            }
            
            // Verifica que los datos binarios sean válidos
            if (empty($binary)) {
                return null;
            }
            
            // Codifica a base64 y crea el data-URI
            return "data:{$mime};base64," . base64_encode($binary);
        } catch (\Exception $e) {
            \Log::error('Error al codificar imagen: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Listado de insumos, con imagenes en Base64
     */
    public function index()
    {
        $supplies = Supply::all()->map(function($s) {
            if ($s->imagen) {
                $s->imagen = $this->encodeImage($s->imagen);
            }
            return $s;
        });

        return response()->json($supplies);
    }

    /**
     * Crear nuevo insumo
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'imagen'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'      => 'boolean',
        ]);

        if ($v->fails()) {
            return response()->json($v->errors(), 422);
        }

        // Procesa la imagen si viene
        if ($request->hasFile('imagen')) {
            $binary = file_get_contents($request->file('imagen')->getRealPath());
            $request->merge(['imagen' => $binary]);
        }

        $supply = Supply::create($request->all());

        // Convierte blob a data-URI antes de responder
        if ($supply->imagen) {
            $supply->imagen = $this->encodeImage($supply->imagen);
        }

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
        if ($supply->imagen) {
            $supply->imagen = $this->encodeImage($supply->imagen);
        }
        return response()->json($supply);
    }

    /**
     * Actualizar insumo existente
     */
    public function update(Request $request, Supply $supply)
    {
        $v = Validator::make($request->all(), [
            'nombre'      => 'sometimes|required|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
            'precio'      => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'imagen'      => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'      => 'sometimes|boolean',
        ]);

        if ($v->fails()) {
            return response()->json($v->errors(), 422);
        }

        // Si hay un nuevo archivo, conviértelo a blob
        if ($request->hasFile('imagen')) {
            $binary = file_get_contents($request->file('imagen')->getRealPath());
            $request->merge(['imagen' => $binary]);
        }

        $supply->update($request->all());

        // Convierte blob a data-URI antes de responder
        if ($supply->imagen) {
            $supply->imagen = $this->encodeImage($supply->imagen);
        }

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
