<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MonthlyCategorySummary;
use App\Http\Controllers\BaseController;

class MonthlyCategorySummaryController extends BaseController
{
    public function getSummary(Request $request)
    {
        $user = $request->user();
        $summaryData = Cache::remember(
            "dashboard:summary:{$user->id}",
            60 * 2,
            function () use ($user) {
                $now = Carbon::now();
                $thirtyDaysFromNow = $now->copy()->addDays(30);

                $summary = bills::where('user_id', $user->id)
                    ->selectRaw("
                    SUM(CASE WHEN status IN ('Pending', 'Overdue') THEN amount ELSE 0 END) as total_unpaid,
                    COUNT(CASE WHEN status = 'Pending' AND due_date BETWEEN ? AND ? THEN 1 END) as upcoming_count,
                    COUNT(CASE WHEN YEAR(due_date) = ? AND MONTH(due_date) = ? THEN 1 END) as total_bills_this_month,
                    COUNT(CASE WHEN status = 'Pending' AND due_date < ? THEN 1 END) as overdue_count
                ", [
                        $now,
                        $thirtyDaysFromNow,
                        $now->year,
                        $now->month,
                        $now->toDateString(),
                    ])
                    ->first();

                return [
                    'total_unpaid' => (float) $summary->total_unpaid,
                    'upcoming_count' => (int) $summary->upcoming_count,
                    'total_bills_this_month' => (int) $summary->total_bills_this_month,
                    'overdue_count' => (int) $summary->overdue_count,
                ];
            });

        return $this->sendResponse($summaryData, 'Dashboard summary retrieved successfully.');
    }
    public function getSpendingCountByCategory(Request $request)
    {
        try {
            $user = $request->user();
            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;
            Log::info("Check cache spending_count_by_category_user_{$user->id}_{$year}_{$month}");
            $data = Cache::remember(
                "spending_count_by_category_user_{$user->id}_{$year}_{$month}",
                5 * 60 * 60,
                function () use ($user, $year, $month) {
                    Log::info("retrieve from DB spending_count_by_category_user_{$user->id}_{$year}_{$month}");
                    $categorySummaries = MonthlyCategorySummary::where('user_id', $user->id)
                        ->where('summary_year', $year)
                        ->where('summary_month', $month)
                        ->with('billCategory')
                        ->get();

                    $totalSpending = $categorySummaries->sum('total_amount_spent');
                    $labels = $categorySummaries->pluck('billCategory.name');
                    $totals = $categorySummaries->pluck('total_amount_spent');
                    $series = [];
                    foreach ($categorySummaries as $item) {
                        $percentage = $totalSpending > 0 ? ($item->total_amount_spent / $totalSpending) * 100 : 0;
                        array_push($series, round($percentage, 2));
                    }
                    return [
                        'labels' => $labels,
                        'totals' => $totals,
                        'series' => $series,
                    ];
                });

            return $this->sendResponse($data, 'Get spending count by category success');
        } catch (\Throwable $th) {
            return $this->sendError('Get spending count by category failed', ['error' => $th->getMessage()], 500);
        }
    }
    public function getMonthlySpendingTrend(Request $request)
    {
        try {
            $user = $request->user();
            $data = Cache::remember(
                "monthly_spending_trend_user_{$user->id}",
                5 * 60 * 60,
                function () use ($user) {
                    $aggregatedValue = MonthlyCategorySummary::where('user_id', $user->id)
                        ->whereRaw("STR_TO_DATE(CONCAT(summary_year, '-', summary_month, '-01'), '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)")
                        ->selectRaw('summary_year, summary_month, SUM(total_amount_spent) as total_spent')
                        ->groupBy('summary_year', 'summary_month')
                        ->orderBy('summary_year', 'asc')
                        ->orderBy('summary_month', 'asc')
                        ->get();

                    $labels = [];
                    $totals = [];
                    foreach ($aggregatedValue as $item) {
                        $labels[] = Carbon::create($item->summary_year, $item->summary_month, 1)->format('F Y');
                        $totals[] = $item->total_spent;
                    }

                    return [
                        'labels' => $labels,
                        'totals' => $totals,
                    ];
                });
            return $this->sendResponse($data, 'Get monthly spending trend success');
        } catch (\Throwable $th) {
            return $this->sendError('Get monthly spending trend failed', ['error' => $th->getMessage()], 500);
        }
    }
}
