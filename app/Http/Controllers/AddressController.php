<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Obtener todas las direcciones del usuario autenticado
     */
    public function index()
    {
        $user = Auth::user();
        $addresses = $user->addresses;
        return response()->json($addresses);
    }

    /**
     * Obtener la dirección predeterminada del usuario
     */
    public function getDefault()
    {
        $user = Auth::user();
        $address = $user->defaultAddress();
        
        if (!$address) {
            // Si no hay dirección predeterminada, devolver la primera dirección
            $address = $user->addresses()->first();
        }
        
        return response()->json($address);
    }

    /**
     * Almacenar una nueva dirección
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'street' => 'required|string|max:100',
            'number' => 'required|string|max:10',
            'colony' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        
        // Si es la dirección predeterminada, quitar ese estado de las demás direcciones
        if ($request->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }
        
        // Si es la primera dirección del usuario, hacerla predeterminada
        if ($user->addresses()->count() === 0) {
            $request->merge(['is_default' => true]);
        }

        $address = $user->addresses()->create($request->all());
        return response()->json($address, 201);
    }

    /**
     * Mostrar una dirección específica
     */
    public function show($id)
    {
        $user = Auth::user();
        $address = $user->addresses()->findOrFail($id);
        return response()->json($address);
    }

    /**
     * Actualizar una dirección existente
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'street' => 'sometimes|string|max:100',
            'number' => 'sometimes|string|max:10',
            'colony' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:10',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $address = $user->addresses()->findOrFail($id);
        
        // Si se establece como predeterminada, quitar ese estado de las demás direcciones
        if ($request->has('is_default') && $request->is_default) {
            $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }
        
        $address->update($request->all());
        return response()->json($address);
    }

    /**
     * Eliminar una dirección
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $address = $user->addresses()->findOrFail($id);
        
        // Si es la dirección predeterminada y existen otras direcciones, establecer otra como predeterminada
        if ($address->is_default) {
            $otherAddress = $user->addresses()->where('id', '!=', $id)->first();
            if ($otherAddress) {
                $otherAddress->update(['is_default' => true]);
            }
        }
        
        $address->delete();
        return response()->json(null, 204);
    }
}