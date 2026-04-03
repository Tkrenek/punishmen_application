<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\PenaltyRepository;
use App\Model\UserRepository;
use App\Model\PenaltyTypeRepository;
use Nette\Database\Table\ActiveRow;
use Tester\Assert;

class PenaltyRepositoryTest extends \Tests\DbTestCase
{
    private PenaltyRepository $repo;
    private int $userId;
    private int $typeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo   = $this->container->getByType(PenaltyRepository::class);
        $this->userId = (int) $this->container->getByType(UserRepository::class)
            ->insert(['initials' => 'PR1', 'is_active' => 1])->id;
        $this->typeId = (int) $this->container->getByType(PenaltyTypeRepository::class)
            ->insert(['name' => 'PR Type', 'default_amount' => 20.00, 'is_active' => 1])->id;
    }

    private function mkPenalty(float $amount = 20.0, int $isPaid = 0): ActiveRow
    {
        return $this->repo->insert([
            'user_id'         => $this->userId,
            'penalty_type_id' => $this->typeId,
            'amount'          => $amount,
            'penalty_date'    => '2025-01-15',
            'is_paid'         => $isPaid,
        ]);
    }

    public function testInsert_createsPenalty(): void
    {
        $row = $this->mkPenalty(30.0);
        Assert::truthy($row->id);
        Assert::same(30.0, (float) $row->amount);
    }

    public function testFindById_returnsPenalty(): void
    {
        $row   = $this->mkPenalty();
        $found = $this->repo->findById($row->id);
        Assert::notNull($found);
        Assert::same((int) $row->id, (int) $found->id);
    }

    public function testFindById_returnsNullForMissing(): void
    {
        Assert::null($this->repo->findById(999999));
    }

    public function testMarkAsPaid_setsIsPaidToOne(): void
    {
        $row = $this->mkPenalty();
        Assert::same(0, (int) $row->is_paid);
        $this->repo->markAsPaid($row->id);
        $updated = $this->repo->findById($row->id);
        Assert::same(1, (int) $updated->is_paid);
    }

    public function testMarkAsUnpaid_setsIsPaidToZero(): void
    {
        $row = $this->mkPenalty(20.0, 1);
        $this->repo->markAsUnpaid($row->id);
        $updated = $this->repo->findById($row->id);
        Assert::same(0, (int) $updated->is_paid);
    }

    public function testDelete_removesPenalty(): void
    {
        $row = $this->mkPenalty();
        $this->repo->delete($row->id);
        Assert::null($this->repo->findById($row->id));
    }

    public function testFindFiltered_byUserId(): void
    {
        $row     = $this->mkPenalty();
        $results = $this->repo->findFiltered(['user_id' => $this->userId])->fetchAll();
        $ids     = array_map(fn($r) => (int) $r->id, $results);
        Assert::contains((int) $row->id, $ids);
    }

    public function testFindFiltered_byIsPaid(): void
    {
        $paid   = $this->mkPenalty(20.0, 1);
        $unpaid = $this->mkPenalty(20.0, 0);
        $paidResults = $this->repo->findFiltered(['is_paid' => 1])->fetchAll();
        $paidIds     = array_map(fn($r) => (int) $r->id, $paidResults);
        Assert::contains((int) $paid->id, $paidIds);
        Assert::notContains((int) $unpaid->id, $paidIds);
    }

    public function testGetPageSize_returns25(): void
    {
        Assert::same(25, $this->repo->getPageSize());
    }
}

(new PenaltyRepositoryTest())->run();