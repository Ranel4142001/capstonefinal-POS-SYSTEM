<?php

class AnalyticsService
{
    private AnalyticsRepository $repository;

    public function __construct(AnalyticsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function buildDashboard(array $input): array
    {
        $filter = $this->resolveDateFilter($input);
        $startDateTime = $filter['start']->format('Y-m-d 00:00:00');
        $endDateTimeExclusive = $filter['end']->modify('+1 day')->format('Y-m-d 00:00:00');

        $revenueSummary = $this->repository->getRevenueSummary($startDateTime, $endDateTimeExclusive);
        $refundSummary = $this->repository->getRefundSummary($startDateTime, $endDateTimeExclusive);
        $costSummary = $this->repository->getCostOfGoodsSold($startDateTime, $endDateTimeExclusive);
        $refundedCostSummary = $this->repository->getRefundedCostOfGoods($startDateTime, $endDateTimeExclusive);

        $grossRevenue = (float) ($revenueSummary['gross_revenue'] ?? 0);
        $refundTotal = (float) ($refundSummary['refund_total'] ?? 0);
        $transactionCount = (int) ($revenueSummary['transaction_count'] ?? 0);
        $grossCostOfGoods = (float) ($costSummary['gross_cost_of_goods'] ?? 0);
        $refundedCostOfGoods = (float) ($refundedCostSummary['refunded_cost_of_goods'] ?? 0);

        $totalRevenue = $grossRevenue - $refundTotal;
        $netCostOfGoods = $grossCostOfGoods - $refundedCostOfGoods;
        $netProfit = $totalRevenue - $netCostOfGoods;
        $averageBasketValue = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0.0;

        $salesTrendGranularity = $this->determineSalesTrendGranularity($filter['start'], $filter['end']);
        $salesTrendRows = $salesTrendGranularity === 'month'
            ? $this->repository->getMonthlyRevenueTrend($startDateTime, $endDateTimeExclusive)
            : $this->repository->getDailyRevenueTrend($startDateTime, $endDateTimeExclusive);
        $topProductRows = $this->repository->getTopSellingProducts($startDateTime, $endDateTimeExclusive, 5);
        $categoryRows = $this->repository->getRevenueByCategory($startDateTime, $endDateTimeExclusive);
        $peakHourRows = $this->repository->getPeakSalesHours($startDateTime, $endDateTimeExclusive);
        $stockValueRow = $this->repository->getTotalStockValue();
        $yearlyPerformanceRows = $this->repository->getYearlyPerformanceSummary($startDateTime, $endDateTimeExclusive);

        $slowMovingEnd = new DateTimeImmutable('today');
        $slowMovingStart = $slowMovingEnd->modify('-29 days');
        $slowMovingStartDateTime = $slowMovingStart->format('Y-m-d 00:00:00');
        $slowMovingEndDateTimeExclusive = $slowMovingEnd->modify('+1 day')->format('Y-m-d 00:00:00');
        $slowMovingCount = $this->repository->getSlowMovingItemsCount($slowMovingStartDateTime, $slowMovingEndDateTimeExclusive);
        $slowMovingItems = $this->repository->getSlowMovingItems($slowMovingStartDateTime, $slowMovingEndDateTimeExclusive, 10);

        return [
            'success' => true,
            'generated_at' => date('c'),
            'filter' => [
                'preset' => $filter['preset'],
                'label' => $filter['label'],
                'start_date' => $filter['start']->format('Y-m-d'),
                'end_date' => $filter['end']->format('Y-m-d'),
                'available_presets' => ['today', 'last_7_days', 'last_30_days', 'this_month', 'custom'],
            ],
            'kpis' => [
                'total_revenue' => round($totalRevenue, 2),
                'net_profit' => round($netProfit, 2),
                'transaction_count' => $transactionCount,
                'average_basket_value' => round($averageBasketValue, 2),
                'gross_sales' => round($grossRevenue, 2),
                'refunds_total' => round($refundTotal, 2),
                'cost_of_goods_sold' => round($netCostOfGoods, 2),
            ],
            'charts' => [
                'sales_trends' => $this->buildSalesTrendChart(
                    $filter['start'],
                    $filter['end'],
                    $salesTrendRows,
                    $salesTrendGranularity,
                    $this->buildYearlyBreakdown($filter['start'], $filter['end'], $yearlyPerformanceRows)
                ),
                'top_products' => $this->buildTopProductsChart($topProductRows),
                'revenue_by_category' => $this->buildRevenueByCategoryChart($categoryRows),
                'peak_sales_hours' => $this->buildPeakSalesHoursChart($peakHourRows),
            ],
            'inventory' => [
                'total_stock_value' => round((float) ($stockValueRow['total_stock_value'] ?? 0), 2),
                'slow_moving_window_label' => $slowMovingStart->format('M d, Y') . ' - ' . $slowMovingEnd->format('M d, Y'),
                'slow_moving_count' => $slowMovingCount,
                'slow_moving_items' => array_map(function (array $item): array {
                    return [
                        'product_id' => (int) $item['product_id'],
                        'product_name' => (string) $item['product_name'],
                        'category_name' => (string) $item['category_name'],
                        'stock_quantity' => (int) $item['stock_quantity'],
                        'cost_price' => round((float) $item['cost_price'], 2),
                        'stock_value' => round((float) $item['stock_value'], 2),
                    ];
                }, $slowMovingItems),
            ],
            'meta' => [
                'has_refunds_table' => $this->repository->hasRefundsTable(),
                'currency' => 'PHP',
            ],
        ];
    }

    public function resolveDateFilter(array $input): array
    {
        $today = new DateTimeImmutable('today');
        $preset = strtolower(trim((string) ($input['preset'] ?? 'last_30_days')));
        $requestedStart = $this->parseDate($input['start_date'] ?? null);
        $requestedEnd = $this->parseDate($input['end_date'] ?? null);

        switch ($preset) {
            case 'today':
                $start = $today;
                $end = $today;
                $label = 'Today';
                break;
            case 'last_7_days':
                $start = $today->modify('-6 days');
                $end = $today;
                $label = 'Last 7 Days';
                break;
            case 'this_month':
                $start = $today->modify('first day of this month');
                $end = $today;
                $label = 'This Month';
                break;
            case 'custom':
                $start = $requestedStart ?? $today->modify('-29 days');
                $end = $requestedEnd ?? $today;
                $label = 'Custom Range';
                break;
            case 'last_30_days':
            default:
                $preset = 'last_30_days';
                $start = $today->modify('-29 days');
                $end = $today;
                $label = 'Last 30 Days';
                break;
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [
            'preset' => $preset,
            'label' => $label,
            'start' => $start,
            'end' => $end,
        ];
    }

    private function buildSalesTrendChart(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        array $rows,
        string $granularity,
        array $yearlyBreakdown
    ): array
    {
        if ($granularity === 'month') {
            return $this->buildMonthlySalesTrendChart($start, $end, $rows, $yearlyBreakdown);
        }

        $rowMap = [];
        foreach ($rows as $row) {
            $rowMap[(string) $row['metric_day']] = round((float) ($row['net_revenue'] ?? 0), 2);
        }

        $labels = [];
        $fullLabels = [];
        $isoDates = [];
        $values = [];
        $cursor = $start;

        while ($cursor <= $end) {
            $isoDate = $cursor->format('Y-m-d');
            $isoDates[] = $isoDate;
            $labels[] = $cursor->format('M d');
            $fullLabels[] = $cursor->format('M d, Y');
            $values[] = $rowMap[$isoDate] ?? 0.0;
            $cursor = $cursor->modify('+1 day');
        }

        return [
            'chart_type' => 'line',
            'granularity' => 'day',
            'description' => 'Daily net revenue across the active range',
            'grouping_note' => 'Daily grouping is used for short date ranges to preserve transaction-level accuracy.',
            'labels' => $labels,
            'full_labels' => $fullLabels,
            'raw_dates' => $isoDates,
            'yearly_breakdown' => $yearlyBreakdown,
            'datasets' => [
                [
                    'label' => 'Daily Net Revenue',
                    'data' => $values,
                ],
            ],
        ];
    }

    private function buildMonthlySalesTrendChart(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        array $rows,
        array $yearlyBreakdown
    ): array
    {
        $rowMap = [];
        foreach ($rows as $row) {
            $rowMap[(string) $row['period_month']] = round((float) ($row['net_revenue'] ?? 0), 2);
        }

        $labels = [];
        $fullLabels = [];
        $periodMonths = [];
        $values = [];
        $cursor = $start->modify('first day of this month');
        $lastMonth = $end->modify('first day of this month');

        while ($cursor <= $lastMonth) {
            $periodKey = $cursor->format('Y-m-01');
            $periodMonths[] = $periodKey;
            $labels[] = $cursor->format('M Y');
            $fullLabels[] = $cursor->format('F Y');
            $values[] = $rowMap[$periodKey] ?? 0.0;
            $cursor = $cursor->modify('+1 month');
        }

        $spansMultipleYears = $start->format('Y') !== $end->format('Y');

        return [
            'chart_type' => 'bar',
            'granularity' => 'month',
            'description' => 'Monthly net revenue across the active range',
            'grouping_note' => $spansMultipleYears
                ? 'Grouped by month automatically so cross-year revenue stays readable and comparable.'
                : 'Grouped by month automatically for longer ranges to keep the trend accurate and readable.',
            'labels' => $labels,
            'full_labels' => $fullLabels,
            'period_months' => $periodMonths,
            'yearly_breakdown' => $yearlyBreakdown,
            'datasets' => [
                [
                    'label' => 'Monthly Net Revenue',
                    'data' => $values,
                ],
            ],
        ];
    }

    private function buildTopProductsChart(array $rows): array
    {
        $labels = [];
        $quantities = [];
        $revenues = [];

        foreach ($rows as $row) {
            $labels[] = (string) $row['product_name'];
            $quantities[] = (int) round((float) ($row['quantity_sold'] ?? 0));
            $revenues[] = round((float) ($row['revenue'] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Quantity Sold',
                    'data' => $quantities,
                ],
            ],
            'revenue' => $revenues,
        ];
    }

    private function buildRevenueByCategoryChart(array $rows): array
    {
        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = (string) $row['category_name'];
            $values[] = round((float) ($row['revenue'] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue by Category',
                    'data' => $values,
                ],
            ],
        ];
    }

    private function buildPeakSalesHoursChart(array $rows): array
    {
        $rowMap = [];
        foreach ($rows as $row) {
            $hour = str_pad((string) $row['sale_hour'], 2, '0', STR_PAD_LEFT);
            $rowMap[$hour] = (int) ($row['transaction_count'] ?? 0);
        }

        $labels = [];
        $values = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourLabel = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
            $labels[] = $hourLabel . ':00';
            $values[] = $rowMap[$hourLabel] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Transactions',
                    'data' => $values,
                ],
            ],
        ];
    }

    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', trim($value));

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    private function determineSalesTrendGranularity(DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        $dayCount = ((int) $start->diff($end)->days) + 1;

        return $dayCount > 45 ? 'month' : 'day';
    }

    private function buildYearlyBreakdown(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        array $yearlyPerformanceRows
    ): array
    {
        $performanceByYear = [];
        foreach ($yearlyPerformanceRows as $row) {
            $year = (int) ($row['metric_year'] ?? 0);
            $performanceByYear[$year] = [
                'revenue' => round((float) ($row['net_revenue'] ?? 0), 2),
                'transaction_count' => (int) ($row['transaction_count'] ?? 0),
            ];
        }

        $breakdown = [];
        $startYear = (int) $start->format('Y');
        $endYear = (int) $end->format('Y');

        for ($year = $startYear; $year <= $endYear; $year++) {
            $revenue = $performanceByYear[$year]['revenue'] ?? 0.0;
            $transactionCount = $performanceByYear[$year]['transaction_count'] ?? 0;

            $breakdown[] = [
                'year' => $year,
                'revenue' => $revenue,
                'transaction_count' => $transactionCount,
                'average_basket_value' => $transactionCount > 0 ? round($revenue / $transactionCount, 2) : 0.0,
            ];
        }

        return $breakdown;
    }
}
