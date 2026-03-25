<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;

class StatisticsModel
{
    public function __construct(private readonly Explorer $database)
    {
    }

    /**
     * Celkový zůstatek fondu pokut.
     * Zůstatek = suma všech pokut (zaplacených i nezaplacených) + bonusy - výdaje.
     * Logika: zaplacené pokuty tvoří příjem, výdaje ho snižují, bonusy přidávají.
     */
    public function getTotalBalance(): float
    {
        $paidPenalties = (float) $this->database->table('penalties')
            ->where('is_paid', 1)
            ->sum('amount');

        $withdrawals = (float) $this->database->table('fund_transactions')
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');

        $bonuses = (float) $this->database->table('fund_transactions')
            ->where('transaction_type', 'bonus')
            ->sum('amount');

        return $paidPenalties + $bonuses - $withdrawals;
    }

    /**
     * Celková suma pokut (všech, zaplacených i nezaplacených).
     */
    public function getTotalPenaltiesSum(): float
    {
        return (float) $this->database->table('penalties')->sum('amount');
    }

    /**
     * Top hříšníci — uživatelé s nejvíce pokutami.
     * @return array<array{initials: string, name: string|null, count: int, total_amount: float}>
     */
    public function getTopOffenders(int $limit = 5): array
    {
        $rows = $this->database->query(
            'SELECT u.id, u.initials, u.name,
                    COUNT(p.id) AS cnt,
                    SUM(p.amount) AS total_amount
             FROM penalties p
             JOIN users u ON u.id = p.user_id
             GROUP BY u.id, u.initials, u.name
             ORDER BY cnt DESC, total_amount DESC
             LIMIT ?',
            $limit
        )->fetchAll();

        return array_map(fn($r) => [
            'id'           => $r->id,
            'initials'     => $r->initials,
            'name'         => $r->name,
            'count'        => (int) $r->cnt,
            'total_amount' => (float) $r->total_amount,
        ], $rows);
    }

    /**
     * Trend pokut po měsících (posledních N měsíců).
     * @return array<array{month: string, count: int, total_amount: float}>
     */
    public function getMonthlyTrend(int $months = 12): array
    {
        $rows = $this->database->query(
            'SELECT DATE_FORMAT(penalty_date, \'%Y-%m\') AS month,
                    COUNT(id) AS cnt,
                    SUM(amount) AS total_amount
             FROM penalties
             WHERE penalty_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month
             ORDER BY month ASC',
            $months
        )->fetchAll();

        return array_map(fn($r) => [
            'month'        => $r->month,
            'count'        => (int) $r->cnt,
            'total_amount' => (float) $r->total_amount,
        ], $rows);
    }

    /**
     * Nejčastější typy pokut.
     * @return array<array{name: string, count: int, total_amount: float}>
     */
    public function getMostCommonTypes(): array
    {
        $rows = $this->database->query(
            'SELECT pt.name, COUNT(p.id) AS cnt, SUM(p.amount) AS total_amount
             FROM penalties p
             JOIN penalty_types pt ON pt.id = p.penalty_type_id
             GROUP BY pt.id, pt.name
             ORDER BY cnt DESC'
        )->fetchAll();

        return array_map(fn($r) => [
            'name'         => $r->name,
            'count'        => (int) $r->cnt,
            'total_amount' => (float) $r->total_amount,
        ], $rows);
    }

    /**
     * Nezaplacené pokuty per uživatel (pouze ti s alespoň 1 nezaplacenou).
     * @return array<array{initials: string, name: string|null, count: int, total_amount: float}>
     */
    public function getUnpaidByUser(): array
    {
        $rows = $this->database->query(
            'SELECT u.initials, u.name,
                    COUNT(p.id) AS cnt,
                    SUM(p.amount) AS total_amount
             FROM penalties p
             JOIN users u ON u.id = p.user_id
             WHERE p.is_paid = 0
             GROUP BY u.id, u.initials, u.name
             ORDER BY total_amount DESC'
        )->fetchAll();

        return array_map(fn($r) => [
            'initials'     => $r->initials,
            'name'         => $r->name,
            'count'        => (int) $r->cnt,
            'total_amount' => (float) $r->total_amount,
        ], $rows);
    }

    /**
     * Celkový počet pokut a nezaplacených pokut.
     * @return array{total: int, unpaid: int, unpaid_amount: float}
     */
    public function getPenaltySummary(): array
    {
        $total = (int) $this->database->table('penalties')->count('*');
        $unpaid = (int) $this->database->table('penalties')->where('is_paid', 0)->count('*');
        $unpaidAmount = (float) $this->database->table('penalties')->where('is_paid', 0)->sum('amount');

        return [
            'total'          => $total,
            'unpaid'         => $unpaid,
            'unpaid_amount'  => $unpaidAmount,
        ];
    }
}
