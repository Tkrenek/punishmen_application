<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\PenaltyTypeRepository;
use App\Presenters\PenaltyTypePresenter;
use Nette\Application\IPresenterFactory;
use Tester\Assert;

class PenaltyTypeViewTest extends \Tests\DbTestCase
{
    private PenaltyTypeRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(PenaltyTypeRepository::class);
    }

    public function testPenaltyTypePresenter_canBeCreated(): void
    {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter('PenaltyType');
        Assert::type(PenaltyTypePresenter::class, $presenter);
    }

    public function testPenaltyTypeList_containsInserted(): void
    {
        $type = $this->repo->insert(['name' => 'View Type Test', 'default_amount' => 20.0, 'is_active' => 1]);
        $names = array_map(fn($t) => $t->name, $this->repo->findAll()->fetchAll());
        Assert::contains('View Type Test', $names);
    }

    public function testActivePenaltyTypes_afterSoftDelete(): void
    {
        $type = $this->repo->insert(['name' => 'Will Be Deleted', 'default_amount' => 20.0, 'is_active' => 1]);
        $this->repo->delete($type->id);
        $activeIds = array_map(fn($t) => (int) $t->id, $this->repo->findActive()->fetchAll());
        Assert::notContains((int) $type->id, $activeIds);
    }
}

(new PenaltyTypeViewTest())->run();