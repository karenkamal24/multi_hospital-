<?php

namespace App\Utils;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
public static function send(
    $code = Response::HTTP_OK,
    $message = 'Success response',
    $data = [],
    $meta = null,
    $errors = []
): JsonResponse {
    $data = self::prepareData($data, null);

    $response = [
        'status' => $code,
        'message' => $message,
        'meta' => $meta ?? ($data['meta'] ?? null),
        'data' => $data['items'],
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    return response()->json($response, $code);
}

    public static function success(string $message = 'Success response', $data = [], $resource = null): JsonResponse
    {
        return self::send(Response::HTTP_OK, $message, $data, $resource);
    }

    public static function created(string $message = 'Resource created successfully', $data = [], $resource = null): JsonResponse
    {
        return self::send(Response::HTTP_CREATED, $message, $data, $resource);
    }

    public static function badRequest(string $message, array $errors = []): JsonResponse
    {
        return self::send(Response::HTTP_BAD_REQUEST, $message, errors: $errors);
    }

    public static function forbidden(string $message): JsonResponse
    {
        return self::send(Response::HTTP_FORBIDDEN, $message);
    }

    public static function validationError(string $message, $errors = [], $resource = null): JsonResponse
    {
        return self::send(Response::HTTP_UNPROCESSABLE_ENTITY, $message, $errors, $resource);
    }

    public static function error(string $message): JsonResponse
    {
        return self::send(Response::HTTP_INTERNAL_SERVER_ERROR, $message);
    }

    protected static function getPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    protected static function prepareData($data, $resource)
    {
        if ($data instanceof LengthAwarePaginator) {
            $items = $resource != null ? $resource::collection($data->items()) : $data->items();

            return [
                'meta' => self::getPaginationMeta($data),
                'items' => $items,
            ];
        }

        // If data is a Resource collection or single Resource, resolve it to array
        if ($data instanceof \Illuminate\Http\Resources\Json\ResourceCollection) {
            return ['items' => $data->resolve()];
        }

        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            return ['items' => $data->resolve()];
        }

        return ['items' => $data];
    }
}
