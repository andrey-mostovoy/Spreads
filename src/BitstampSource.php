<?php

namespace Spreads;

use GuzzleHttp\Client;

/**
 * Класс получения данных от биржы Bitstamp
 * @see https://ru.bitstamp.net/api/
 *
 * @author Andrey Mostovoy
 */
class BitstampSource implements SourceInterface {
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
     * @return mixed
     */
    private function getTickerPair($pair) {
        $GuzzleClient = new Client();
        $Response = $GuzzleClient->get('https://www.bitstamp.net/api/v2/ticker_hour/' . $pair . '/');

        return (float) \GuzzleHttp\json_decode($Response->getBody()->getContents(), true)['last'];
    }
}
