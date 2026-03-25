<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class FundTransactionRepository
{
    public function __construct(private readonly Explorer $database)
    {
    }

    public function findAll(): Selection
    {
        return $this->database->table('fund_transactions')->order('entry_date DESC, id DESC');
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('fund_transactions')->get($id) ?: null;
    }

    public function findByType(string $type): Selection
    {
        return $this->database->table('fund_transactions')
            ->where('transaction_type', $type)
            ->order('entry_date DESC');
    }

    public function insert(array $data): ActiveRow
    {
        return $this->database->table('fund_transactions')->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('fund_transactions')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->database->table('fund_transactions')
            ->where('id', $id)
            ->delete();
    }

    /** Celková suma výdajů z fondu */
    public function getTotalWithdrawals(): float
    {
        return (float) $this->database->table('fund_transactions')
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');
    }

    /** Celková suma bonusů do fondu */
    public function getTotalBonuses(): float
    {
        return (float) $this->database->table('fund_transactions')
            ->where('transaction_type', 'bonus')
            ->sum('amount');
    }
}
