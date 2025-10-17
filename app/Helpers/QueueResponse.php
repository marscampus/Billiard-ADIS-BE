<?php

namespace App\Helpers;

class QueueResponse
{

    // Success
    public const WAITING = [
        "status" => "waiting"
    ];

    // Not Found
    public const IN_PROGRESS = [
        "status" => "in_progress"
    ];

    // Data Exist
    public const DONE = [
        "status" => "done"
    ];
}
