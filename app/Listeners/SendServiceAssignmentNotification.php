<?php

namespace App\Listeners;

use App\Events\ServiceAssigned;
use App\Notifications\ServiceAssignmentNotification;

class SendServiceAssignmentNotification
{
    public function handle(ServiceAssigned $event)
    {
        $serviceRequest = $event->serviceRequest;
        // Se asume que el técnico asignado es quien debe recibir la notificación.
        // Verifica que el serviceRequest tenga un technician y que este tenga la capacidad de recibir notificaciones.
        if ($serviceRequest->technician) {
            $serviceRequest->technician->notify(new ServiceAssignmentNotification($serviceRequest));
        }
    }
}
