<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['planta', 'tecnico']);

        if ($request->has('planta_id')) {
            $query->where('planta_id', $request->planta_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'planta_id' => 'required|exists:plants,id',
            'technician_id' => 'nullable|exists:users,id',
            'tipo_servicio' => 'required|in:cambio_sedimentos,suministro_sal,cambio_carbon,mantenimiento_preventivo',
            'fecha_programada' => 'required|date',
            'estado' => 'in:pendiente,en_proceso,completado',
            'observaciones_cliente' => 'nullable|string',
            'observaciones_tecnico' => 'nullable|string',
        ]);

        // Validar que el usuario tenga rol técnico (si se envió technician_id)
        if ($data['technician_id'] ?? false) {
            $tecnico = User::find($data['technician_id']);
            if (!$tecnico || !$tecnico->hasRole('tecnico')) {
                throw ValidationException::withMessages([
                    'technician_id' => 'El usuario seleccionado no tiene el rol de técnico.',
                ]);
            }
        }

        $service = Service::create($data);
        return response()->json($service, 201);
    }

    public function show(Service $service)
    {
        return $service->load(['planta', 'tecnico']);
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
            'tipo_servicio' => 'sometimes|in:cambio_sedimentos,suministro_sal,cambio_carbon,mantenimiento_preventivo',
            'fecha_programada' => 'sometimes|date',
            'estado' => 'in:pendiente,en_proceso,completado',
            'observaciones_cliente' => 'nullable|string',
            'observaciones_tecnico' => 'nullable|string',
        ]);

        // Validar técnico en update también
        if ($data['technician_id'] ?? false) {
            $tecnico = User::find($data['technician_id']);
            if (!$tecnico || !$tecnico->hasRole('tecnico')) {
                throw ValidationException::withMessages([
                    'technician_id' => 'El usuario seleccionado no tiene el rol de técnico.',
                ]);
            }
        }

        $service->update($data);
        return response()->json($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(null, 204);
    }
}
