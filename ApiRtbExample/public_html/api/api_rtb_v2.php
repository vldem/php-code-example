<?php
use DI\ContainerBuilder;
use Slim\App;

require_once __DIR__ . '/../../comps4/vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

// Set up settings
$containerBuilder->addDefinitions(__DIR__ . '/../../config/api/container.php');

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Create App instance
$app = $container->get(App::class);

// Register routes
$app->get('/api/rtb/{brokerId}/{width}/{height}/{bidfloor}[/{mode}]', \App\Action\Api\Rtb\GetAdsAction::class);

// Register middleware
(require __DIR__ . '/../../config/api/middleware.php')($app);

//$app->setBasePath('/api/rtb');

$app->run();