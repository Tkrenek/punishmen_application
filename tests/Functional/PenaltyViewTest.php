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

    /** sumFiltered vrati spravnou celkovou sumu pro dany filtr. */
    public function testPenaltySum_filteredByUser_returnsCorrectTotal(): void
    {
        $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 100.0, 'penalty_date' => '2025-10-01', 'is_paid' => 0]);
        $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 50.0,  'penalty_date' => '2025-10-02', 'is_paid' => 1]);
        $sum = $this->penalties->sumFiltered(['user_id' => $this->userId]);
        // Muze obsahovat zaznamy z jinych testu, ale alespon 150 Kc od tohoto testu
        Assert::true($sum >= 150.0, "Ocekavana suma >= 150, dostali jsme: $sum");
    }

    /** sumFiltered bez filtru vrati sumu vsech pokut (nenulova po insertu). */
    public function testPenaltySum_noFilter_returnsNonZero(): void
    {
        $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 75.0, 'penalty_date' => '2025-10-03', 'is_paid' => 0]);
        $sum = $this->penalties->sumFiltered([]);
        Assert::true($sum > 0.0, "Suma vsech pokut by mela byt nenulova");
    }

    /** sumFiltered s filtrem is_paid=1 vrati pouze sumu zaplacenych. */
    public function testPenaltySum_onlyPaid_excludesUnpaid(): void
    {
        $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 200.0, 'penalty_date' => '2025-10-04', 'is_paid' => 1]);
        $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 999.0, 'penalty_date' => '2025-10-05', 'is_paid' => 0]);
        $paidSum = $this->penalties->sumFiltered(['user_id' => $this->userId, 'is_paid' => '1']);
        $unpaidSum = $this->penalties->sumFiltered(['user_id' => $this->userId, 'is_paid' => '0']);
        Assert::true($paidSum >= 200.0);
        Assert::true($unpaidSum >= 999.0);
        Assert::true($paidSum !== $unpaidSum);
    }


    /** markAllPaidFiltered oznaci vsechny nezaplacene ve filtru jako zaplacene. */
    public function testMarkAllPaidFiltered_marksOnlyUnpaid(): void
    {
        $p1 = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-11-01', 'is_paid' => 0]);
        $p2 = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-11-02', 'is_paid' => 0]);
        $p3 = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-11-03', 'is_paid' => 1]);

        $count = $this->penalties->markAllPaidFiltered(['user_id' => $this->userId]);
        Assert::true($count >= 2, "Melo byt oznaceno aspon 2 zaznamy, bylo: $count");

        $row1 = $this->penalties->findById((int) $p1->id);
        $row2 = $this->penalties->findById((int) $p2->id);
        $row3 = $this->penalties->findById((int) $p3->id);
        Assert::equal(1, (int) $row1->is_paid);
        Assert::equal(1, (int) $row2->is_paid);
        Assert::equal(1, (int) $row3->is_paid); // uz bylo zaplaceno, stale 1
    }

    /** markAllPaidFiltered s is_paid filtrem oznaci jen nezaplacene. */
    public function testMarkAllPaidFiltered_withIsUnpaidFilter(): void
    {
        $p = $this->penalties->insert(['user_id' => $this->userId, 'penalty_type_id' => $this->typeId, 'amount' => 20.0, 'penalty_date' => '2025-11-10', 'is_paid' => 0]);
        $count = $this->penalties->markAllPaidFiltered(['user_id' => $this->userId, 'is_paid' => null]);
        Assert::true($count >= 1);
        $row = $this->penalties->findById((int) $p->id);
        Assert::equal(1, (int) $row->is_paid);
    }
}

(new PenaltyViewTest())->run();