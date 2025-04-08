<?php

namespace App\Http\Controllers;

use App\Models\PaymentConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Obtener la configuración actual de pasarelas de pago.
     */
    public function index()
    {
        $config = PaymentConfiguration::first();

        if (!$config) {
            // Si no existe configuración, se crea una por defecto.
            $config = PaymentConfiguration::create([
                'stripe_enabled'   => false,
                'efectivo_enabled' => false,
            ]);
        }

        return response()->json($config);
    }

    /**
     * Actualizar la configuración de pasarelas de pago.
     */
    public function updateConfiguration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stripe_enabled'   => 'required|boolean',
            'efectivo_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $config = PaymentConfiguration::first();

        if (!$config) {
            // Crea la configuración si no existe
            $config = PaymentConfiguration::create($request->all());
        } else {
            $config->update($request->all());
        }

        return response()->json([
            'message' => 'Payment configuration updated successfully',
            'config'  => $config
        ]);
    }
}
