<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Application\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function sales(Request $request): JsonResponse|StreamedResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $report = [
            'period' => ['from' => $from, 'to' => $to],
            'bookings_by_status' => DB::table('bookings')
                ->select('status', DB::raw('count(*) as count'))
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->groupBy('status')
                ->pluck('count', 'status'),
            'payments_by_method' => DB::table('payments')
                ->select('method', DB::raw('sum(amount) as total'))
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to])
                ->groupBy('method')
                ->pluck('total', 'method'),
            'total_revenue' => (float) DB::table('payments')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to])
                ->sum('amount'),
            'total_bookings' => (int) DB::table('bookings')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->count(),
        ];

        if ($request->input('format') === 'csv') {
            return $this->csvDownload('sales-report.csv', [
                ['metric', 'value'],
                ['total_bookings', $report['total_bookings']],
                ['total_revenue', $report['total_revenue']],
                ...collect($report['bookings_by_status'])->map(fn ($v, $k) => ["booking_status_{$k}", $v])->values()->all(),
                ...collect($report['payments_by_method'])->map(fn ($v, $k) => ["payment_method_{$k}", $v])->values()->all(),
            ]);
        }

        return $this->success($report);
    }

    public function leads(Request $request): JsonResponse|StreamedResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $report = [
            'period' => ['from' => $from, 'to' => $to],
            'by_source' => DB::table('leads')
                ->select('source', DB::raw('count(*) as count'))
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->groupBy('source')
                ->pluck('count', 'source'),
            'by_status' => DB::table('leads')
                ->select('status', DB::raw('count(*) as count'))
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->groupBy('status')
                ->pluck('count', 'status'),
            'total' => (int) DB::table('leads')
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->count(),
        ];

        if ($request->input('format') === 'csv') {
            return $this->csvDownload('leads-report.csv', [
                ['dimension', 'key', 'count'],
                ...collect($report['by_source'])->map(fn ($v, $k) => ['source', $k, $v])->values()->all(),
                ...collect($report['by_status'])->map(fn ($v, $k) => ['status', $k, $v])->values()->all(),
            ]);
        }

        return $this->success($report);
    }

    public function overview(): JsonResponse
    {
        $kpis = $this->dashboardService->kpis();

        // Flat keys for simpler UI consumers + nested original shape.
        return $this->success([
            ...$kpis,
            'total_leads' => $kpis['leads']['total'] ?? 0,
            'total_bookings' => $kpis['sales']['total_bookings'] ?? 0,
            'total_revenue' => $kpis['sales']['revenue_collected'] ?? 0,
            'conversion_rate' => $kpis['leads']['conversion_rate'] ?? 0,
        ]);
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    private function csvDownload(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
