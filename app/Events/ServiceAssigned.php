<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Queue\SerializesModels;

class ServiceAssigned
{
    use SerializesModels;

    public $serviceRequest;

    public function __construct(ServiceRequest $serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }
}
