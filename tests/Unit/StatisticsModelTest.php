<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\StatisticsModel;
use App\Model\UserRepository;
use App\Model\PenaltyTypeRepository;
use App\Model\PenaltyRepository;
use App\Model\FundTransactionRepository;
use Tester\Assert;

class StatisticsModelTest extends \Tests\DbTestCase
{
    private StatisticsModel $model;
    private UserRepository $users;
    private PenaltyTypeRepository $types;
    private PenaltyRepository $penalties;
    private FundTransactionRepository $fund;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model    = $this->container->getByType(StatisticsModel::class);
        $this->users    = $this->container->getByType(UserRepository::class);
        $this->types    = $this->container->getByType(PenaltyTypeRepository::class);
        $this->penalties = $this->container->getByType(PenaltyRepository::class);
        $this->fund     = $this->container->getByType(FundTransactionRepository::class);
    }

    private function mkUser(string $initials): int
    {
        return (int) $this->users->insert(['initials' => $initials, 'is_active' => 1])->id;
    }

    private function mkType(string $name): int
    {
        return (int) $this->types->insert(['name' => $name, 'default_amount' => 20.0, 'is_active' => 1])->id;
    }

    private function mkPenalty(int $uid, int $tid, float $amount, bool $paid): int
    {
        return (int) $this->penalties->insert([
            'user_id'         => $uid,
            'penalty_type_id' => $tid,
            'amount'          => $amount,
            'penalty_date'    => '2025-03-01',
            'is_paid'         => $paid ? 1 : 0,
        ])->id;
    }

    public function testGetTotalBalance_paidPlusBonusesMinusWithdrawals(): void
    {
        $uid = $this->mkUser('SM1');
        $tid = $this->mkType('StatTypeA');
        $this->mkPenalty($uid, $tid, 100.0, true);
        $this->mkPenalty($uid, $tid, 50.0,  false);
        $this->fund->insert(['user_id' => null, 'entry_date' => '2025-03-01', 'amount' => 30.0, 'description' => 'b', 'transaction_type' => 'bonus']);
        $this->fund->insert(['user_id' => null, 'entry_date' => '2025-03-01', 'amount' => 20.0, 'description' => 'w', 'transaction_type' => 'withdrawal']);
        $balance = $this->model->getTotalBalance();
        Assert::true($balance >= 110.0);
    }

    public function testGetPenaltySummary_countsCorrectly(): void
    {
        $uid = $this->mkUser('SM2');
        $tid = $this->mkType('StatTypeB');
        $this->mkPenalty($uid, $tid, 20.0, false);
        $this->mkPenalty($uid, $tid, 20.0, false);
        $this->mkPenalty($uid, $tid, 20.0, true);
        $summary = $this->model->getPenaltySummary();
        Assert::true($summary['total'] >= 3);
        Assert::true($summary['unpaid'] >= 2);
        Assert::true($summary['unpaid_amount'] >= 40.0);
    }

    public function testGetTopOffenders_returnsArray(): void
    {
        $uid = $this->mkUser('SM3');
        $tid = $this->mkType('StatTypeC');
        $this->mkPenalty($uid, $tid, 20.0, false);
        $offenders = $this->model->getTopOffenders(5);
        Assert::type('array', $offenders);
        Assert::true(count($offenders) >= 1);
        Assert::true(isset($offenders[0]['initials'], $offenders[0]['count']));
    }

    public function testGetUnpaidByUser_onlyUnpaid(): void
    {
        $uid = $this->mkUser('SM4');
        $tid = $this->mkType('StatTypeD');
        $this->mkPenalty($uid, $tid, 20.0, false);
        $unpaid = $this->model->getUnpaidByUser();
        Assert::type('array', $unpaid);
        $initials = array_column($unpaid, 'initials');
        Assert::contains('SM4', $initials);
    }

    public function testGetMonthlyTrend_returnsArray(): void
    {
        $result = $this->model->getMonthlyTrend(12);
        Assert::type('array', $result);
    }

    public function testGetMostCommonTypes_returnsArray(): void
    {
        $result = $this->model->getMostCommonTypes();
        Assert::type('array', $result);
    }
}

(new StatisticsModelTest())->run();