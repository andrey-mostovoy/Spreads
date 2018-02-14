<?php

namespace Spreads;

use Exception;

/**
 * Класс описания парсинга данных от бирж.
 * @author Andrey Mostovoy
 */
class SpreadCalculator {
    private $availableCurrencies = [
        CurrenciesISO4217::EUR,
        CurrenciesISO4217::USD,
    ];

    /**
     * Собирает данные на текущий момент с бирж, курсов валют, высчитывает разброс и собирает результат.
     * @param array $userCashRate Курс, который ввел юзер.
     * @return array
     */
    public function process(array $userCashRate = []) {
        // получаем текущий курс валюты
        $CashRbc = new CashRbcSource($userCashRate);
        $cashRate = $CashRbc->getCurrentData();

        // получаем стоимость продажи на localbitcoins
        $LocalBitcoins = new LocalbitcoinsSource();
        $localBitcoinSell = $LocalBitcoins->getBuyOnlineAveragePrice();

        // получаем стоимость покупки на биржах
        $stockExchanges = [
            'dsx' => ['name' => 'DSX'],
            'bitfinex' => ['name' => 'BITFINEX'],
            'bitstamp' => ['name' => 'Bitstamp'],
            'binance' => ['name' => 'BINANCE'],
        ];

        foreach ($stockExchanges as $stockId => &$stockData) {
            switch ($stockId) {
                case 'dsx':
                    // DSX
                    $Source = new DsxSource();
                    break;
                case 'bitfinex':
                    // BitfinexSource
                    $Source = new BitfinexSource();
                    break;
                case 'bitstamp':
                    // Bitstamp
                    $Source = new BitstampSource();
                    break;
                case 'binance':
                    // Binance
                    $Source = new BinanceSource();
                    break;
                default:
                    continue;
            }

            if (!isset($Source)) {
                continue;
            }

            try {
                $stockData['buy'] = $Source->getCurrentData();

                // по формуле посчитаем доход
                // ( RURBTC by LCBTC - 1% ) - (BTCEUR by DSX * EUR BUY + 1%) = SPREAD
                $result = $this->calculate($stockData['buy'], $localBitcoinSell, $cashRate);
                $stockData['buyAmount'] = $result[0];
                $stockData['spread'] = $result[1];
                $stockData['percent'] = $result[2];
            } catch (Exception $Ex) {
                unset($stockExchanges[$stockId]);
            }
        }

        return [
            'stock' => $stockExchanges,
            'local' => $localBitcoinSell,
            'cash' => $cashRate,
            'timestamp' => time(),
        ];
    }

    private function calculate($buy, $sell, $cash) {
        $spreads = [];
        $buyAmounts = [];
        $percents = [];
        foreach ($this->availableCurrencies as $currency) {
            // ( RURBTC by LCBTC - 1% ) - (BTCEUR by DSX * EUR BUY + 1%) = SPREAD
            $sellAmount = $sell - $this->getCommission($sell, 1);
            $buyAmount = $buy[$currency] * $cash[$currency];
            $buyAmount += $this->getCommission($buyAmount, 1);
            $buyAmounts[$currency] = $buyAmount;
            $spreads[$currency] = (int) round($sellAmount - $buyAmount);
            $percents[$currency] = (int) round($spreads[$currency] * 100 / $buyAmount);
        }

        return [$buyAmounts, $spreads, $percents];
    }

    private function getCommission($sum, $percent) {
        return $sum / 100 * $percent;
    }

    private function convertToRub($buy, $cash) {
        $result = [];
        foreach ($this->availableCurrencies as $currency) {
            $result[$currency] = $buy[$currency] * $cash[$currency];
        }
        return $result;
    }

    /**
     * Обрабатывает текущие данные с учетом данных от юзера.
     * @param array $currentData текущие данные
     * @param array $userCashRate курс валют введенные юзером.
     * @return array
     */
    public function processWithUserDataOnStoredResult(array $currentData, array $userCashRate = []) {
        // получаем текущие данные из стореджа
        foreach ($this->availableCurrencies as $currency) {
            if (isset($userCashRate[$currency])) {
                $currentData['cash'][$currency] = (float) $userCashRate[$currency];
            }
        }

        foreach ($currentData['stock'] as $stockId => &$stockData) {
            try {
                // по формуле посчитаем доход
                // ( RURBTC by LCBTC - 1% ) - (BTCEUR by DSX * EUR BUY + 1%) = SPREAD
                $result = $this->calculate($stockData['buy'], $currentData['local'], $currentData['cash']);
                $stockData['buyAmount'] = $result[0];
                $stockData['spread'] = $result[1];
                $stockData['percent'] = $result[2];
            } catch (Exception $Ex) {
                unset($currentData['stock'][$stockId]);
            }
        }

        $currentData['timestamp'] = time();

        return $currentData;
    }
}
