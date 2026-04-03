<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\UserRepository;
use Tester\Assert;

class UserRepositoryTest extends \Tests\DbTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(UserRepository::class);
    }

    public function testInsert_createsUser(): void
    {
        $row = $this->repo->insert(['initials' => 'UT1', 'name' => 'Test One', 'is_active' => 1]);
        Assert::truthy($row->id);
        Assert::same('UT1', $row->initials);
    }

    public function testFindById_returnsUser(): void
    {
        $row = $this->repo->insert(['initials' => 'UT2', 'is_active' => 1]);
        $found = $this->repo->findById($row->id);
        Assert::notNull($found);
        Assert::same('UT2', $found->initials);
    }

    public function testFindById_returnsNullForMissing(): void
    {
        Assert::null($this->repo->findById(999999));
    }

    public function testFindByInitials_returnsUser(): void
    {
        $this->repo->insert(['initials' => 'UT3', 'is_active' => 1]);
        $found = $this->repo->findByInitials('UT3');
        Assert::notNull($found);
        Assert::same('UT3', $found->initials);
    }

    public function testFindActive_excludesInactiveUser(): void
    {
        $active = $this->repo->insert(['initials' => 'UA1', 'is_active' => 1]);
        $inactive = $this->repo->insert(['initials' => 'UA2', 'is_active' => 0]);
        $activeIds = array_map(fn($r) => (int) $r->id, $this->repo->findActive()->fetchAll());
        Assert::contains((int) $active->id, $activeIds);
        Assert::notContains((int) $inactive->id, $activeIds);
    }

    public function testUpdate_changesName(): void
    {
        $row = $this->repo->insert(['initials' => 'UT4', 'name' => 'Old', 'is_active' => 1]);
        $this->repo->update($row->id, ['name' => 'New Name']);
        $updated = $this->repo->findById($row->id);
        Assert::same('New Name', $updated->name);
    }

    public function testDelete_softDeletesUser(): void
    {
        $row = $this->repo->insert(['initials' => 'UT5', 'is_active' => 1]);
        $this->repo->delete($row->id);
        $found = $this->repo->findById($row->id);
        Assert::same(0, (int) $found->is_active);
    }

    public function testRestore_reactivatesUser(): void
    {
        $row = $this->repo->insert(['initials' => 'UT6', 'is_active' => 0]);
        $this->repo->restore($row->id);
        $found = $this->repo->findById($row->id);
        Assert::same(1, (int) $found->is_active);
    }

    public function testDelete_removesFromFindActive(): void
    {
        $row = $this->repo->insert(['initials' => 'UT7', 'is_active' => 1]);
        $this->repo->delete($row->id);
        $activeIds = array_map(fn($r) => (int) $r->id, $this->repo->findActive()->fetchAll());
        Assert::notContains((int) $row->id, $activeIds);
    }
}

(new UserRepositoryTest())->run();