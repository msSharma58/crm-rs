<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Application\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function sales(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $report = [
            'period' => ['from' => $from, 'to' => $to],
            'bookings_by_status' => DB::table('bookings')
                ->select('status', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('status')
                ->pluck('count', 'status'),
            'payments_by_method' => DB::table('payments')
                ->select('method', DB::raw('sum(amount) as total'))
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to])
                ->groupBy('method')
                ->pluck('total', 'method'),
        ];

        return $this->success($report);
    }

    public function leads(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $report = [
            'period' => ['from' => $from, 'to' => $to],
            'by_source' => DB::table('leads')
                ->select('source', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('source')
                ->pluck('count', 'source'),
            'by_status' => DB::table('leads')
                ->select('status', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        return $this->success($report);
    }

    public function overview(): JsonResponse
    {
        return $this->success($this->dashboardService->kpis());
    }
}
