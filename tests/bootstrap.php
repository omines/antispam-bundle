<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\ErrorHandler;

require dirname(__DIR__) . '/vendor/autoload_runtime.php';

/*
 * Temporary workaround as per https://github.com/symfony/symfony/issues/53812#issuecomment-1962740145
 */
set_exception_handler([new ErrorHandler(), 'handleException']);

(new Dotenv())->loadEnv(__DIR__ . '/Fixture/.env');
