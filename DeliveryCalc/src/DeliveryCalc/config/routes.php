<?php

use Slim\App;

return function (App $app) {
    $app->get('/delivery[/]', \App\Action\HomeAction::class);
};