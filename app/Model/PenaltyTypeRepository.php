<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class PenaltyTypeRepository
{
    public function __construct(private readonly Explorer $database)
    {
    }

    public function findAll(): Selection
    {
        return $this->database->table('penalty_types')->order('name ASC');
    }

    public function findActive(): Selection
    {
        return $this->database->table('penalty_types')
            ->where('is_active', 1)
            ->order('name ASC');
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('penalty_types')->get($id) ?: null;
    }

    public function insert(array $data): ActiveRow
    {
        return $this->database->table('penalty_types')->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('penalty_types')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): void
    {
        $this->update($id, ['is_active' => 0]);
    }
}
