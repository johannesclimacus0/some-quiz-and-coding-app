<?php

namespace App\Enums;

enum AttemptGradingStatus: string
{
    case NotStarted = 'not_started';
    case Pending = 'pending';
    case Graded = 'graded';
    case Error = 'error';
}
