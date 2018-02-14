<?php

namespace Spreads;

use Exception;
use Sly\NotificationPusher\ApnsPushService;
use Sly\NotificationPusher\PushManager;
use Spreads\Storage\FileStorage;

/**
 * @author Andrey Mostovoy
 */
class Spreads {
    const STORAGE_NAME = 'btc';
    const APNS_TOKEN_STORAGE_NAME = 'tokens';
    const PUSH_SEND_TIME = 'push_send_ts';

    public function calculate() {
        $Calculator = new SpreadCalculator();
        return $Calculator->process($_POST['userCashRate'] ?? []);
    }

    public function calculateWithUserData(array $userData = []) {
        $Calculator = new SpreadCalculator();
        return $Calculator->processWithUserDataOnStoredResult($this->read(), $userData['cashRate'] ?? []);
    }

    public function save($data) {
        $FileStorage = new FileStorage();
        $FileStorage->save(self::STORAGE_NAME, json_encode($data));
    }

    public function read() {
        $FileStorage = new FileStorage();
        return json_decode($FileStorage->read(self::STORAGE_NAME), true);
    }

    /**
     * Пуш уведомления при наличии рублевой разницы в 5% - 10% - 15% и более
     * Разница между чистой ценой покупки и продажи в рублях
     * @param array $data
     */
    public function sendPushIfNeeded($data) {
        // пуши выключены - выходим
        if (!PUSH_ENABLED) {
            return;
        }

        $pushMessages = [];
        foreach ($data['stock'] as $id => $stock) {
            foreach ($stock['percent'] as $currency => $percent) {
                if ($percent > MIN_PERCENT_FOR_PUSH &&
                    !($currency == CurrenciesISO4217::EUR && $id == 'binance')
                ) {
                    $pushMessages[] = sprintf(
                        '%s, %s, %d%%',
                        $stock['name'],
                        $currency,
                        $percent
                    );
                }
            }
        }

        // нечего слать - выходим
        if (!$pushMessages) {
            return;
        }

        // отправляем пуш если истек таймаут между пушами
        if (time() - $this->getLastPushTime() > PUSH_TIMEOUT) {
            echo join(PHP_EOL, $pushMessages) . PHP_EOL;

            $tokens = $this->getAPNSTokens();

            if (!$tokens) {
                return;
            }

            $messages = [
                join(PHP_EOL, $pushMessages),
            ];

            $params = [];

            $PushNotificationService = new ApnsPushService(
                APNS_CERTIFICATE[PUSH_ENV],
                APNS_PASS_PHRASE[PUSH_ENV],
                PUSH_ENV == 'DEV' ? PushManager::ENVIRONMENT_DEV : PushManager::ENVIRONMENT_PROD
            );

            $Response = $PushNotificationService->push($tokens, $messages, $params);
            $Logger = new Logger();
            $Logger->info(new Exception(var_export($Response->getParsedResponses(), true)));
            $invalidTokens = $PushNotificationService->getInvalidTokens();
            $Logger->info(new Exception('invalid tokens ' . var_export($invalidTokens, true)));
            $successfulTokens = $PushNotificationService->getSuccessfulTokens();
            $Logger->info(new Exception('success tokens ' . var_export($successfulTokens, true)));

            // обновляем дату отправки пушей
            $this->updatePushTime();
        }
    }

    /**
     * Добавляет токен.
     * @param string $token
     * @return bool
     */
    public function addAPNSToken($token) {
        $FileStorage = new FileStorage();
        for ($i = 0; $i < 3; $i ++) {
            try {
                $tokens = $this->getAPNSTokens();
                $tokens[] = $token;
                $tokens = array_unique($tokens);
                $FileStorage->save(self::APNS_TOKEN_STORAGE_NAME, json_encode($tokens));
                return true;
            } catch (Exception $Ex) {
                sleep(2);
            }
        }
        return false;
    }

    private function getAPNSTokens() {
        $FileStorage = new FileStorage();
        $raw = $FileStorage->read(self::APNS_TOKEN_STORAGE_NAME);
        if (!$raw) {
            return [];
        }
        return json_decode($raw, true);
    }

    private function getLastPushTime() {
        $FileStorage = new FileStorage();
        $lastSendPush = $FileStorage->read(self::PUSH_SEND_TIME);
        if (!$lastSendPush) {
            return 0;
        }

        return $lastSendPush;
    }

    private function updatePushTime() {
        $FileStorage = new FileStorage();
        $FileStorage->save(self::PUSH_SEND_TIME, time());
    }
}
