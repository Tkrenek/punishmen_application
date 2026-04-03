<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\PenaltyRepository;
use App\Model\UserRepository;
use App\Model\PenaltyTypeRepository;
use Tester\Assert;

class PenaltyFlowTest extends \Tests\DbTestCase
{
    private PenaltyRepository $penalties;
    private int $userId;
    private int $typeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->penalties = $this->container->getByType(PenaltyRepository::class);
        $this->userId = (int) $this->container->getByType(UserRepository::class)
            ->insert(['initials' => 'PF1', 'is_active' => 1])->id;
        $this->typeId = (int) $this->container->getByType(PenaltyTypeRepository::class)
            ->insert(['name' => 'PF Type', 'default_amount' => 20.0, 'is_active' => 1])->id;
    }

    private function mkPenalty(string $date = '2025-05-10', int $isPaid = 0): \Nette\Database\Table\ActiveRow
    {
        return $this->penalties->insert([
            'user_id'         => $this->userId,
            'penalty_type_id' => $this->typeId,
            'amount'          => 20.0,
            'penalty_date'    => $date,
            'is_paid'         => $isPaid,
        ]);
    }

    public function testAddPenalty_isFoundInListing(): void
    {
        $penalty = $this->mkPenalty();
        $all = $this->penalties->findAll()->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $all);
        Assert::contains((int) $penalty->id, $ids);
    }

    public function testMarkPaid_changesStatus(): void
    {
        $penalty = $this->mkPenalty();
        Assert::same(0, (int) $penalty->is_paid);
        $this->penalties->markAsPaid($penalty->id);
        Assert::same(1, (int) $this->penalties->findById($penalty->id)->is_paid);
    }

    public function testMarkUnpaid_changesStatus(): void
    {
        $penalty = $this->mkPenalty('2025-05-11', 1);
        $this->penalties->markAsUnpaid($penalty->id);
        Assert::same(0, (int) $this->penalties->findById($penalty->id)->is_paid);
    }

    public function testDeletePenalty_isRemovedFromDB(): void
    {
        $penalty = $this->mkPenalty();
        $this->penalties->delete($penalty->id);
        Assert::null($this->penalties->findById($penalty->id));
    }

    public function testFindFiltered_byDateRange(): void
    {
        $p1 = $this->mkPenalty('2025-01-10');
        $p2 = $this->mkPenalty('2025-03-10');
        $results = $this->penalties->findFiltered(['date_from' => '2025-02-01', 'date_to' => '2025-12-31'])->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $p2->id, $ids);
        Assert::notContains((int) $p1->id, $ids);
    }

    public function testFindFiltered_byPenaltyTypeId(): void
    {
        $penalty = $this->mkPenalty();
        $results = $this->penalties->findFiltered(['penalty_type_id' => $this->typeId])->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $penalty->id, $ids);
    }
}

(new PenaltyFlowTest())->run();