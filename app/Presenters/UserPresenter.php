<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\UserRepository;
use Nette\Application\UI\Form;

class UserPresenter extends BasePresenter
{
    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $this->template->users = $this->users->findAll();
    }

    public function renderAdd(): void
    {
        // formulář se renderuje přes komponentu
    }

    public function renderEdit(int $id): void
    {
        $user = $this->users->findById($id);
        if (!$user) {
            $this->error('Uživatel nenalezen.', 404);
        }

        $this['userForm']->setDefaults([
            'initials' => $user->initials,
            'name'     => $user->name,
        ]);
        $this->template->editedUser = $user;
    }

    public function actionDelete(int $id, string $back = 'default'): void
    {
        $user = $this->users->findById($id);
        if (!$user) {
            $this->error('Uživatel nenalezen.', 404);
        }
        $this->users->delete($id);
        $this->flashMessage('Uživatel byl deaktivován.', 'success');
        $this->redirect($back === 'edit' ? 'edit' : 'default', $back === 'edit' ? ['id' => $id] : []);
    }

    public function actionRestore(int $id, string $back = 'default'): void
    {
        $user = $this->users->findById($id);
        if (!$user) {
            $this->error('Uživatel nenalezen.', 404);
        }
        $this->users->restore($id);
        $this->flashMessage('Uživatel byl obnoven.', 'success');
        $this->redirect($back === 'edit' ? 'edit' : 'default', $back === 'edit' ? ['id' => $id] : []);
    }

    protected function createComponentUserForm(): Form
    {
        $form = new Form();
        $form->addProtection('Bezpečnostní token vypršel, odešlete formulář znovu.');

        $form->addText('initials', 'Iniciály:')
            ->setRequired('Zadejte iniciály uživatele.')
            ->setMaxLength(10)
            ->addRule(Form::Pattern, 'Iniciály smí obsahovat pouze písmena.', '[A-Za-z]+');

        $form->addText('name', 'Jméno (volitelné):')
            ->setMaxLength(100);

        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = $this->userFormSucceeded(...);

        return $form;
    }

    private function userFormSucceeded(Form $form, \stdClass $values): void
    {
        $id = (int) $this->getParameter('id');

        $data = [
            'initials' => strtoupper(trim($values->initials)),
            'name'     => $values->name !== '' ? trim($values->name) : null,
        ];

        if ($id > 0) {
            $this->users->update($id, $data);
            $this->flashMessage('Uživatel byl upraven.', 'success');
        } else {
            $this->users->insert($data);
            $this->flashMessage('Uživatel byl přidán.', 'success');
        }

        $this->redirect('default');
    }
}
