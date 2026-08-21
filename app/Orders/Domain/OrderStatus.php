<?php

namespace App\Orders\Domain;

enum OrderStatus: string
{
    case Open = 'open';
    case Sent = 'sent';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
