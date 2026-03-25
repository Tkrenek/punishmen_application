<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\BadRequestException;

class ErrorPresenter extends BasePresenter
{
    public function renderDefault(BadRequestException $exception): void
    {
        $code = $exception->getCode();
        $this->setView(in_array($code, [403, 404, 405, 410, 500], true) ? (string) $code : '4xx');
        $this->template->title = $exception->getMessage();
        $this->template->code  = $code;
    }
}
