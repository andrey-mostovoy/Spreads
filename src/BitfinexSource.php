<?php

namespace Spreads;

use GuzzleHttp\Client;

/**
 * Класс получения данных от биржы BITFINEX
 * @see https://docs.bitfinex.com/v1/reference#rest-public-trades
 * запросов 45 в минуту можно
 *
 * @author Andrey Mostovoy
 */
class BitfinexSource implements SourceInterface {
    /**
     * Получение значений стоимости покупки биткоина за валюту.
     * @return array
     */
    public function getCurrentData() {
        return [
            CurrenciesISO4217::USD => $this->getTickerPair(CurrencyPairs::BITCOIN_USD),
            CurrenciesISO4217::EUR => $this->getTickerPair(CurrencyPairs::BITCOIN_EUR),
        ];
    }

    /**
     * @param string $pair
     * @param string $type sell|buy
     * @return mixed
     */
    private function getTickerPair($pair, $type = 'sell') {
        $GuzzleClient = new Client();
        $Response = $GuzzleClient->get('https://api.bitfinex.com/v1/trades/' . $pair);

        $response = \GuzzleHttp\json_decode($Response->getBody()->getContents(), true);

        $sum = 0;
        $count = 0;
        foreach ($response as $item) {
            if ($item['type'] != $type) {
                continue;
            }
            $count += 1;
            $sum += $item['price'];
        }

        return round($sum / $count, 5);
    }
}
