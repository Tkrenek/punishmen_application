<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\PenaltyTypeRepository;
use Tester\Assert;

class PenaltyTypeRepositoryTest extends \Tests\DbTestCase
{
    private PenaltyTypeRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(PenaltyTypeRepository::class);
    }

    public function testInsert_createsPenaltyType(): void
    {
        $row = $this->repo->insert(['name' => 'Test Penalty', 'default_amount' => 50.00, 'is_active' => 1]);
        Assert::truthy($row->id);
        Assert::same('Test Penalty', $row->name);
    }

    public function testFindById_returnsType(): void
    {
        $row = $this->repo->insert(['name' => 'PT Find', 'default_amount' => 20.00, 'is_active' => 1]);
        $found = $this->repo->findById($row->id);
        Assert::notNull($found);
        Assert::same('PT Find', $found->name);
    }

    public function testFindById_returnsNullForMissing(): void
    {
        Assert::null($this->repo->findById(999999));
    }

    public function testFindActive_excludesInactive(): void
    {
        $this->repo->insert(['name' => 'Active Type', 'default_amount' => 20.00, 'is_active' => 1]);
        $inactive = $this->repo->insert(['name' => 'Inactive Type', 'default_amount' => 20.00, 'is_active' => 0]);
        $activeIds = array_map(fn($r) => (int) $r->id, $this->repo->findActive()->fetchAll());
        Assert::notContains((int) $inactive->id, $activeIds);
    }

    public function testUpdate_changesName(): void
    {
        $row = $this->repo->insert(['name' => 'Old Name', 'default_amount' => 20.00, 'is_active' => 1]);
        $this->repo->update($row->id, ['name' => 'New Name']);
        $found = $this->repo->findById($row->id);
        Assert::same('New Name', $found->name);
    }

    public function testDelete_softDeletesType(): void
    {
        $row = $this->repo->insert(['name' => 'To Delete', 'default_amount' => 20.00, 'is_active' => 1]);
        $this->repo->delete($row->id);
        $found = $this->repo->findById($row->id);
        Assert::same(0, (int) $found->is_active);
    }
}

(new PenaltyTypeRepositoryTest())->run();