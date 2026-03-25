<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Bootstrap\Configurator();

// Debug mode - v produkci nastavit na false nebo použít IP whitelist
$configurator->setDebugMode(true);
$configurator->enableTracy(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->addConfig(__DIR__ . '/../config/common.neon');

// Lokální konfigurace (gitignored - DB credentials apod.)
$localConfig = __DIR__ . '/../config/local.neon';
if (file_exists($localConfig)) {
    $configurator->addConfig($localConfig);
} else {
    throw new RuntimeException(
        'Missing config/local.neon. Copy config/local.neon.example to config/local.neon and fill in your DB credentials.'
    );
}

return $configurator->createContainer();
