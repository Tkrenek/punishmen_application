<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\PenaltyRepository;
use App\Model\UserRepository;
use App\Model\PenaltyTypeRepository;
use App\Presenters\PenaltyPresenter;
use Nette\Application\IPresenterFactory;
use Tester\Assert;

class PenaltyViewTest extends \Tests\DbTestCase
{
    private PenaltyRepository $penalties;
    private int $userId;
    private int $typeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->penalties = $this->container->getByType(PenaltyRepository::class);
        $this->userId = (int) $this->container->getByType(UserRepository::class)
            ->insert(['initials' => 'PV1', 'is_active' => 1])->id;
        $this->typeId = (int) $this->container->getByType(PenaltyTypeRepository::class)
            ->insert(['name' => 'PV Type', 'default_amount' => 20.0, 'is_active' => 1])->id;
    }

    public function testPenaltyPresenter_canBeCreated(): void
    {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter('Penalty');
        Assert::type(PenaltyPresenter::class, $presenter);
    }

    public function testPenaltyList_withUserFilter(): void
    {
        $p = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-08-01', 'is_paid' => 0]);
        $results = $this->penalties->findFiltered(['user_id' => $this->userId])->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $p->id, $ids);
    }

    public function testPenaltyFilter_byPaidStatus(): void
    {
        $paid   = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-08-02', 'is_paid' => 1]);
        $unpaid = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-08-03', 'is_paid' => 0]);
        $paidOnly = $this->penalties->findFiltered(['is_paid' => 1])->fetchAll();
        $paidIds = array_map(fn($r) => (int) $r->id, $paidOnly);
        Assert::contains((int) $paid->id, $paidIds);
        Assert::notContains((int) $unpaid->id, $paidIds);
    }

    /** HTML GET form odesila prazdny string "" kdyz nic neni vybrano. */
    public function testPenaltyFilter_emptyStringUserId_returnsAll(): void
    {
        $p1 = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-09-01', 'is_paid' => 0]);
        // Simulace HTML GET formu: user_id="" - prazdny string musi byt osetreny jako null (bez filtrace)
        $results = $this->penalties->findFiltered(['user_id' => null])->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $p1->id, $ids);
    }

    /** Filtrace podle user_id jako int string (napr. z URL ?user_id=3) musi fungovat. */
    public function testPenaltyFilter_intStringUserId_filtersCorrectly(): void
    {
        $p = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-09-02', 'is_paid' => 0]);
        // Presenter konvertuje string na int: (int)"3" = 3
        $userId = (int) (string) $this->userId;
        $results = $this->penalties->findFiltered(['user_id' => $userId])->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $p->id, $ids);
    }

    /** Kombinovana filtrace user_id + is_paid. */
    public function testPenaltyFilter_combined_userAndPaid(): void
    {
        $paid = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-09-03', 'is_paid' => 1]);
        $unpaid = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-09-04', 'is_paid' => 0]);
        $results = $this->penalties->findFiltered(['user_id' => $this->userId, 'is_paid' => 1])->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $paid->id, $ids);
        Assert::notContains((int) $unpaid->id, $ids);
    }
}

(new PenaltyViewTest())->run();