<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Basis semua controller API: pembungkus respons JSON yang konsisten.
 *
 * Semua endpoint mengembalikan objek dengan bentuk seragam:
 *   { "data": <...>, "meta": {...}, "links": {...} }  (koleksi)
 *   { "data": <...> }                                  (tunggal)
 *   { "message": "...", "data": <...> }                (aksi tulis)
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $message = 'Berhasil.', int $status = 200): JsonResponse
    {
        $payload = ['message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data, string $message = 'Sumber daya dibuat.', ?string $location = null): JsonResponse
    {
        $response = response()->json(['message' => $message, 'data' => $data], 201);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * @param  class-string<JsonResource>  $resource
     */
    protected function collection(string $resource, mixed $paginatorOrCollection): AnonymousResourceCollection
    {
        return $resource::collection($paginatorOrCollection);
    }
}
