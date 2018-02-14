<?php
/*
 * Крон получает текущие курсы с бирж, сохраняет, анализирует их, шлет пуш уведомление при необходимости
 */

use Spreads\Spreads;

require_once __DIR__ . '/bootstrap.php';

$startTime = time();
$endDate = date('Y-m-d H:i:00', $startTime + 60);
$endTime = strtotime($endDate);

$arguments = $argv;
array_shift($arguments);

$iterationsPerMinute = 10;

foreach ($arguments as $cronArgument) {
    list ($cmd, $val) = explode('=', $cronArgument);

    switch ($cmd) {
        case 'i':
            if ($val > 0 && $val <= 22) {
                $iterationsPerMinute = $val;
            } else {
                echo 'iteration count too big. check limits' . PHP_EOL;
            }
            break;
        default:
            echo 'No case for ' . $cmd . PHP_EOL;
    }
}

/**
 * максимум минуту живет
 */
$sleepTime = 60 / $iterationsPerMinute;

$Spreads = new Spreads();

while (time() < $endTime) {
    $result = $Spreads->calculate();
    $Spreads->save($result);
    $Spreads->sendPushIfNeeded($result);

    sleep($sleepTime);
}
