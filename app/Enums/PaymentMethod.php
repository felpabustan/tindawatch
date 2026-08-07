<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Gcash = 'gcash';
    case Utang = 'utang';
}
