<?php

namespace App\Enums;

enum PurchaseStatuses: int
{
    case PENDING = 1;
    case PAID = 2;
    case DELIVERED = 3;
    case PENDING_PAYMENT = 4;
    case CANCELLED = 5;
    // case SHIPPED = 7;
    // case FAILED = 5;
    // case PROCESSING = 6;
}
