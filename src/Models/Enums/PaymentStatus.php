<?php

namespace Unusualify\Payable\Models\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';
}
