<?php

namespace App\Enums;

enum ReceiptStatus: string
{
    case QUEUED = 'queued';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            ReceiptStatus::QUEUED => __('Queued'),
            ReceiptStatus::IN_PROGRESS => __('In Progress'),
            ReceiptStatus::COMPLETED => __('Completed'),
            ReceiptStatus::CANCELLED => __('Cancelled'),
            ReceiptStatus::FAILED => __('Failed'),
        };
    }
}