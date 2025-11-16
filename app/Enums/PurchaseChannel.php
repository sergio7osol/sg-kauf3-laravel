<?php

namespace App\Enums;

enum PurchaseChannel: string
{
    case IN_STORE = 'in_store';
    case ONLINE = 'online';
    case HYBRID = 'hybrid';
}
