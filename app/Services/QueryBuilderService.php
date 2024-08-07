<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueryBuilderService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('queryBuilder');
    }

    public function applyFilters(Builder $query, Request $request, string $modelName): Builder
    {
        $allowedFilters = $this->getAllowedFilters($modelName);
        foreach ($allowedFilters as $filter) {
            if ($request->has($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return $query;
    }

    public function applySorting(Builder $query, Request $request, string $modelName): Builder
    {
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = $this->getAllowedSorts($modelName);

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    public function applyPagination(Builder $query, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = $request->input('per_page', 10); // Počet položiek na stránku
        return $query->paginate($perPage);
    }

    public function applyGrouping(Builder $query, Request $request, string $modelName): Builder
    {
        $groupBy = $request->input('group_by');
        $allowedGroupBy = $this->getAllowedGroupBy($modelName);

        if (is_array($groupBy)) {
            $groupBy = array_intersect($groupBy, $allowedGroupBy);
            if (!empty($groupBy)) {
                $query->groupBy($groupBy);
            }
        }

        return $query;
    }

    protected function getAllowedFilters(string $modelName): array
    {
        return $this->config[$modelName]['filters'] ?? [];
    }

    protected function getAllowedSorts(string $modelName): array
    {
        return $this->config[$modelName]['sorts'] ?? [];
    }

    protected function getAllowedGroupBy(string $modelName): array
    {
        return $this->config[$modelName]['group_by'] ?? [];
    }
}
