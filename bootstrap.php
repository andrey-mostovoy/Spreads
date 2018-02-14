<?php

use Spreads\Logger;

require_once __DIR__ . '/vendor/autoload.php';

define('MIN_PERCENT_FOR_PUSH', 5);

define('STORAGE_DIR', __DIR__ . '/storage/');
define('LOG_DIR', __DIR__ . '/log/');

define('PUSH_ENABLED', true);

// таймаут в секундах (3 раза в сутки)
define('PUSH_TIMEOUT', 28800);

define('PUSH_ENV', 'PROD');
define('APNS_CERTIFICATE', [
    'DEV'  => __DIR__ . '/spreadspushdev.pem',
    'PROD' => __DIR__ . '/spreadspushprod.pem',
]);
define('APNS_PASS_PHRASE', [
    'DEV'  => null,
    'PROD' => null,
]);


register_shutdown_function('_handleShutdown');

set_exception_handler('_handleException');

set_error_handler('_handleError');

/**
 * Обработчик фатальных ошибок
 */
function _handleShutdown() {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    $Ex = new ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);

    $Logger = new Logger();
    $Logger->error($Ex);
}

/**
 * Обработчик исключений
 * @param Throwable $Ex
 */
function _handleException(Throwable $Ex) {
    $Logger = new Logger();
    $Logger->error($Ex);
}

/**
 * Обработчик ошибок
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 */
function _handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // Этот код ошибки не включен в error_reporting,
        // так что пусть обрабатываются стандартным обработчиком ошибок PHP
        return false;
    }

    $Ex = new ErrorException($errstr, $errno, 1, $errfile, $errline);

    $Logger = new Logger();
    $Logger->error($Ex);

    return true;
}
