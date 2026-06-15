<?php

declare(strict_types=1);

namespace App\Shared\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse(self::envelope(true, $data, $message, []), $status);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return new JsonResponse(self::envelope(true, $data, $message, []), JsonResponse::HTTP_CREATED);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public static function error(string $message, int $status = JsonResponse::HTTP_BAD_REQUEST, array $errors = []): JsonResponse
    {
        return new JsonResponse(self::envelope(false, null, $message, $errors), $status);
    }

    private static function envelope(bool $success, mixed $data, ?string $message, array $errors): array
    {
        return [
            'success' => $success,
            'data'    => $data,
            'message' => $message,
            'errors'  => $errors,
        ];
    }
}
