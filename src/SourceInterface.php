<?php

namespace Spreads;

/**
 * @author Andrey Mostovoy
 */
interface SourceInterface {
    /**
     * Получение значений стоимости покупки биткоина за валюту.
     * @return array
     */
    public function getCurrentData();
}
