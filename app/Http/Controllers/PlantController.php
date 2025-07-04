<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(Request $request)
    {
        $query = Plant::query();
        $query->with('servicios', 'user')->get();

        $query->when($request->has('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'like', "%{$search}%")
                    ->orWhere('direccion_instalacion', 'like', "%{$search}%")
                    ->orWhere('paquete_instalado', 'like', "%{$search}%");
            });
        });

        $query->when($request->has('filter.client_id'), function ($q) use ($request) {
            $clientId = $request->input('filter.client_id');
            $q->where('user_id', $clientId);
        });

        $result = $query->paginate();
        return $result;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion_instalacion' => 'required|string|max:255',
            'paquete_instalado' => 'required|string|max:255',
            'fecha_instalacion' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $plant = Plant::create($data);
        return response()->json($plant, 201);
    }

    public function show(Plant $plant)
    {
        return $plant->load('servicios', 'user');
    }

    public function update(Request $request, Plant $plant)
    {
        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'direccion_instalacion' => 'sometimes|string|max:255',
            'paquete_instalado' => 'sometimes|string|max:255',
            'fecha_instalacion' => 'sometimes|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $plant->update($data);
        return response()->json($plant);
    }

    public function destroy(Plant $plant)
    {
        $plant->delete();
        return response()->json(null, 204);
    }

    // Cliente: ver solo sus propias plantas
    public function myPlants(Request $request)
    {
        return Plant::with('servicios')
            ->where('user_id', $request->user()->id)
            ->get();
    }

    // TODO: delete this endpoint
    public function UserPlants($user_id)
    {
        return Plant::with('servicios')
            ->where('user_id', $user_id)
            ->get();
    }

    // Asignar planta a cliente (solo admin/superadmin)
    public function assignToUser(Request $request, Plant $plant)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        if ($plant->user_id != null) {
            return response()->json(['message' => 'Esta planta ya está asignada a un cliente.'], 409);
        }

        $plant->user_id = $request->user_id;
        $plant->save();

        return response()->json([
            'message' => 'Planta asignada correctamente.',
            'plant' => $plant->load('user')
        ]);
    }

    // Desasignar planta
    public function unassignUser(Plant $plant)
    {
        $plant->user_id = null;
        $plant->save();

        return response()->json(['message' => 'Planta desasignada correctamente.']);
    }
}
