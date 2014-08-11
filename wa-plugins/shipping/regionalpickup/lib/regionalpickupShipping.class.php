<?php
/**
 * Модуль расчета доставки в Пункты выдачи заказов с разбивкой по регионам.
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
 * License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330,
 * Boston, MA 02111-1307 USA
 *
 * @license http://www.gnu.org/licenses/lgpl.html LGPL-2.1
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @copyright (C) 2014 Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.3.1
 *
 * @property-read string $currency Валюта плагина
 * @property-read array $rate_zone Массив со страной и регионом, для которых работает плагин
 * @property-read string $rate_zone['country'] ISO3 Страна
 * @property-read string $rate_zone['region'] код региона
 * @property-read array $rate Массив с пунктами выдачи, ценами, лимитами
 * @property-read array $rate[$code] массив настроек одного ПВЗ с кодом $code. $code не может быть 0 или "0" или пустым
 * @property-read string $rate[$code]['location'] Название ПВЗ
 * @property-read string $rate[$code]['cost'] Стоимость доставки. По идее тут float, но чтобы в шаблон передавалось число с точкой в качестве разделителя, то string
 * @property-read string $rate[$code]['maxweight'] Максимальный допустимый вес заказа. Про float см. выше
 * @property-read string $rate[$code]['free'] Пороговое значение стоимости заказа, выше которого доставка бесплатна. Про float см. выше
 *
 * @todo С версии 1.4 $rate[$code]['location'] переименовать в $rate[$code]['name']. В location будет location :) https://github.com/SergeR/regionalpickup/issues?milestone=2
 */
class regionalpickupShipping extends waShipping
{

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedAddress()
    {
        return array(array_filter($this->rate_zone));
    }

    /**
     * Единица измерения веса, используемая плагином
     *
     * @return string
     */
    public function allowedWeightUnit()
    {
        return 'kg';
    }

    /**
     * Расчет стоимости и возможности доставки заказа
     *
     * @return string|array Сообщение о недоступности ПВЗ или список ПВЗ с ценами
     */
    protected function calculate()
    {
        $address = $this->getAddress();

        if(
            !isset($address['country'])
            || $address['country'] !== $this->rate_zone['country']
            || !isset($address['region'])
            || $address['region'] !== $this->rate_zone['region']
        )
        {
            return _w('No suitable pick-up points');
        }

        $weight = $this->getTotalWeight();
        $cost = $this->getTotalPrice();

        $deliveries = array();

        foreach ($this->rate as $code => $rate) {
            if($this->isAllowedWeight($rate, $weight)) {
                /** @todo для ясности можно и отдельный метод сделать, который будет выдавать нужный формат массива */
                $deliveries[$code] = array(
                    'name' => $rate['location'],
                    'currency' => $this->currency,
                    'rate' => $this->calcCost($rate, $cost),
                    'est_delivery' => ''
                );
            }
        }

        return empty($deliveries) ? _w('No suitable pick-up points') : $deliveries;
    }

    public function getSettingsHTML($params = array())
    {
        $values = $this->getSettings();

        if (!empty($params['value'])) {
            $values = array_merge($values, $params['value']);
        }

        $namespace = '';
        if($params['namespace']) {
            $namespace = is_array($params['namespace']) ? '[' . implode('][', $params['namespace']) . ']' : $params['namespace'];
        }

        $view = wa()->getView();

        $autoescape = $view->autoescape();
        $view->autoescape(TRUE);

        $view->assign(array(
                'namespace' => $namespace,
                'values' => $values,
                'p' => $this
            ));

        $html = $view->fetch($this->path . '/templates/settings.html');

        $view->autoescape($autoescape);

        return $html . parent::getSettingsHTML($params);
    }

    public function requestedAddressFields()
    {
        return FALSE;
    }

    /**
     * Несмотря на название это, видимо, валидатор сохраняемых значений
     * конфигурации. Во всяком случае то, что он возвращает сохраняется
     * в БД.
     *
     * Название ПВЗ не можеь быть пустым. Потомушта.
     *
     * @todo Проверять, чтоб страна и регион были указаны
     *
     * @param array $settings
     * @return array
     * @throws waException Если данные не прошли проверку
     */
    public function saveSettings($settings = array()) {

        foreach ($settings['rate'] as $index=>$item)
        {
            if(!isset($item['location']) || empty($item['location']))
                throw new waException(_w('Pick-up point name cannot be empty'));

            $settings['rate'][$index]['cost'] = isset($item['cost']) ? str_replace(',', '.', floatval($item['cost'])) : "0";
            $settings['rate'][$index]['maxweight'] = isset($item['maxweight']) ? str_replace(',', '.', floatval($item['maxweight'])) : "0";
            $settings['rate'][$index]['free'] = isset($item['free']) ? str_replace(',', '.', floatval($item['free'])) : "0";
        }

        return parent::saveSettings($settings);
    }

    /**
     * @param string $name
     * @return mixed
     *
    public function getSettings($name = null) {
        $settings = parent::getSettings($name);

        if (isset($settings['rate']) && is_array($settings['rate'])) {
            foreach ($settings['rate'] as $index => $item) {
                $settings['rate'][$index] = array_merge(
                        array('code' => $index, 'free' => '0.0', 'maxweight' => '0.0', 'cost' => '0.0'), (array) $item
                );
            }
        }

        return $settings;
    }
     */

    /**
     * Проверяет есть-ли у варианта ограничение по максимальному весу
     * и, если есть, разрешен-ли указанный вес для этого варианта
     *
     * @param array $rate массив с настройками варианта
     * @param float $weight вес заказа
     * @return boolean
     */
    private function isAllowedWeight($rate, $weight)
    {
        return (!$rate['maxweight'] || $weight <= $rate['maxweight']);
    }

    /**
     * Расчет стоимости доставки указанного варианта с учетом возможного
     * бесплатного порога. Если бесплатный порог не указан, пуст или равен 0
     * то возвращаем стоимость доставки. Иначе 0
     *
     * @param array $rate Настройки варианта
     * @param float $orderCost стоиомсть заказа
     * @return int|float стоимость доставки
     */
    private function calcCost($rate, $orderCost)
    {
        return (!$rate['free'] || $orderCost < $rate['free']) ? $rate['cost'] : 0;
    }
}
