<?php

namespace Spreads;

use Exception;
use GuzzleHttp\Client;

/**
 * Класс получения данных от биржы localbitcoins
 * @see https://localbitcoins.net/api-docs/
 *
 * @author Andrey Mostovoy
 */
class LocalbitcoinsSource implements SourceInterface {
    public function getCurrentData() {
        try {
            $jsonResponse = $this->getTickerPairs();
        } catch (Exception $Ex) {
            // что-то пошло не так
            return [];
        }

        return [
            CurrencyPairs::BITCOIN_USD => $jsonResponse[CurrenciesISO4217::USD],
            CurrencyPairs::BITCOIN_EUR => $jsonResponse[CurrenciesISO4217::EUR],
            CurrencyPairs::BITCOIN_RUB => $jsonResponse[CurrenciesISO4217::RUB],
        ];
    }

    private function getTickerPairs() {
        $GuzzleClient = new Client();
        $Response = $GuzzleClient->get('https://localbitcoins.net/bitcoinaverage/ticker-all-currencies/');

        return \GuzzleHttp\json_decode($Response->getBody()->getContents(), true);
    }

    /**
     * Возвращает среднее арифметическое из первых выставленных трейдеров.
     * @return float
     */
    public function getBuyOnlineAveragePrice() {
        $limit = 3;
        $url = 'https://localbitcoins.net/buy-bitcoins-online/RU/russian-federation/transfers-with-specific-bank/.json';

        $GuzzleClient = new Client();
        $Response = $GuzzleClient->get($url);

        $result = \GuzzleHttp\json_decode($Response->getBody()->getContents(), true);

        $limitedResult = array_slice($result['data']['ad_list'] ?? [], 0, $limit);

        $sum = 0;
        foreach ($limitedResult as $ad) {
            $sum += $ad['data']['temp_price'] ?? 0;
        }

        return round($sum / $limit, 2);
    }
}
