<?php

namespace App\Helpers;

class ApiResponse
{

    // Success
    public const SUCCESS = [
        "code" => "200",
        "status" => "Success",
        "message" => "REQUEST SUCCESSFUL"
    ];

    // Not Found
    public const NOT_FOUND = [
        "code" => "404",
        "status" => "Error",
        "message" => "REQUEST NOT FOUND"
    ];

    // Data Exist
    public const ALREADY_EXIST = [
        "code" => "409",
        "status" => "Error",
        "message" => "REQUEST ALREADY EXIST"
    ];

    // Validator Store/Update. (required, string, number, max, min)
    public const INVALID_REQUEST = [
        "code" => "99",
        "status" => "Error",
        "message" => "INVALID REQUEST"
    ];

    // Processing Error
    public const PROCESSING_ERROR = [
        "code" => "500",
        "status" => "Error",
        "message" => "PROCESSING ERROR"
    ];
}
