<?php

namespace Spreads;

use GuzzleHttp\Client;

/**
 * Класс описания получения текущего курса обмена валют по мск с использованием сайта https://cash.rbc.ru/
 * @author Andrey Mostovoy
 */
class CashRbcSource {
    const SOURCE_URL = 'https://cash.rbc.ru/cash/json/cash_rates_with_volumes/?city=1&currency=%d&volume=2';

    const CURRENCY_CODE_EUR = 2;
    const CURRENCY_CODE_USD = 3;

    private $userValues = [];

    /**
     * CashRbcSource constructor.
     * @param array $userValues Возможные данные от юзера. Ключами должны быть константы из CurrenciesISO4217
     */
    public function __construct(array $userValues = []) {
        $this->userValues = $userValues;
    }

    /**
     * Если был передан ручной ввод то будет использован он.
     * @return array
     */
    public function getCurrentData() {
        $GuzzleClient = new Client();

        if (isset($this->userValues[CurrenciesISO4217::EUR])) {
            $eurValue = $this->userValues[CurrenciesISO4217::EUR];
        } else {
            $url = sprintf(self::SOURCE_URL, self::CURRENCY_CODE_EUR);

            $Response = $GuzzleClient->get($url);
            $result = \GuzzleHttp\json_decode($Response->getBody()->getContents(), true);

            $eurValue = $this->getMin($result['banks']);
        }

        if (isset($this->userValues[CurrenciesISO4217::USD])) {
            $usdValue = $this->userValues[CurrenciesISO4217::USD];
        } else {
            $url = sprintf(self::SOURCE_URL, self::CURRENCY_CODE_USD);

            $Response = $GuzzleClient->get($url);
            $result = \GuzzleHttp\json_decode($Response->getBody()->getContents(), true);

            $usdValue = $this->getMin($result['banks']);
        }

        return [
            CurrenciesISO4217::EUR => (float) $eurValue,
            CurrenciesISO4217::USD => (float) $usdValue,
        ];
    }

    private function getMin($banksData) {
        $min = PHP_INT_MAX;
        foreach ($banksData as $bank) {
            if (($bank['rate']['sell'] ?? PHP_INT_MAX) < $min) {
                $min = $bank['rate']['sell'];
            }
        }

        return $min;
    }

    private function getMax($banksData) {
        $max = 0;
        foreach ($banksData as $bank) {
            if (($bank['rate']['sell'] ?? 0) > $max) {
                $max = $bank['rate']['sell'];
            }
        }

        return $max;
    }
}
