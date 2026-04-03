<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\FundTransactionRepository;
use App\Presenters\FundPresenter;
use Nette\Application\IPresenterFactory;
use Tester\Assert;

class FundViewTest extends \Tests\DbTestCase
{
    private FundTransactionRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(FundTransactionRepository::class);
    }

    public function testFundPresenter_canBeCreated(): void
    {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter('Fund');
        Assert::type(FundPresenter::class, $presenter);
    }

    public function testFundList_containsInserted(): void
    {
        $tx = $this->repo->insert(['user_id' => null, 'entry_date' => '2025-09-01', 'amount' => 75.0, 'description' => 'View test tx', 'transaction_type' => 'withdrawal']);
        $ids = array_map(fn($t) => (int) $t->id, $this->repo->findAll()->fetchAll());
        Assert::contains((int) $tx->id, $ids);
    }

    public function testFundSummary_withdrawalsAndBonuses(): void
    {
        $this->repo->insert(['user_id' => null, 'entry_date' => '2025-09-02', 'amount' => 55.0, 'description' => 'W', 'transaction_type' => 'withdrawal']);
        $this->repo->insert(['user_id' => null, 'entry_date' => '2025-09-03', 'amount' => 25.0, 'description' => 'B', 'transaction_type' => 'bonus']);
        Assert::true($this->repo->getTotalWithdrawals() >= 55.0);
        Assert::true($this->repo->getTotalBonuses() >= 25.0);
    }
}

(new FundViewTest())->run();
