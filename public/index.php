<?php

declare(strict_types=1);

// Absolutní cesta k adresáři aplikace
$container = require __DIR__ . '/../app/Bootstrap.php';
$container->getByType(Nette\Application\Application::class)->run();
