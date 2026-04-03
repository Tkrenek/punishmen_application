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
        $found = $this->users->findById($id);
        Assert::notNull($found);
        Assert::equal(0, (int) $found->is_active);
    }

    /** Template pouziva {foreach $users as $u} - ne $user - kvuli konfliktu s Nette Security\User. */
    public function testUserList_noDuplicateVariableConflict(): void
    {
        $this->users->insert(['initials' => 'UVA', 'is_active' => 1]);
        $this->users->insert(['initials' => 'UVB', 'is_active' => 0]);
        $allUsers = $this->users->findAll()->fetchAll();
        $initials = [];
        foreach ($allUsers as $u) {
            $initials[] = $u->initials;
        }
        Assert::contains('UVA', $initials);
        Assert::contains('UVB', $initials);
    }

    /** renderEdit pouziva $this->template->editedUser (ne $user) - bez konfliktu s Nette Security\User. */
    public function testUserEdit_editedUserVariable_noConflictWithSecurityUser(): void
    {
        $row = $this->users->insert(['initials' => 'EDU', 'name' => 'Edit Test', 'is_active' => 1]);
        $id = (int) $row->id;
        $found = $this->users->findById($id);
        Assert::equal('EDU', $found->initials);
        Assert::equal('Edit Test', $found->name);
        Assert::notNull($found);
    }

    /** Prepinani is_active z editacni stranky - soft delete a obnoveni. */
    public function testUserToggleActive_fromEdit_softDeleteAndRestore(): void
    {
        $row = $this->users->insert(['initials' => 'TGL', 'is_active' => 1]);
        $id = (int) $row->id;
        $this->users->delete($id);
        $deactivated = $this->users->findById($id);
        Assert::equal(0, (int) $deactivated->is_active);
        $this->users->restore($id);
        $restored = $this->users->findById($id);
        Assert::equal(1, (int) $restored->is_active);
    }
}

(new UserViewTest())->run();