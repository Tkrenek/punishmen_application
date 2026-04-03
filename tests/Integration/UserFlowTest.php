<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\UserRepository;
use Tester\Assert;

class UserFlowTest extends \Tests\DbTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(UserRepository::class);
    }

    public function testFullUserCrudFlow(): void
    {
        // CREATE
        $user = $this->repo->insert(['initials' => 'IF1', 'name' => 'Integration User', 'is_active' => 1]);
        Assert::truthy($user->id);

        // READ
        $found = $this->repo->findById($user->id);
        Assert::same('IF1', $found->initials);
        Assert::same('Integration User', $found->name);

        // UPDATE
        $this->repo->update($user->id, ['name' => 'Updated User']);
        $updated = $this->repo->findById($user->id);
        Assert::same('Updated User', $updated->name);

        // SOFT DELETE
        $this->repo->delete($user->id);
        $deleted = $this->repo->findById($user->id);
        Assert::same(0, (int) $deleted->is_active);

        // Not in findActive
        $activeIds = array_map(fn($r) => (int) $r->id, $this->repo->findActive()->fetchAll());
        Assert::notContains((int) $user->id, $activeIds);

        // RESTORE
        $this->repo->restore($user->id);
        $restored = $this->repo->findById($user->id);
        Assert::same(1, (int) $restored->is_active);
    }

    public function testFindByInitials_afterInsert(): void
    {
        $this->repo->insert(['initials' => 'IF2', 'is_active' => 1]);
        $found = $this->repo->findByInitials('IF2');
        Assert::notNull($found);
        Assert::same('IF2', $found->initials);
    }

    public function testDeactivatedUser_notInFindActive(): void
    {
        $user = $this->repo->insert(['initials' => 'IF3', 'is_active' => 1]);
        $this->repo->delete($user->id);
        $activeIds = array_map(fn($r) => (int) $r->id, $this->repo->findActive()->fetchAll());
        Assert::notContains((int) $user->id, $activeIds);
    }
}

(new UserFlowTest())->run();