<?php
declare(strict_types=1);

namespace Tests;

use Nette\DI\Container;
use Nette\Database\Explorer;
use Nette\Database\Connection;

abstract class DbTestCase extends \Tester\TestCase
{
    protected Container $container;
    protected Explorer $db;
    private Connection $connection;

    public function __construct()
    {
        $this->container = createTestContainer();
        $this->connection = $this->container->getByType(Connection::class);
        $this->db = $this->container->getByType(Explorer::class);
    }

    protected function setUp(): void
    {
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            $this->connection->rollBack();
        } catch (\Throwable $e) {
            // nothing to rollback
        }
    }
}