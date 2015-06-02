<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license Webasyst
 * @version 1.1.0
 * @license http://www.webasyst.com/terms/#eula Webasyst
 * @package b2cpl
 */

/**
 * Main plugin class
 *
 * @property-read string $api_url
 * @property-read string $client_id
 * @property-read string $api_key
 * @property-read string $home_region
 * @property-read bool $courier
 * @property-read bool $pickup
 * @property-read bool $rus_post
 * @property-read int|float|string $handling_cost
 * @property-read string $handling_days
 * @property-read int|string $limit_hour
 */
class b2cplShipping extends waShipping
{

    /**
     * @return string ISO3 currency code or array of ISO3 codes
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     * @return string Weight units or array of weight units
     */
    public function allowedWeightUnit()
    {
        return 'g';
    }

    /**
     * @return array
     */
    public function allowedAddress()
    {
        return array(
            array('country' => 'rus')
        );
    }

    /**
     * @return array
     */
    public function requestedAddressFields()
    {
        $fields = array(
            'zip'     => array('cost' => true),
            'country' => array('hidden' => true, 'value' => 'rus')
        );


        if ($this->courier || $this->rus_post) {

            $fields += array(
                'region' => array(),
                'city'   => array(),
                'street' => array()
            );
        }

        return $fields;
    }

    /**
     * @param null|string $tracking_id
     * @return string
     */
    public function tracking($tracking_id = null)
    {
        if (!$tracking_id) {
            return "";
        }

        try {
            $this->checkRequiredExtensions();
        } catch (waException $ex) {
            waLog::log($ex->getMessage(), 'b2cpl.log');

            return "";
        }

        $params = array(
            'client=' . $this->client_id,
            'key=' . $this->api_key,
            'func=status_b2c',
            'codes=' . urlencode(json_encode(array('codes' => array($tracking_id))))
        );

        $url = $this->api_url . '?' . implode('&', $params);

        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1
        ));

        $result = curl_exec($curl);
        if ($result === false) {
            waLog::log('cURL Error: ' . curl_error($curl), 'b2cpl.log');

            return "";
        }
        curl_close($curl);

        $result = json_decode(mb_convert_encoding($result, 'UTF-8', 'CP1251'), true);

        if (isset($result['flag_error']) && $result['flag_error']) {
            waLog::log('Ошибка обращения к API' . (isset($result['text_error']) ? ' ' . $result['text_error'] : ''), 'b2cpl.log');

            return "";
        }

        if (!$result['quantity']) {
            waLog::log("Невозможно обработать трекинговый номер: " . $tracking_id, 'b2cpl.log');

            return "";
        }

        // Нам нужен только первый код из массива, пакетно мы не обрабатываем все равно
        $track_info = $result['codes'][0];

        $html = "";
        if (isset($track_info['city_now'])) {
            $html .= "<p>Местонахождение отправления: <b>{$track_info['city_now']}</b></p>";
        }

        if (isset($track_info['status']) && !empty($track_info['status'])) {
            $html .= '<table class="zebra"><tr><th>Дата</th><th>Статус</th><th>Подразделение</th><th>Состояние</th></tr>';

            foreach ($track_info['status'] as $status) {

                $date_str = "";
                if (isset($status['date']) && !empty($status['date'])) {
                    $status_date = strtotime($status['date']);
                    if ($status_date) {
                        $date_str = waDateTime::format('humandate', $status_date);
                    }
                }
                $seat_str = isset($status['seat']) && !empty($status['seat']) ? $status['seat'] : '';
                $state_str = isset($status['state_do']) && !empty($status['state_do']) ? $status['state_do'] : '';
                $status_str = isset($status['status']) && !empty($status['status']) ? $status['status'] : '';

                $html .= "<tr><td>$date_str</td><td>$status_str</td><td>$seat_str</td><td>$state_str</td></tr>";
            }

            $html .= "</table";
        }

        return $html;
    }

    /**
     *
     */
    protected function calculate()
    {
        $weight = $this->getTotalWeight();
        $weight = intval($weight) > 0 ? intval($weight) : 1000;

        $cost = $this->getTotalPrice();
        $zip = $this->getAddress('zip');

        if (empty($zip)) {
            return 'Неверный почтовый индекс';
        }

        try {
            $deliveries = $this->queryB2cForDelivery($zip, $weight, $cost);
        } catch (waException $ex) {
            waLog::log($ex->getMessage(), 'b2cpl.log');

            return 'Для указанного почтового индекса нет вариантов доставки';
        }

        if (!$deliveries['flag_delivery']) {
            return 'Для указанного почтового индекса нет вариантов доставки';
        }

        $transport_days = isset($deliveries['transport_days']) && !empty($deliveries['transport_days']) ? intval($deliveries['transport_days']) : 0;
        $est_delivery = $this->getEstimatedDelivery($transport_days, $this->handling_days);

        $result = array();

        foreach ($deliveries['delivery_ways'] as $dv) {
            if (isset($dv['Код']) && ($dv['Код'] == 'курьер') && $this->courier) {
                $result[$dv['Код']] = array(
                    'name'         => $dv['Наименование'],
                    'currency'     => 'RUB',
                    'rate'         => $dv['Стоимость'] + $this->getHandlingCost($this->handling_cost, $dv['Стоимость']),
                    'est_delivery' => (!empty($est_delivery) ? $est_delivery : null)
                );
            } elseif (isset($dv['Наименование']) && ($dv['Наименование'] == 'пвз') && $this->pickup) {
                $result[$dv['Код']] = array(
                    'name'         => mb_strtoupper($dv['Код']) . " " . $dv['Адрес'],
                    'currency'     => 'RUB',
                    'rate'         => $dv['Стоимость'] + $this->getHandlingCost($this->handling_cost, $dv['Стоимость']),
                    'est_delivery' => (!empty($est_delivery) ? $est_delivery : null),
                    'description'  => $dv['Адрес'] . (isset($dv['Время работы']) && !empty($dv['Время работы']) ? ' (' . $dv['Время работы'] . ')' : ''),
                    'comment'      => $dv['Адрес'] . (isset($dv['Время работы']) && !empty($dv['Время работы']) ? ' (' . $dv['Время работы'] . ')' : '')
                );
            } elseif ($this->rus_post) {
                $result[$dv['Код']] = array(
                    'name'     => $dv['Наименование'],
                    'currency' => 'RUB',
                    'rate'     => $dv['Стоимость'] + $this->getHandlingCost($this->handling_cost, $dv['Стоимость']),
                );
            }
        }

//        var_dump($result, $deliveries);

        return $result;
    }

    /**
     * @param string $zip
     * @param string $weight
     * @param string $cost
     * @return array
     * @throws waException
     */
    private function queryB2cForDelivery($zip, $weight, $cost)
    {
        $this->checkRequiredExtensions();

        $params = array(
            'client=' . $this->client_id,
            'key=' . $this->api_key,
            'func=tarif',
            'zip=' . $zip,
            'weight=' . $weight,
            'region=' . $this->home_region
        );

        if ($this->rus_post) {
            $params[] = 'price=' . $cost;
            $params[] = 'type=+post';
        }

        $url = $this->api_url . '?' . implode('&', $params);

        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1
        ));

        $result = curl_exec($curl);
        if ($result === false) {
            throw new waException('cURL Error: ' . curl_error($curl));
        }

        curl_close($curl);

        $result = json_decode(mb_convert_encoding($result, 'UTF-8', 'CP1251'), true);

        if (isset($result['flag_error']) && $result['flag_error']) {
            throw new waException('Ошибка обращения к API' . (isset($result['text_error']) ? ' ' . $result['text_error'] : ''));
        }

        return $result;
    }

    /**
     * @throws waException
     */
    private function checkRequiredExtensions()
    {
        if (!extension_loaded('curl')) {
            throw new waException('Не найден модуль расширения CURL для PHP. Расчет стоимости доставки невозможен.');
        }

        if (!extension_loaded('mbstring')) {
            throw new waException('Не найден модуль расширения mbstring для PHP, необходимый для обработки ответа. Расчет стоимости доставки невозможен.');
        }
    }

    /**
     * @param int|float|string $cost
     * @param int|float $order_cost
     * @return float|int
     */
    private function getHandlingCost($cost, $order_cost = 0)
    {
        $cost = trim($cost);
        $cost = str_replace(',', '.', $cost);

        $percent_sign_pos = strpos($cost, '%');

        if ($percent_sign_pos === false) {
            return floatval($cost);
        }

        $cost = substr($cost, 0, $percent_sign_pos + 1);
        if (strlen($cost) < 1) {
            return 0.0;
        }

        return $order_cost * floatval($cost) / 100;
    }

    /**
     * Составляет дату или интервал дат доставки.
     *
     * Если $additional равна просто числу, то это число добавляется
     * к общему сроку доставки и возвращается в виде читаемой даты
     *
     * Если $additional содержит интервал вида "3-5", то вычисляются
     * минимальная максимальная даты и возвращается интервал дат (как
     * в плагине почты России)
     *
     * @param int $days
     * @param int|string $additional
     * @return string
     */
    private function getEstimatedDelivery($days = 0, $additional = 0)
    {
        $end = 0;

        if (strpos($additional, '-') !== false) {
            list($begin, $end) = explode('-', $additional);
        } else {
            $begin = $additional;
        }

        $begin = intval($begin);
        $end = intval($end);

        if ($end > 0) {
            if ($end < $begin) {
                list($begin, $end) = array($end, $begin);
            } elseif ($end == $begin) {
                $end = 0;
            }
        }

        $limit_hour = trim($this->limit_hour);
        $limit_hour = intval($limit_hour);

        if ($limit_hour && date('H') >= $limit_hour) {
            $days++;
        }

        $result = waDateTime::format('humandate', strtotime(sprintf("+%d day", $begin + $days)));
        if ($end > 0) {
            $result .= ' — ' . waDateTime::format('humandate', strtotime(sprintf("+%d day", $end + $days)));
        }

        return $result;
    }
}
