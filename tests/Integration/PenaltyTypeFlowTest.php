<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\PenaltyTypeRepository;
use Tester\Assert;

class PenaltyTypeFlowTest extends \Tests\DbTestCase
{
    private PenaltyTypeRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(PenaltyTypeRepository::class);
    }

    public function testAddEditSoftDeleteFlow(): void
    {
        // ADD
        $type = $this->repo->insert(['name' => 'PenaltyType Flow', 'default_amount' => 25.0, 'is_active' => 1]);
        Assert::truthy($type->id);
        Assert::same('PenaltyType Flow', $type->name);

        // READ
        $found = $this->repo->findById($type->id);
        Assert::same(25.0, (float) $found->default_amount);

        // EDIT
        $this->repo->update($type->id, ['name' => 'Updated Type', 'default_amount' => 35.0]);
        $updated = $this->repo->findById($type->id);
        Assert::same('Updated Type', $updated->name);
        Assert::same(35.0, (float) $updated->default_amount);

        // SOFT DELETE
        $this->repo->delete($type->id);
        $deleted = $this->repo->findById($type->id);
        Assert::same(0, (int) $deleted->is_active);

        // Not in findActive
        $activeIds = array_map(fn($r) => (int) $r->id, $this->repo->findActive()->fetchAll());
        Assert::notContains((int) $type->id, $activeIds);
    }

    public function testInsertedType_appearsInFindAll(): void
    {
        $type = $this->repo->insert(['name' => 'Appear In All', 'default_amount' => 20.0, 'is_active' => 1]);
        $all = $this->repo->findAll()->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $all);
        Assert::contains((int) $type->id, $ids);
    }
}

(new PenaltyTypeFlowTest())->run();