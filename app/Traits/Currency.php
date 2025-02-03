<?php

namespace App\Traits;

trait Currency
{
    public function formatAmount($amount)
    {
        return '₦' . number_format((float)$amount, 2);

    }
}
