<?php

namespace Spreads;

use GuzzleHttp\Client;

/**
 * Класс получения данных от биржы BINANCE
 * @see https://github.com/binance-exchange/binance-official-api-docs/blob/master/rest-api.md
 * 1200 запросов в минуту пропускает
 *
 * @author Andrey Mostovoy
 */
class BinanceSource implements SourceInterface {
    /**
     * Получение значений стоимости покупки биткоина за валюту.
     * @return array
     */
    public function getCurrentData() {
        return [
            CurrenciesISO4217::USD => $this->getTickerPair(CurrencyPairs::BITCOIN_USD),
            CurrenciesISO4217::EUR => 0.1, // нет такой пары на бирже
        ];
    }

    /**
     * @param string $pair
     * @return mixed
     */
    private function getTickerPair($pair) {
        if ($pair == CurrencyPairs::BITCOIN_USD) {
            $pair = 'BTCUSDT';
        }
        $GuzzleClient = new Client();
        $Response = $GuzzleClient->get('https://api.binance.com/api/v3/ticker/price?symbol=' . $pair);

        return (float) \GuzzleHttp\json_decode($Response->getBody()->getContents(), true)['price'];
    }
}
