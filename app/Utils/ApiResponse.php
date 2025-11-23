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

    // تحديد اللغة من الـheader
    $locale = self::getLocaleFromRequest();

    // إذا كان message مصفوفة (ar, en) أو string
    if (is_array($message)) {
        $finalMessage = $message[$locale] ?? $message['ar'] ?? $message['en'] ?? 'Success';
    } else {
        $finalMessage = $message;
    }

    $response = [
        'status' => $code,
        'message' => $finalMessage,
        'meta' => $meta ?? ($data['meta'] ?? null),
        'data' => $data['items'],
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    return response()->json($response, $code);
}

/**
 * Get locale from request header (Accept-Language)
 *
 * @return string
 */
protected static function getLocaleFromRequest(): string
{
    $request = request();
    $acceptLanguage = $request->header('Accept-Language', 'ar');

    // استخراج اللغة الأولى من Accept-Language header
    // مثال: "ar,en;q=0.9" -> "ar"
    $locale = strtolower(explode(',', $acceptLanguage)[0]);
    $locale = explode(';', $locale)[0]; // إزالة quality values
    $locale = trim($locale);

    // إذا كانت اللغة غير معروفة، استخدم العربية كافتراضي
    if (!in_array($locale, ['ar', 'en'])) {
        $locale = 'ar';
    }

    return $locale;
}

    public static function success(string|array $message = 'Success response', $data = [], $resource = null): JsonResponse
    {
        return self::send(Response::HTTP_OK, $message, $data, $resource);
    }

    public static function created(string|array $message = 'Resource created successfully', $data = [], $resource = null): JsonResponse
    {
        return self::send(Response::HTTP_CREATED, $message, $data, $resource);
    }

    public static function badRequest(string|array $message, array $errors = []): JsonResponse
    {
        return self::send(Response::HTTP_BAD_REQUEST, $message, errors: $errors);
    }

    public static function forbidden(string|array $message): JsonResponse
    {
        return self::send(Response::HTTP_FORBIDDEN, $message);
    }

    public static function notFound(string|array $message): JsonResponse
    {
        return self::send(Response::HTTP_NOT_FOUND, $message);
    }

    public static function validationError(string|array $message, $errors = [], $resource = null): JsonResponse
    {
        return self::send(Response::HTTP_UNPROCESSABLE_ENTITY, $message, $errors, $resource);
    }

    public static function error(string|array $message): JsonResponse
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
