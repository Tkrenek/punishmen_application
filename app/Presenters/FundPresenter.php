<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\FundTransactionRepository;
use App\Model\UserRepository;
use Nette\Application\UI\Form;

class FundPresenter extends BasePresenter
{
    public function __construct(
        private readonly FundTransactionRepository $fund,
        private readonly UserRepository            $users,
    ) {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $this->template->transactions     = $this->fund->findAll();
        $this->template->totalWithdrawals = $this->fund->getTotalWithdrawals();
        $this->template->totalBonuses     = $this->fund->getTotalBonuses();
    }

    public function actionDelete(int $id): void
    {
        $tx = $this->fund->findById($id);
        if (!$tx) {
            $this->error('Transakce nenalezena.', 404);
        }
        $this->fund->delete($id);
        $this->flashMessage('Transakce byla odstraněna.', 'success');
        $this->redirect('default');
    }

    protected function createComponentFundForm(): Form
    {
        $form = new Form();
        $form->addProtection('Bezpečnostní token vypršel, odešlete formulář znovu.');

        $users = $this->users->findActive()->fetchPairs('id', 'initials');

        $form->addSelect('user_id', 'Uživatel (volitelné):')
            ->setItems($users)
            ->setPrompt('-- Kolektivní transakce --');

        $form->addText('entry_date', 'Datum:')
            ->setRequired('Zadejte datum.')
            ->setHtmlType('date')
            ->setDefaultValue(date('Y-m-d'));

        $form->addText('amount', 'Částka (Kč):')
            ->setRequired('Zadejte částku.')
            ->addRule(Form::Float, 'Částka musí být číslo.')
            ->addRule(Form::Min, 'Částka musí být kladná.', 0.01);

        $form->addSelect('transaction_type', 'Typ:', [
            'withdrawal' => 'Výdaj z fondu',
            'bonus'      => 'Bonus do kasy',
        ])->setRequired('Vyberte typ transakce.');

        $form->addTextArea('description', 'Popis:', 40, 2)
            ->setRequired('Zadejte popis transakce.')
            ->setMaxLength(500);

        $form->addSubmit('save', 'Přidat transakci');
        $form->onSuccess[] = $this->fundFormSucceeded(...);

        return $form;
    }

    private function fundFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->fund->insert([
            'user_id'          => $values->user_id !== '' ? (int) $values->user_id : null,
            'entry_date'       => $values->entry_date,
            'amount'           => (float) $values->amount,
            'transaction_type' => $values->transaction_type,
            'description'      => trim($values->description),
        ]);

        $this->flashMessage('Transakce byla přidána.', 'success');
        $this->redirect('default');
    }
}
