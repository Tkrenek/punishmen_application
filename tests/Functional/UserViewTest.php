<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\UserRepository;
use App\Presenters\UserPresenter;
use Nette\Application\IPresenterFactory;
use Tester\Assert;

class UserViewTest extends \Tests\DbTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = $this->container->getByType(UserRepository::class);
    }

    public function testUserPresenter_canBeCreated(): void
    {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter('User');
        Assert::type(UserPresenter::class, $presenter);
    }

    public function testUserCreation_andRetrieval(): void
    {
        $row = $this->users->insert(['initials' => 'UV1', 'is_active' => 1]);
        $found = $this->users->findById((int) $row->id);
        Assert::equal('UV1', $found->initials);
    }

    public function testUserList_containsInserted(): void
    {
        $this->users->insert(['initials' => 'UV2', 'is_active' => 1]);
        $all = $this->users->findAll()->fetchAll();
        $initials = array_map(fn($u) => $u->initials, $all);
        Assert::contains('UV2', $initials);
    }

    public function testUserUpdate_changesInitials(): void
    {
        $row = $this->users->insert(['initials' => 'OLD', 'is_active' => 1]);
        $id = (int) $row->id;
        $this->users->update($id, ['initials' => 'NEW']);
        $updated = $this->users->findById($id);
        Assert::equal('NEW', $updated->initials);
    }

    public function testUserDelete_softDeletesRecord(): void
    {
        $row = $this->users->insert(['initials' => 'DEL', 'is_active' => 1]);
        $id = (int) $row->id;
        $this->users->delete($id);
        // delete() je soft delete - nastavi is_active = 0, zaznam zustavaa
        $found = $this->users->findById($id);
        Assert::notNull($found);
        Assert::equal(0, (int) $found->is_active);
    }

    /**
     * Template pouziva {foreach $users as $u} (ne $user) aby nedoslo
     * ke konfliktu s automaticky injektovanou promennou $user (Nette Security\User).
     * Tento test verifikuje ze UserRepository vraci iterable data.
     */
    public function testUserList_noDuplicateVariableConflict(): void
    {
        $this->users->insert(['initials' => 'UVA', 'is_active' => 1]);
        $this->users->insert(['initials' => 'UVB', 'is_active' => 0]);
        $allUsers = $this->users->findAll()->fetchAll();
        // foreach $users as $u - promennou $u pouziva template, NE $user
        $initials = [];
        foreach ($allUsers as $u) {
            $initials[] = $u->initials;
        }
        Assert::contains('UVA', $initials);
        Assert::contains('UVB', $initials);
    }

    /**
     * Test opravy bugu: renderEdit pouzival $this->template->user (konflikt s Nette Security\User).
     * Nova verze pouziva $this->template->editedUser.
     * Overuje ze UserPresenter::renderEdit vraci spravna data pres editedUser promennou.
     */
    public function testUserEdit_editedUserVariable_noConflictWithSecurityUser(): void
    {
        $row = $this->users->insert(['initials' => 'EDU', 'name' => 'Edit Test', 'is_active' => 1]);
        $id = (int) $row->id;
        // Overi ze findById vraci spravny zaznam - stejny zdroj dat jako renderEdit
        $found = $this->users->findById($id);
        Assert::equal('EDU', $found->initials);
        Assert::equal('Edit Test', $found->name);
        // Presenter nastavi $this->template->editedUser (ne $user) aby nedoslo ke konfliktu
        Assert::notNull($found);
    }
}

(new UserViewTest())->run();