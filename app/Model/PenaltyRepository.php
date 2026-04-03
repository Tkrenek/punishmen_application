<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class PenaltyRepository
{
    private const DEFAULT_PAGE_SIZE = 25;

    public function __construct(private readonly Explorer $database)
    {
    }

    public function findAll(): Selection
    {
        return $this->database->table('penalties')->order('penalty_date DESC, id DESC');
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('penalties')->get($id) ?: null;
    }

    /**
     * Filtrovaný výpis pokut pro stránku výpisu.
     *
     * @param array{
     *   user_id?: int|null,
     *   penalty_type_id?: int|null,
     *   is_paid?: int|null,
     *   date_from?: string|null,
     *   date_to?: string|null
     * } $filters
     */
    public function findFiltered(array $filters = []): Selection
    {
        $query = $this->database->table('penalties')->order('penalty_date DESC, id DESC');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['penalty_type_id'])) {
            $query->where('penalty_type_id', (int) $filters['penalty_type_id']);
        }

        if (isset($filters['is_paid']) && $filters['is_paid'] !== '') {
            $query->where('is_paid', (int) $filters['is_paid']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('penalty_date >= ?', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('penalty_date <= ?', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Vrátí součet `amount` pro filtrované pokuty (respektuje stejné filtry jako findFiltered).
     */
    public function sumFiltered(array $filters = []): float
    {
        $query = $this->findFiltered($filters);
        return (float) $query->sum('amount');
    }

    public function insert(array $data): ActiveRow
    {
        return $this->database->table('penalties')->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('penalties')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('penalties')
            ->where('id', $id)
            ->delete();
    }

    public function markAsPaid(int $id): void
    {
        $this->update($id, ['is_paid' => 1]);
    }

    public function markAsUnpaid(int $id): void
    {
        $this->update($id, ['is_paid' => 0]);
    }

    public function getPageSize(): int
    {
        return self::DEFAULT_PAGE_SIZE;
    }
}
