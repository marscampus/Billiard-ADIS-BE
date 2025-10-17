<?php

namespace App\Http\Controllers\Concerns;

use Auth;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

use function response;

trait ApiResponse
{
    public function respondNotFound(
        string|Exception $message,
        ?string $key = 'message'
    ): JsonResponse {
        return $this->apiResponse(
            data: [$key => $this->morphMessage($message)],
            code: Response::HTTP_NOT_FOUND
        );
    }

    public function respondWithSuccess(
        string|Exception $message,
        array|Arrayable|JsonSerializable|null $contents = null
    ): JsonResponse {
        return $this->apiResponse(
            data: [
                'message' => $this->morphMessage($message),
                ...$this->morphToArray(data: $contents) ?? []
            ]
        );
    }

    public function respondOk(string $message): JsonResponse
    {
        return $this->respondWithSuccess($message);
    }

    public function respondUnAuthenticated(?string $message = null): JsonResponse
    {
        return $this->apiResponse(
            data: ['message' => $message ?? 'Unauthenticated'],
            code: Response::HTTP_UNAUTHORIZED
        );
    }

    public function respondAuthenticated(?string $message = null, int $response = Response::HTTP_OK): JsonResponse
    {
        return $this->apiResponse(
            data: [
                'message' => $message ?? 'Authenticated',
                'data' => Auth::user()
            ],
            code: $response
        );
    }

    public function respondForbidden(?string $message = null): JsonResponse
    {
        return $this->apiResponse(
            data: ['message' => $message ?? 'Forbidden'],
            code: Response::HTTP_FORBIDDEN
        );
    }

    public function respondError(?string $message = null): JsonResponse
    {
        return $this->apiResponse(
            data: ['message' => $message ?? 'message'],
            code: Response::HTTP_BAD_REQUEST
        );
    }

    public function respondCreated(
        string|Exception $message,
        array|Arrayable|JsonSerializable|null $data = null
    ): JsonResponse {
        $data ??= [];

        return $this->apiResponse(
            data: [
                'message' => $this->morphMessage($message),
                ...$this->morphToArray(data: $data) ?? []
            ],
            code: Response::HTTP_CREATED
        );
    }

    public function respondFailedValidation(
        string|Exception $message,
        ?string $key = 'message'
    ): JsonResponse {
        return $this->apiResponse(
            data: [$key => $this->morphMessage($message)],
            code: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    public function respondTeapot(): JsonResponse
    {
        return $this->apiResponse(
            data: ['message' => 'I\'m a teapot'],
            code: Response::HTTP_I_AM_A_TEAPOT
        );
    }

    public function respondNoContent(
        array|Arrayable|JsonSerializable|null $data = null
    ): JsonResponse {
        $data ??= [];
        $data = $this->morphToArray(data: $data);

        return $this->apiResponse(
            data: $data,
            code: Response::HTTP_NO_CONTENT
        );
    }

    private function apiResponse(array $data, int $code = 200): JsonResponse
    {
        return response()->json(data: $data, status: $code);
    }

    private function morphToArray(array|Arrayable|JsonSerializable|null $data): ?array
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof JsonSerializable) {
            return $data->jsonSerialize();
        }

        return $data;
    }

    private function morphMessage(string|Exception $message): string
    {
        return $message instanceof Exception
            ? $message->getMessage()
            : $message;
    }
}
