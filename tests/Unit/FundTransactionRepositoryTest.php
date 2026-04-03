<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\FundTransactionRepository;
use Nette\Database\Table\ActiveRow;
use Tester\Assert;

class FundTransactionRepositoryTest extends \Tests\DbTestCase
{
    private FundTransactionRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(FundTransactionRepository::class);
    }

    private function mkTx(string $type = 'withdrawal', float $amount = 100.0): ActiveRow
    {
        return $this->repo->insert([
            'user_id'          => null,
            'entry_date'       => '2025-06-01',
            'amount'           => $amount,
            'description'      => 'Test transaction',
            'transaction_type' => $type,
        ]);
    }

    public function testInsert_createsTransaction(): void
    {
        $row = $this->mkTx('withdrawal', 200.0);
        Assert::truthy($row->id);
        Assert::same(200.0, (float) $row->amount);
    }

    public function testFindById_returnsTransaction(): void
    {
        $row   = $this->mkTx();
        $found = $this->repo->findById($row->id);
        Assert::notNull($found);
        Assert::same((int) $row->id, (int) $found->id);
    }

    public function testFindById_returnsNullForMissing(): void
    {
        Assert::null($this->repo->findById(999999));
    }

    public function testFindByType_filtersCorrectly(): void
    {
        $w = $this->mkTx('withdrawal', 50.0);
        $b = $this->mkTx('bonus', 30.0);
        $withdrawals = $this->repo->findByType('withdrawal')->fetchAll();
        $wIds = array_map(fn($r) => (int) $r->id, $withdrawals);
        Assert::contains((int) $w->id, $wIds);
        Assert::notContains((int) $b->id, $wIds);
    }

    public function testGetTotalWithdrawals_sumsCorrectly(): void
    {
        $this->mkTx('withdrawal', 100.0);
        $this->mkTx('withdrawal', 50.0);
        $total = $this->repo->getTotalWithdrawals();
        Assert::true($total >= 150.0);
    }

    public function testGetTotalBonuses_sumsCorrectly(): void
    {
        $this->mkTx('bonus', 200.0);
        $total = $this->repo->getTotalBonuses();
        Assert::true($total >= 200.0);
    }

    public function testDelete_removesTransaction(): void
    {
        $row = $this->mkTx();
        $this->repo->delete($row->id);
        Assert::null($this->repo->findById($row->id));
    }
}

(new FundTransactionRepositoryTest())->run();