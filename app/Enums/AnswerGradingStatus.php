<?php

namespace App\Enums;

enum AnswerGradingStatus: string
{
    case NotStarted = 'not_started';
    case PendingManual = 'pending_manual';
    case Graded = 'graded';
    case Error = 'error';
}
