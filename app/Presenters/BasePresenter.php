<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    /** @persistent */
    public string $locale = 'cs';

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->appName = 'Pokutovník';
    }
}
