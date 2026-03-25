<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class UserRepository
{
    public function __construct(private readonly Explorer $database)
    {
    }

    public function findAll(): Selection
    {
        return $this->database->table('users')->order('initials ASC');
    }

    public function findActive(): Selection
    {
        return $this->database->table('users')
            ->where('is_active', 1)
            ->order('initials ASC');
    }

    public function findById(int $id): ?ActiveRow
    {
        return $this->database->table('users')->get($id) ?: null;
    }

    public function findByInitials(string $initials): ?ActiveRow
    {
        return $this->database->table('users')
            ->where('initials', $initials)
            ->fetch() ?: null;
    }

    public function insert(array $data): ActiveRow
    {
        return $this->database->table('users')->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->database->table('users')
            ->where('id', $id)
            ->update($data);
    }

    /** Soft delete — pouze deaktivuje uživatele */
    public function delete(int $id): void
    {
        $this->update($id, ['is_active' => 0]);
    }

    public function restore(int $id): void
    {
        $this->update($id, ['is_active' => 1]);
    }
}
