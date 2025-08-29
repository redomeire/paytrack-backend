<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\MonthlyCategorySummary;
use App\Http\Controllers\BaseController;

class MonthlyCategorySummaryController extends BaseController
{
    public function getSpendingCountByCategory(Request $request)
    {
        // TODO: add Redis caching
        try {
            $user = $request->user();
            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;

            $data = MonthlyCategorySummary::where('user_id', $user->id)
                ->where('summary_year', $year)
                ->where('summary_month', $month)
                ->with('billCategory')
                ->get();

            // count sum of total_amount_spent
            $totalSpending = $data->sum('total_amount_spent');
            $labels = $data->pluck('billCategory.name');
            $totals = $data->pluck('total_amount_spent');
            $series = [];
            foreach ($data as $item) {
                $percentage = $totalSpending > 0 ? ($item->total_amount_spent / $totalSpending) * 100 : 0;
                array_push($series, round($percentage, 2));
            }

            return $this->sendResponse([
                'labels' => $labels,
                'totals' => $totals,
                'series' => $series,
            ], 'Get spending count by category success');
        } catch (\Throwable $th) {
            return $this->sendError('Get spending count by category failed', ['error' => $th->getMessage()], 500);
        }
    }
    public function getMonthlySpendingTrend(Request $request)
    {
        // TODO: add Redis caching
        try {
            $user = $request->user();
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
            return $this->sendResponse([
                'labels' => $labels,
                'totals' => $totals,
            ], 'Get monthly spending trend success');
        } catch (\Throwable $th) {
            return $this->sendError('Get monthly spending trend failed', ['error' => $th->getMessage()], 500);
        }
    }
}
