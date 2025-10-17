<?php

namespace App\Enums;

enum QueueState: string
{
    case WAITING = 'waiting';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
}
