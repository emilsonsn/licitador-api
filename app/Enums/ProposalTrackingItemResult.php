<?php

namespace App\Enums;

enum ProposalTrackingItemResult: string
{
    case Pending = 'pending';
    case Won = 'won';
    case Lost = 'lost';
}
