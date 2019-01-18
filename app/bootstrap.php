<?php declare(strict_types = 1);

namespace App;

use Nette\Configurator;

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Configurator();

$debugMode = $configurator->isDebugMode();
$tempDirectory = 'temp';
if (\PHP_SAPI === 'cli') {
	$debugMode = \getenv('development') === 'true';
	$tempDirectory = 'tempcli';
}

$configurator->setDebugMode($debugMode);

$configurator->setTempDirectory(__DIR__ . '/../var/' . $tempDirectory);
$configurator->enableTracy(__DIR__ . '/../var/log');

//$configurator->createRobotLoader()
//	->addDirectory(__DIR__)
//	->register();
$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

\set_error_handler(function (int $severity, string $message, string $file, int $line): void {
	if (!(\error_reporting() & $severity)) { // This error code is not included in error_reporting
		return;
	}
	throw new \ErrorException($message, 0, $severity, $file, $line);
});

$container = $configurator->createContainer();

return $container;
