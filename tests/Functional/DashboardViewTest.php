<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\StatisticsModel;
use App\Presenters\DashboardPresenter;
use Nette\Application\IPresenterFactory;
use Tester\Assert;

class DashboardViewTest extends \Tests\DbTestCase
{
    public function testDashboardPresenter_canBeCreated(): void
    {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter('Dashboard');
        Assert::type(DashboardPresenter::class, $presenter);
    }

    public function testStatisticsModel_providesAllDashboardData(): void
    {
        $model = $this->container->getByType(StatisticsModel::class);
        Assert::type('float', $model->getTotalBalance());
        $summary = $model->getPenaltySummary();
        Assert::true(isset($summary['total'], $summary['unpaid'], $summary['unpaid_amount']));
        Assert::type('array', $model->getTopOffenders(5));
        Assert::type('array', $model->getMonthlyTrend(12));
        Assert::type('array', $model->getMostCommonTypes());
        Assert::type('array', $model->getUnpaidByUser());
    }
}

(new DashboardViewTest())->run();