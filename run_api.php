<?php
/*
 * Обработчик запросов от приложения.
 */

use Spreads\Spreads;

require_once __DIR__ . '/bootstrap.php';

$responseData = '';

$Spreads = new Spreads();

if (isset($_POST['method'])) {
    switch ($_POST['method']) {
        case 'token_add':
            $result = $Spreads->addAPNSToken($_POST['value']);
            $responseData = [
                'result' => $result,
            ];
            break;
        case 'manualCashRate':
            $responseData = $Spreads->calculateWithUserData($_POST);
            break;
        default:
            $responseData = [
                'error' => 'unknown method provided',
            ];
    }
} else {
    $responseData = $Spreads->read();
}

header('Content-Type: application/json');
echo json_encode($responseData);
