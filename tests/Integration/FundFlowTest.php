<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\FundTransactionRepository;
use Tester\Assert;

class FundFlowTest extends \Tests\DbTestCase
{
    private FundTransactionRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(FundTransactionRepository::class);
    }

    public function testAddWithdrawalFlow(): void
    {
        $tx = $this->repo->insert([
            'user_id'          => null,
            'entry_date'       => '2025-07-01',
            'amount'           => 150.0,
            'description'      => 'Team dinner',
            'transaction_type' => 'withdrawal',
        ]);
        Assert::truthy($tx->id);
        Assert::same(150.0, (float) $tx->amount);
        $found = $this->repo->findById($tx->id);
        Assert::same('Team dinner', $found->description);
    }

    public function testAddBonusFlow(): void
    {
        $tx = $this->repo->insert([
            'user_id'          => null,
            'entry_date'       => '2025-07-02',
            'amount'           => 80.0,
            'description'      => 'Company bonus',
            'transaction_type' => 'bonus',
        ]);
        Assert::same('bonus', $tx->transaction_type);
        $bonuses = $this->repo->findByType('bonus')->fetchAll();
        $ids = array_map(fn($r) => (int) $r->id, $bonuses);
        Assert::contains((int) $tx->id, $ids);
    }

    public function testDeleteTransaction(): void
    {
        $tx = $this->repo->insert([
            'user_id' => null, 'entry_date' => '2025-07-03',
            'amount' => 50.0, 'description' => 'Del test',
            'transaction_type' => 'withdrawal',
        ]);
        $this->repo->delete($tx->id);
        Assert::null($this->repo->findById($tx->id));
    }

    public function testTotals_sumsWithdrawalsAndBonuses(): void
    {
        $this->repo->insert(['user_id' => null, 'entry_date' => '2025-07-04', 'amount' => 100.0, 'description' => 'W1', 'transaction_type' => 'withdrawal']);
        $this->repo->insert(['user_id' => null, 'entry_date' => '2025-07-05', 'amount' => 60.0, 'description' => 'B1', 'transaction_type' => 'bonus']);
        Assert::true($this->repo->getTotalWithdrawals() >= 100.0);
        Assert::true($this->repo->getTotalBonuses() >= 60.0);
    }
}

(new FundFlowTest())->run();