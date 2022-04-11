<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use Selective\BasePath\BasePathMiddleware;
use App\Factory\DbConnection;
use App\Domain\Service\Helpper;
use App\Domain\Api\Rtb\RtbRequestTemplate;
use App\Domain\Api\Rtb\RtbMessage;
use App\Factory\LoggerFactory;

return [
    'settings' => function () {
        return require __DIR__ . '/../settings.php';
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);
        $settings = $container->get('settings')['error'];

        return new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details']
        );
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    },

    // The logger factory
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings')['logger']);
    },

    // Database connection
    DbConnection::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['db'];
        return new DbConnection( $settings['host'], $settings['username'], $settings['password'], $settings['database'] );
    },

    Helpper::class => function(ContainerInterface $container) {
        $settings = $container->get('settings')['helpper'];
        return new Helpper($settings);
    },

    RtbRequestTemplate::class => function(ContainerInterface $container) {
        $settings = $container->get('settings')['helpper'];
        $helpper = new Helpper($settings);
        return new RtbRequestTemplate( $helpper );
    },

    RtbMessage::class => function(ContainerInterface $container) {
        $settings = $container->get('settings')['helpper'];
        $helpper = new Helpper($settings);
        return new RtbMessage($helpper);
    }


];