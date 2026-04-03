<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Model\UserRepository;
use App\Presenters\UserPresenter;
use Nette\Application\IPresenterFactory;
use Tester\Assert;

class UserViewTest extends \Tests\DbTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->container->getByType(UserRepository::class);
    }

    public function testUserPresenter_canBeCreated(): void
    {
        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter('User');
        Assert::type(UserPresenter::class, $presenter);
    }

    public function testUserList_containsInsertedUser(): void
    {
        $user = $this->repo->insert(['initials' => 'VU1', 'name' => 'View User', 'is_active' => 1]);
        $initials = array_map(fn($u) => $u->initials, $this->repo->findAll()->fetchAll());
        Assert::contains('VU1', $initials);
    }

    public function testAddUser_thenEditUser(): void
    {
        $user = $this->repo->insert(['initials' => 'VU2', 'name' => 'Initial Name', 'is_active' => 1]);
        $this->repo->update($user->id, ['name' => 'Edited Name']);
        Assert::same('Edited Name', $this->repo->findById($user->id)->name);
    }
}

(new UserViewTest())->run();