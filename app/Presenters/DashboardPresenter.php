<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\StatisticsModel;

class DashboardPresenter extends BasePresenter
{
    public function __construct(private readonly StatisticsModel $statistics)
    {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $this->template->balance       = $this->statistics->getTotalBalance();
        $this->template->summary       = $this->statistics->getPenaltySummary();
        $this->template->topOffenders  = $this->statistics->getTopOffenders(5);
        $this->template->monthlyTrend  = $this->statistics->getMonthlyTrend(12);
        $this->template->commonTypes   = $this->statistics->getMostCommonTypes();
        $this->template->unpaidByUser  = $this->statistics->getUnpaidByUser();
    }
}
