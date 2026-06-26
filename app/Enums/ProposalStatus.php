<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Draft = 'draft';
    case Finished = 'finished';
    case Canceled = 'canceled';
}
