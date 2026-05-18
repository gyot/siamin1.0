<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardKegiatanIndexRequest;
use App\Http\Resources\DashboardKegiatanCollection;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function kegiatan(DashboardKegiatanIndexRequest $request): DashboardKegiatanCollection
    {
        $paginator = $this->dashboardService->paginateKegiatanForDashboard($request->validated());

        return new DashboardKegiatanCollection($paginator);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->dashboardService->getDashboardStats());
    }
}
