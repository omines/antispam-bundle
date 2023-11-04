<?php

use Tests\Fixture\Kernel;

// This allows us to do the trick with the symlinked vendor folder shared with the main library
$_SERVER['APP_RUNTIME_OPTIONS'] = ['project_dir' => dirname(__DIR__)];

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function () {
    return new Kernel('dev', true);
};
