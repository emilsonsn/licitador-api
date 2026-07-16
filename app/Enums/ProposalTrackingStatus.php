<?php

namespace App\Enums;

enum ProposalTrackingStatus: string
{
    case Open = 'open';
    case Finished = 'finished';
}
