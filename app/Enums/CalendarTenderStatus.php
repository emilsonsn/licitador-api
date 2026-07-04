<?php

namespace App\Enums;

enum CalendarTenderStatus: string
{
    case Participating = 'participating';
    case Qualification = 'qualification';
    case Won = 'won';
    case ToReceive = 'to_receive';
    case Finished = 'finished';
    case Disqualified = 'disqualified';
    case NotDone = 'not_done';
    case NoAward = 'no_award';
    case SuspendedNewDate = 'suspended_new_date';
}
