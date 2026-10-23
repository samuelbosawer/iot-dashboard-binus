<?php

namespace App\Services;

use App\Http\Requests\FilterSensorDataRequest;
use App\Http\Resources\SensorDataResource;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * One page of the "Riwayat Data" table, rendered with the page instead of fetched from an API.
 * Filters, search and paging travel in the page's query string (?q=&date_from=&date_to=&status=&page=).
 */
class SensorHistoryTable
{
    /**
     * Invalid filters are ignored and reported, so a bad link never turns into a redirect loop.
     *
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, from: ?int, to: ?int, total: int, per_page: int}, filters: array{date_from: ?string, date_to: ?string, status: ?string, q: ?string}, links: array{prev: ?string, next: ?string}, error: ?string}
     */
    public function forRequest(Request $request, int $perPage, string $fragment): array
    {
        $validator = Validator::make(
            $request->only(['date_from', 'date_to', 'status', 'q']),
            collect((new FilterSensorDataRequest)->rules())->except('per_page')->all(),
        );

        $filters = $validator->fails() ? [] : $validator->validated();

        $page = SensorData::query()
            ->filter($filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->fragment($fragment);

        return [
            'data' => SensorDataResource::collection($page->items())->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
            'filters' => [
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'status' => $filters['status'] ?? null,
                'q' => $filters['q'] ?? null,
            ],
            'links' => [
                'prev' => $page->previousPageUrl(),
                'next' => $page->nextPageUrl(),
            ],
            'error' => $validator->errors()->first() ?: null,
        ];
    }
}
