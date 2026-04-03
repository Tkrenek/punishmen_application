<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\PenaltyRepository;
use App\Model\PenaltyTypeRepository;
use App\Model\UserRepository;
use Nette\Application\UI\Form;

class PenaltyPresenter extends BasePresenter
{
    /** @persistent */
    public int $page = 1;

    public function __construct(
        private readonly PenaltyRepository     $penalties,
        private readonly PenaltyTypeRepository $penaltyTypes,
        private readonly UserRepository        $users,
    ) {
        parent::__construct();
    }

    public function renderDefault(
        ?string $user_id = null,
        ?string $penalty_type_id = null,
        ?string $is_paid = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): void {
        $filters = [
            'user_id'         => ($user_id !== null && $user_id !== '') ? (int) $user_id : null,
            'penalty_type_id' => ($penalty_type_id !== null && $penalty_type_id !== '') ? (int) $penalty_type_id : null,
            'is_paid'         => ($is_paid !== null && $is_paid !== '') ? $is_paid : null,
            'date_from'       => ($date_from !== null && $date_from !== '') ? $date_from : null,
            'date_to'         => ($date_to !== null && $date_to !== '') ? $date_to : null,
        ];

        $query = $this->penalties->findFiltered($filters);
        $total = $query->count('*');
        $pageSize = $this->penalties->getPageSize();
        $pageCount = (int) ceil($total / $pageSize);
        $page = max(1, min($this->page, max(1, $pageCount)));

        $this->template->penalties    = $query->limit($pageSize, ($page - 1) * $pageSize);
        $this->template->users        = $this->users->findActive();
        $this->template->penaltyTypes = $this->penaltyTypes->findActive();
        $this->template->filters      = $filters;
        $this->template->page         = $page;
        $this->template->pageCount    = $pageCount;
        $this->template->total        = $total;
        $this->template->pageSize     = $pageSize;
        $this->template->totalAmount  = $this->penalties->sumFiltered($filters);
        $unpaidFilters = array_merge($filters, ['is_paid' => '0']);
        $this->template->unpaidCount  = $this->penalties->findFiltered($unpaidFilters)->count('*');
    }

    public function renderAdd(): void
    {
        $this->template->users        = $this->users->findActive();
        $this->template->penaltyTypes = $this->penaltyTypes->findActive();
    }

    public function actionMarkAllPaid(
        ?string $user_id = null,
        ?string $penalty_type_id = null,
        ?string $is_paid = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): void {
        $filters = [
            'user_id'         => ($user_id !== null && $user_id !== '') ? (int) $user_id : null,
            'penalty_type_id' => ($penalty_type_id !== null && $penalty_type_id !== '') ? (int) $penalty_type_id : null,
            'is_paid'         => ($is_paid !== null && $is_paid !== '') ? $is_paid : null,
            'date_from'       => ($date_from !== null && $date_from !== '') ? $date_from : null,
            'date_to'         => ($date_to !== null && $date_to !== '') ? $date_to : null,
        ];

        $count = $this->penalties->markAllPaidFiltered($filters);
        $this->flashMessage("Označeno jako zaplaceno: $count pokut.", 'success');
        $this->redirect('default', [
            'user_id'         => $user_id,
            'penalty_type_id' => $penalty_type_id,
            'is_paid'         => $is_paid,
            'date_from'       => $date_from,
            'date_to'         => $date_to,
        ]);
    }

    public function actionMarkPaid(
        int $id,
        ?string $user_id = null,
        ?string $penalty_type_id = null,
        ?string $is_paid = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): void
    {
        $penalty = $this->penalties->findById($id);
        if (!$penalty) {
            $this->error('Pokuta nenalezena.', 404);
        }
        $this->penalties->markAsPaid($id);
        $this->flashMessage('Pokuta označena jako zaplacená.', 'success');
        $this->redirect('default', ['user_id' => $user_id, 'penalty_type_id' => $penalty_type_id, 'is_paid' => $is_paid, 'date_from' => $date_from, 'date_to' => $date_to]);
    }

    public function actionMarkUnpaid(
        int $id,
        ?string $user_id = null,
        ?string $penalty_type_id = null,
        ?string $is_paid = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): void
    {
        $penalty = $this->penalties->findById($id);
        if (!$penalty) {
            $this->error('Pokuta nenalezena.', 404);
        }
        $this->penalties->markAsUnpaid($id);
        $this->flashMessage('Pokuta označena jako nezaplacená.', 'info');
        $this->redirect('default', ['user_id' => $user_id, 'penalty_type_id' => $penalty_type_id, 'is_paid' => $is_paid, 'date_from' => $date_from, 'date_to' => $date_to]);
    }

    public function actionDelete(int $id): void
    {
        $penalty = $this->penalties->findById($id);
        if (!$penalty) {
            $this->error('Pokuta nenalezena.', 404);
        }
        $this->penalties->delete($id);
        $this->flashMessage('Pokuta byla odstraněna.', 'success');
        $this->redirect('default');
    }

    protected function createComponentPenaltyForm(): Form
    {
        $form = new Form();
        $form->addProtection('Bezpečnostní token vypršel, odešlete formulář znovu.');

        $users = $this->users->findActive()->fetchPairs('id', 'initials');
        $types = $this->penaltyTypes->findActive()->fetchPairs('id', 'name');

        $form->addSelect('user_id', 'Uživatel:', $users)
            ->setRequired('Vyberte uživatele.')
            ->setPrompt('-- Vyberte uživatele --');

        $form->addSelect('penalty_type_id', 'Typ pokuty:', $types)
            ->setRequired('Vyberte typ pokuty.')
            ->setPrompt('-- Vyberte typ --');

        $form->addText('penalty_date', 'Datum:')
            ->setRequired('Zadejte datum pokuty.')
            ->setHtmlType('date')
            ->setDefaultValue(date('Y-m-d'));

        $form->addText('amount', 'Částka (Kč):')
            ->setRequired('Zadejte částku.')
            ->setDefaultValue('20')
            ->addRule(Form::Float, 'Částka musí být číslo.')
            ->addRule(Form::Min, 'Částka musí být kladná.', 0.01);

        $form->addCheckbox('is_paid', 'Zaplaceno');

        $form->addTextArea('note', 'Poznámka:', 40, 2)
            ->setMaxLength(500);

        $form->addSubmit('save', 'Přidat pokutu');

        $form->onSuccess[] = $this->penaltyFormSucceeded(...);

        return $form;
    }

    private function penaltyFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->penalties->insert([
            'user_id'         => (int) $values->user_id,
            'penalty_type_id' => (int) $values->penalty_type_id,
            'penalty_date'    => $values->penalty_date,
            'amount'          => (float) $values->amount,
            'is_paid'         => $values->is_paid ? 1 : 0,
            'note'            => $values->note !== '' ? trim($values->note) : null,
        ]);

        $this->flashMessage('Pokuta byla přidána.', 'success');
        $this->redirect('default');
    }
}
