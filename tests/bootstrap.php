<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
Tester\Environment::setup();

define('TESTS_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));
define('TEST_TEMP_DIR', ROOT_DIR . '/temp/tests');
@mkdir(TEST_TEMP_DIR, 0777, true);

function setupTestDatabase(): void
{
    $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS punishment_app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE punishment_app_test");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        initials VARCHAR(10) NOT NULL UNIQUE,
        name VARCHAR(100) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS penalty_types (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        default_amount DECIMAL(10,2) NOT NULL DEFAULT 20.00,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS penalties (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        penalty_type_id INT UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 20.00,
        penalty_date DATE NOT NULL,
        is_paid TINYINT(1) NOT NULL DEFAULT 0,
        note TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_tst_pen_user FOREIGN KEY (user_id) REFERENCES users(id),
        CONSTRAINT fk_tst_pen_type FOREIGN KEY (penalty_type_id) REFERENCES penalty_types(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS fund_transactions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        entry_date DATE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT NOT NULL,
        transaction_type ENUM('withdrawal','bonus') NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_tst_fund_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

setupTestDatabase();

function createTestContainer(): Nette\DI\Container
{
    static $container = null;
    if ($container === null) {
        $configurator = new Nette\Bootstrap\Configurator();
        $configurator->setDebugMode(false);
        $configurator->setTempDirectory(TEST_TEMP_DIR);
        $configurator->addConfig(ROOT_DIR . '/config/common.neon');
        $configurator->addConfig(TESTS_DIR . '/config/test.neon');
        $container = $configurator->createContainer();
    }
    return $container;
}