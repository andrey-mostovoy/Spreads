<?php

namespace Spreads;

use GuzzleHttp\Client;

/**
 * Класс получения данных от биржы dsx
 * @see https://dsx.docs.apiary.io/
 * Можно 60 запросов в минуту
 *
 * @author Andrey Mostovoy
 */
class DsxSource implements SourceInterface {
    /**
     * Получение значений стоимости покупки биткоина за валюту.
     * @return array
     */
    public function getCurrentData() {
        $tickerData = $this->getTickerPair([CurrencyPairs::BITCOIN_USD, CurrencyPairs::BITCOIN_EUR]);

        return [
            CurrenciesISO4217::USD => $tickerData[CurrencyPairs::BITCOIN_USD]['sell'],
            CurrenciesISO4217::EUR => $tickerData[CurrencyPairs::BITCOIN_EUR]['sell'],
        ];
    }

    private function getTickerPair($pairs) {
        $GuzzleClient = new Client();
        $Response = $GuzzleClient->get('https://dsx.uk/mapi/ticker/' . join('-', $pairs));

        return \GuzzleHttp\json_decode($Response->getBody()->getContents(), true);
    }

    // @todo возможно vol можно получить так https://dsx.uk/mapi/trades/btceur
}
