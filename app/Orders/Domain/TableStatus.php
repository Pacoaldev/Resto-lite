<?php

namespace App\Orders\Domain;

enum TableStatus: string
{
    case Free = 'free';
    case Occupied = 'occupied';
    case BillRequested = 'billRequested';
}
