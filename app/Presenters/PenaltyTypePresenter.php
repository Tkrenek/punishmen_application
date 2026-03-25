<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\PenaltyTypeRepository;
use Nette\Application\UI\Form;

class PenaltyTypePresenter extends BasePresenter
{
    public function __construct(private readonly PenaltyTypeRepository $penaltyTypes)
    {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $this->template->penaltyTypes = $this->penaltyTypes->findAll();
    }

    public function renderEdit(int $id): void
    {
        $type = $this->penaltyTypes->findById($id);
        if (!$type) {
            $this->error('Typ pokuty nenalezen.', 404);
        }
        $this['penaltyTypeForm']->setDefaults([
            'name'           => $type->name,
            'default_amount' => $type->default_amount,
        ]);
        $this->template->penaltyType = $type;
    }

    public function actionDelete(int $id): void
    {
        $type = $this->penaltyTypes->findById($id);
        if (!$type) {
            $this->error('Typ pokuty nenalezen.', 404);
        }
        $this->penaltyTypes->delete($id);
        $this->flashMessage('Typ pokuty byl deaktivován.', 'success');
        $this->redirect('default');
    }

    protected function createComponentPenaltyTypeForm(): Form
    {
        $form = new Form();
        $form->addProtection('Bezpečnostní token vypršel, odešlete formulář znovu.');

        $form->addText('name', 'Název:')
            ->setRequired('Zadejte název typu pokuty.')
            ->setMaxLength(200);

        $form->addText('default_amount', 'Výchozí částka (Kč):')
            ->setRequired('Zadejte výchozí částku.')
            ->setDefaultValue('20')
            ->addRule(Form::Float, 'Částka musí být číslo.')
            ->addRule(Form::Min, 'Částka musí být kladná.', 0.01);

        $form->addSubmit('save', 'Uložit');
        $form->onSuccess[] = $this->penaltyTypeFormSucceeded(...);

        return $form;
    }

    private function penaltyTypeFormSucceeded(Form $form, \stdClass $values): void
    {
        $id = (int) $this->getParameter('id');
        $data = [
            'name'           => trim($values->name),
            'default_amount' => (float) $values->default_amount,
        ];

        if ($id > 0) {
            $this->penaltyTypes->update($id, $data);
            $this->flashMessage('Typ pokuty byl upraven.', 'success');
        } else {
            $this->penaltyTypes->insert($data);
            $this->flashMessage('Typ pokuty byl přidán.', 'success');
        }

        $this->redirect('default');
    }
}
