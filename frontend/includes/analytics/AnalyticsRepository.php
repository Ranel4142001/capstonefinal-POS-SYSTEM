<?php

class AnalyticsRepository
{
    private PDO $pdo;
    private ?bool $refundsTableExists = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getRevenueSummary(string $startDateTime, string $endDateTimeExclusive): array
    {
        $sql = "
            SELECT
                COALESCE(SUM(s.total_amount), 0) AS gross_revenue,
                COUNT(DISTINCT s.id) AS transaction_count
            FROM sales s
            WHERE s.sale_date >= :start_date
              AND s.sale_date < :end_date
              AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
        ";

        return $this->fetchOne($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getRefundSummary(string $startDateTime, string $endDateTimeExclusive): array
    {
        if (!$this->hasRefundsTable()) {
            return ['refund_total' => 0.0];
        }

        $sql = "
            SELECT COALESCE(SUM(r.amount_returned), 0) AS refund_total
            FROM refunds r
            WHERE r.refund_date >= :start_date
              AND r.refund_date < :end_date
        ";

        return $this->fetchOne($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getCostOfGoodsSold(string $startDateTime, string $endDateTimeExclusive): array
    {
        $sql = "
            SELECT
                COALESCE(SUM(si.quantity * COALESCE(p.cost_price, 0)), 0) AS gross_cost_of_goods
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            INNER JOIN products p ON p.id = si.product_id
            WHERE s.sale_date >= :start_date
              AND s.sale_date < :end_date
              AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
        ";

        return $this->fetchOne($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getRefundedCostOfGoods(string $startDateTime, string $endDateTimeExclusive): array
    {
        if (!$this->hasRefundsTable()) {
            return ['refunded_cost_of_goods' => 0.0];
        }

        $sql = "
            SELECT
                COALESCE(SUM(si.quantity * COALESCE(p.cost_price, 0)), 0) AS refunded_cost_of_goods
            FROM refunds r
            INNER JOIN sale_items si ON si.sale_id = r.sale_id
            INNER JOIN products p ON p.id = si.product_id
            WHERE r.refund_date >= :start_date
              AND r.refund_date < :end_date
        ";

        return $this->fetchOne($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getDailyRevenueTrend(string $startDateTime, string $endDateTimeExclusive): array
    {
        if ($this->hasRefundsTable()) {
            $sql = "
                SELECT
                    daily_metrics.metric_day,
                    SUM(daily_metrics.amount) AS net_revenue
                FROM (
                    SELECT
                        DATE(s.sale_date) AS metric_day,
                        SUM(s.total_amount) AS amount
                    FROM sales s
                    WHERE s.sale_date >= :start_date
                      AND s.sale_date < :end_date
                      AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                    GROUP BY DATE(s.sale_date)

                    UNION ALL

                    SELECT
                        DATE(r.refund_date) AS metric_day,
                        SUM(r.amount_returned) * -1 AS amount
                    FROM refunds r
                    WHERE r.refund_date >= :start_date
                      AND r.refund_date < :end_date
                    GROUP BY DATE(r.refund_date)
                ) AS daily_metrics
                GROUP BY daily_metrics.metric_day
                ORDER BY daily_metrics.metric_day ASC
            ";
        } else {
            $sql = "
                SELECT
                    DATE(s.sale_date) AS metric_day,
                    SUM(s.total_amount) AS net_revenue
                FROM sales s
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY DATE(s.sale_date)
                ORDER BY metric_day ASC
            ";
        }

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getMonthlyRevenueTrend(string $startDateTime, string $endDateTimeExclusive): array
    {
        if ($this->hasRefundsTable()) {
            $sql = "
                SELECT
                    monthly_metrics.period_month,
                    SUM(monthly_metrics.amount) AS net_revenue
                FROM (
                    SELECT
                        DATE_FORMAT(s.sale_date, '%Y-%m-01') AS period_month,
                        SUM(s.total_amount) AS amount
                    FROM sales s
                    WHERE s.sale_date >= :start_date
                      AND s.sale_date < :end_date
                      AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                    GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m-01')

                    UNION ALL

                    SELECT
                        DATE_FORMAT(r.refund_date, '%Y-%m-01') AS period_month,
                        SUM(r.amount_returned) * -1 AS amount
                    FROM refunds r
                    WHERE r.refund_date >= :start_date
                      AND r.refund_date < :end_date
                    GROUP BY DATE_FORMAT(r.refund_date, '%Y-%m-01')
                ) AS monthly_metrics
                GROUP BY monthly_metrics.period_month
                ORDER BY monthly_metrics.period_month ASC
            ";
        } else {
            $sql = "
                SELECT
                    DATE_FORMAT(s.sale_date, '%Y-%m-01') AS period_month,
                    SUM(s.total_amount) AS net_revenue
                FROM sales s
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m-01')
                ORDER BY period_month ASC
            ";
        }

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getYearlyRevenueSummary(string $startDateTime, string $endDateTimeExclusive): array
    {
        if ($this->hasRefundsTable()) {
            $sql = "
                SELECT
                    yearly_metrics.metric_year,
                    SUM(yearly_metrics.amount) AS net_revenue
                FROM (
                    SELECT
                        YEAR(s.sale_date) AS metric_year,
                        SUM(s.total_amount) AS amount
                    FROM sales s
                    WHERE s.sale_date >= :start_date
                      AND s.sale_date < :end_date
                      AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                    GROUP BY YEAR(s.sale_date)

                    UNION ALL

                    SELECT
                        YEAR(r.refund_date) AS metric_year,
                        SUM(r.amount_returned) * -1 AS amount
                    FROM refunds r
                    WHERE r.refund_date >= :start_date
                      AND r.refund_date < :end_date
                    GROUP BY YEAR(r.refund_date)
                ) AS yearly_metrics
                GROUP BY yearly_metrics.metric_year
                ORDER BY yearly_metrics.metric_year ASC
            ";
        } else {
            $sql = "
                SELECT
                    YEAR(s.sale_date) AS metric_year,
                    SUM(s.total_amount) AS net_revenue
                FROM sales s
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY YEAR(s.sale_date)
                ORDER BY metric_year ASC
            ";
        }

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getYearlyPerformanceSummary(string $startDateTime, string $endDateTimeExclusive): array
    {
        if ($this->hasRefundsTable()) {
            $sql = "
                SELECT
                    yearly_metrics.metric_year,
                    SUM(yearly_metrics.net_revenue_delta) AS net_revenue,
                    SUM(yearly_metrics.transaction_count_delta) AS transaction_count
                FROM (
                    SELECT
                        YEAR(s.sale_date) AS metric_year,
                        SUM(s.total_amount) AS net_revenue_delta,
                        COUNT(DISTINCT s.id) AS transaction_count_delta
                    FROM sales s
                    WHERE s.sale_date >= :start_date
                      AND s.sale_date < :end_date
                      AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                    GROUP BY YEAR(s.sale_date)

                    UNION ALL

                    SELECT
                        YEAR(r.refund_date) AS metric_year,
                        SUM(r.amount_returned) * -1 AS net_revenue_delta,
                        0 AS transaction_count_delta
                    FROM refunds r
                    WHERE r.refund_date >= :start_date
                      AND r.refund_date < :end_date
                    GROUP BY YEAR(r.refund_date)
                ) AS yearly_metrics
                GROUP BY yearly_metrics.metric_year
                ORDER BY yearly_metrics.metric_year ASC
            ";
        } else {
            $sql = "
                SELECT
                    YEAR(s.sale_date) AS metric_year,
                    SUM(s.total_amount) AS net_revenue,
                    COUNT(DISTINCT s.id) AS transaction_count
                FROM sales s
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY YEAR(s.sale_date)
                ORDER BY metric_year ASC
            ";
        }

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getYearlyTransactionSummary(string $startDateTime, string $endDateTimeExclusive): array
    {
        $sql = "
            SELECT
                YEAR(s.sale_date) AS metric_year,
                COUNT(DISTINCT s.id) AS transaction_count
            FROM sales s
            WHERE s.sale_date >= :start_date
              AND s.sale_date < :end_date
              AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
            GROUP BY YEAR(s.sale_date)
            ORDER BY metric_year ASC
        ";

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getTopSellingProducts(string $startDateTime, string $endDateTimeExclusive, int $limit = 5): array
    {
        if ($this->hasRefundsTable()) {
            $sql = "
                SELECT
                    product_metrics.product_id,
                    product_metrics.product_name,
                    SUM(product_metrics.quantity_delta) AS quantity_sold,
                    SUM(product_metrics.revenue_delta) AS revenue
                FROM (
                    SELECT
                        p.id AS product_id,
                        p.name AS product_name,
                        SUM(si.quantity) AS quantity_delta,
                        SUM(si.subtotal) AS revenue_delta
                    FROM sale_items si
                    INNER JOIN sales s ON s.id = si.sale_id
                    INNER JOIN products p ON p.id = si.product_id
                    WHERE s.sale_date >= :start_date
                      AND s.sale_date < :end_date
                      AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                    GROUP BY p.id, p.name

                    UNION ALL

                    SELECT
                        p.id AS product_id,
                        p.name AS product_name,
                        SUM(si.quantity) * -1 AS quantity_delta,
                        SUM(si.subtotal) * -1 AS revenue_delta
                    FROM refunds r
                    INNER JOIN sale_items si ON si.sale_id = r.sale_id
                    INNER JOIN products p ON p.id = si.product_id
                    WHERE r.refund_date >= :start_date
                      AND r.refund_date < :end_date
                    GROUP BY p.id, p.name
                ) AS product_metrics
                GROUP BY product_metrics.product_id, product_metrics.product_name
                HAVING SUM(product_metrics.quantity_delta) > 0
                ORDER BY quantity_sold DESC, revenue DESC, product_metrics.product_name ASC
                LIMIT :result_limit
            ";
        } else {
            $sql = "
                SELECT
                    p.id AS product_id,
                    p.name AS product_name,
                    SUM(si.quantity) AS quantity_sold,
                    SUM(si.subtotal) AS revenue
                FROM sale_items si
                INNER JOIN sales s ON s.id = si.sale_id
                INNER JOIN products p ON p.id = si.product_id
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY p.id, p.name
                ORDER BY quantity_sold DESC, revenue DESC, p.name ASC
                LIMIT :result_limit
            ";
        }

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
            ':result_limit' => $limit,
        ], [
            ':result_limit' => PDO::PARAM_INT,
        ]);
    }

    public function getRevenueByCategory(string $startDateTime, string $endDateTimeExclusive): array
    {
        if ($this->hasRefundsTable()) {
            $sql = "
                SELECT
                    category_metrics.category_name,
                    SUM(category_metrics.revenue_delta) AS revenue
                FROM (
                    SELECT
                        COALESCE(c.name, 'Uncategorized') AS category_name,
                        SUM(si.subtotal) AS revenue_delta
                    FROM sale_items si
                    INNER JOIN sales s ON s.id = si.sale_id
                    INNER JOIN products p ON p.id = si.product_id
                    LEFT JOIN categories c ON c.id = p.category_id
                    WHERE s.sale_date >= :start_date
                      AND s.sale_date < :end_date
                      AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                    GROUP BY COALESCE(c.name, 'Uncategorized')

                    UNION ALL

                    SELECT
                        COALESCE(c.name, 'Uncategorized') AS category_name,
                        SUM(si.subtotal) * -1 AS revenue_delta
                    FROM refunds r
                    INNER JOIN sale_items si ON si.sale_id = r.sale_id
                    INNER JOIN products p ON p.id = si.product_id
                    LEFT JOIN categories c ON c.id = p.category_id
                    WHERE r.refund_date >= :start_date
                      AND r.refund_date < :end_date
                    GROUP BY COALESCE(c.name, 'Uncategorized')
                ) AS category_metrics
                GROUP BY category_metrics.category_name
                HAVING SUM(category_metrics.revenue_delta) <> 0
                ORDER BY revenue DESC, category_metrics.category_name ASC
            ";
        } else {
            $sql = "
                SELECT
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    SUM(si.subtotal) AS revenue
                FROM sale_items si
                INNER JOIN sales s ON s.id = si.sale_id
                INNER JOIN products p ON p.id = si.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY COALESCE(c.name, 'Uncategorized')
                ORDER BY revenue DESC, category_name ASC
            ";
        }

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getPeakSalesHours(string $startDateTime, string $endDateTimeExclusive): array
    {
        $sql = "
            SELECT
                LPAD(HOUR(s.sale_date), 2, '0') AS sale_hour,
                COUNT(DISTINCT s.id) AS transaction_count
            FROM sales s
            WHERE s.sale_date >= :start_date
              AND s.sale_date < :end_date
              AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
            GROUP BY HOUR(s.sale_date)
            ORDER BY HOUR(s.sale_date) ASC
        ";

        return $this->fetchAll($sql, [
            ':start_date' => $startDateTime,
            ':end_date' => $endDateTimeExclusive,
        ]);
    }

    public function getTotalStockValue(): array
    {
        $sql = "
            SELECT
                COALESCE(SUM(COALESCE(stock_quantity, 0) * COALESCE(cost_price, 0)), 0) AS total_stock_value
            FROM products
        ";

        return $this->fetchOne($sql);
    }

    public function getSlowMovingItemsCount(string $windowStartDateTime, string $windowEndDateTimeExclusive): int
    {
        $sql = "
            SELECT COUNT(*) AS slow_moving_count
            FROM products p
            LEFT JOIN (
                SELECT
                    si.product_id,
                    SUM(si.quantity) AS quantity_sold
                FROM sale_items si
                INNER JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY si.product_id
            ) AS recent_sales ON recent_sales.product_id = p.id
            WHERE COALESCE(recent_sales.quantity_sold, 0) = 0
        ";

        $row = $this->fetchOne($sql, [
            ':start_date' => $windowStartDateTime,
            ':end_date' => $windowEndDateTimeExclusive,
        ]);

        return (int) ($row['slow_moving_count'] ?? 0);
    }

    public function getSlowMovingItems(string $windowStartDateTime, string $windowEndDateTimeExclusive, int $limit = 10): array
    {
        $sql = "
            SELECT
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(c.name, 'Uncategorized') AS category_name,
                COALESCE(p.stock_quantity, 0) AS stock_quantity,
                COALESCE(p.cost_price, 0) AS cost_price,
                COALESCE(p.stock_quantity, 0) * COALESCE(p.cost_price, 0) AS stock_value
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN (
                SELECT
                    si.product_id,
                    SUM(si.quantity) AS quantity_sold
                FROM sale_items si
                INNER JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date >= :start_date
                  AND s.sale_date < :end_date
                  AND LOWER(COALESCE(s.status, 'completed')) IN ('completed', 'refunded')
                GROUP BY si.product_id
            ) AS recent_sales ON recent_sales.product_id = p.id
            WHERE COALESCE(recent_sales.quantity_sold, 0) = 0
            ORDER BY p.stock_quantity DESC, p.name ASC
            LIMIT :result_limit
        ";

        return $this->fetchAll($sql, [
            ':start_date' => $windowStartDateTime,
            ':end_date' => $windowEndDateTimeExclusive,
            ':result_limit' => $limit,
        ], [
            ':result_limit' => PDO::PARAM_INT,
        ]);
    }

    public function hasRefundsTable(): bool
    {
        if ($this->refundsTableExists !== null) {
            return $this->refundsTableExists;
        }

        $statement = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
        ");
        $statement->execute([':table_name' => 'refunds']);
        $this->refundsTableExists = ((int) $statement->fetchColumn()) > 0;

        return $this->refundsTableExists;
    }

    private function fetchOne(string $sql, array $params = [], array $types = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, $params, $types);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchAll(string $sql, array $params = [], array $types = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, $params, $types);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function bindValues(PDOStatement $statement, array $params, array $types): void
    {
        foreach ($params as $key => $value) {
            $type = $types[$key] ?? $this->inferPdoType($value);
            $statement->bindValue($key, $value, $type);
        }
    }

    private function inferPdoType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}
