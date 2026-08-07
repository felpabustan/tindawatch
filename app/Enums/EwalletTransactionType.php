<?php

namespace App\Enums;

enum EwalletTransactionType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
}
