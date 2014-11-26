<?php

class cdekShipping extends waShipping
{
    private $log = '/shop/plugins/cdekShippingPlugin.log';

    public function calculate()
    {

        $params['weight'] = max(0.5, $this->getTotalWeight());

        if ($params['weight'] > $this->max_weight) {
            return 'Вес отправления превышает максимально допустимый (' . $this->max_weight . ' кг).';
        } elseif ($params['weight'] < $this->min_weight) {
            return 'Вес отправления меньше допустимого (' . $this->min_weight . ' кг).';
        } elseif (empty($params['weight'])) {
            return 'Вес отправления не задан.';
        }

        $total_price = $this->getTotalPrice();

        $incomplete = false;

        $params['from'] = $this->city;
        $params['to'] = $this->findTo($this->getAddress());
        if (empty($params['to'])) {
            $city = trim($this->getAddress('city'));
            $incomplete = empty($city);
        }

        $services = array();

        if (!empty($params['to'])) {
            if (!empty($params['from'])) {

                $height = $this->getSettings("height");
                $width = $this->getSettings("width");
                $length = $this->getSettings("length");

                $method_map = $this->map();

                foreach ($method_map[$this->getSettings("from_method")]['methods'] as $method_id => $method) {


                    if ($result = $this->price($params['to'], $params['weight'], $total_price, $method_id, $height, $width, $length)) {

                        $est_delivery = '';
                        $est_delivery .= waDateTime::format('humandate', strtotime($result['deliveryDateMin'])) . " - " . waDateTime::format('humandate', strtotime($result['deliveryDateMax']));

                        $rate = doubleval(ifset($result['price'], 0));
                        if (doubleval($this->surcharge) > 0) {
                            $rate+= $rate * (doubleval($this->surcharge) / 100.0);
                        }

                        if (doubleval($this->surcharge_by_order) > 0) {
                            $rate+= $total_price * (doubleval($this->surcharge_by_order) / 100.0);
                        }

                        $services[$method_id] = array(
                            'name' => $method['name'] . " тариф." . $result['tariffId'],
                            'rate' => $rate,
                            'currency' => 'RUB',
                            'est_delivery' => $est_delivery,
                        );
                    }
                }
                if (empty($services)) {
                    $services = 'Доставка в выбраный город невозможна';
                }
            } else {
                $services = 'Стоимость доставки не может быть рассчитана, так как в настройках способа доставки «СДЭК» не указан адрес отправителя.';
            }
        } elseif ($incomplete) {
            $services = 'Для расчета стоимости доставки укажите город доставки.';
        } else {
            $services = 'Не возможно рассчитать стоимость доставки в указанный город.';
        }

        return $services;
    }

    public function saveSettings($settings = array())
    {

        if (!isset($settings['city']) || is_null($this->findTo(array('city' => $settings['city'])))) {
            throw new waException('Указанный адрес пункта отправления не найден в списке поддерживаемых ТК СДЭК.');
        }
        if (isset($settings['surcharge'])) {
            if (strpos($settings['surcharge'], ',')) {
                $settings['surcharge'] = str_replace(',', '.', $settings['surcharge']);
            }
            $settings['surcharge'] = max(0, doubleval($settings['surcharge']));
        }
        return parent::saveSettings($settings);
    }

    private function findTo($address)
    {
        $to = null;
        if (isset($address['city'])) {
            $city_name = mb_strtoupper($address['city']);
            $map = $this->city();

            if ($city_name) {
                foreach ($map as $city) {
                    if (mb_strtoupper($city['title']) == $city_name) {
                        $to = $city['value'];
                    }
                }
            }
        }
        return $to;
    }

    public function requestedAddressFields()
    {
        return array(
            'country' => array('hidden' => true, 'value' => 'rus'),
            'region' => array('hidden' => false, 'cost' => false),
            'city' => array('hidden' => false, 'cost' => true, 'required' => true),
            'street' => array('hidden' => false, 'cost' => false),
        );
    }

    public function allowedAddress()
    {
        return array(array(
            'country' => array('rus'),
        ));
    }

    public function customFields(waOrder $order)
    {
        if (method_exists(wa()->getConfig(), "getCheckoutSettings")) {
            $checkoutConf = wa()->getConfig()->getCheckoutSettings();
        }
        $city = $this->city();

        // Если в настройках включен запрос адреса доставки на первом шаге и 
        // поле "город" уже заполнено, то стандратные поля wa-form не появятся и
        // customFields будут неработоспособны.
        $settingsAllow = (isset($checkoutConf['contactinfo']['fields']['address.shipping']) && $this->getAddress('city'));

        // или если массив городов пуст, значит произошла ошибка и customFields
        // так же будут неработоспособны.
        if ($settingsAllow || empty($city)) {
            $cf = array();
        } else {
            $cf = array(
                '1' => array(
                    'control_type' => waHtmlControl::CUSTOM,
                    'title' => 'Город',
                    'id' => 'city',
                    'description' => 'Выберите город.',
                    'callback' => array($this, 'cityCallback')
                ),
            );
        }
        return $cf;
    }

    /**
     * Возвращает html код выбора города назначения доставки
     */
    public function cityCallback($name)
    {
        $region = $this->getAddress('region') ? $this->getAddress('region') : '00';
        $this->view()->assign('city_list', $this->city($region));
        $this->view()->assign('current_city', $this->getAddress('city'));
        $this->view()->assign('name', $name);
        $this->view()->assign('id', $this->key);
        $this->view()->assign('path', $this->getPath("cdek"));
        $this->view()->assign('shipping_name', $this->getName());

        $out = $this->view()->fetch($this->path . '/templates/citySelect.html');
        return $out;
    }

    public function cityAction()
    {
        $region = waRequest::post("region", false, 'string_trim');
        echo json_encode($this->city($region));
    }

    /**
     * Возвращает список городов  которых возможна доставка
     */
    public function city($region = '00')
    {
        $file_path = $this->path . '/lib/config/data/cities.csv';

        //Кеш все равно быстрее чем каждый раз разбирать файл
        $cache = new waSerializeCache(__CLASS__ . __FUNCTION__ . $region, 86400, 'webasyst');

        if (!($locations = $cache->get())) {
            $data = file($file_path);
            unset($data[0]);


            if ($data) {
                foreach ($data as $row) {
                    $city_arr = explode(";", $row);
                    if ($region != "00" && $city_arr[3] == $region) {
                        $locations[] = array(
                            'title' => $city_arr[2],
                            'value' => $city_arr[0],
                        );
                    } elseif ($region == "00") {
                        $locations[] = array(
                            'title' => $city_arr[2],
                            'value' => $city_arr[0],
                        );
                    }
                }
            } else {
                waLog::log("Не удалось получить список городов", $this->log);
            }

            if (!empty($locations)) {
                usort($locations, array($this, "sortArray"));
                $cache->set($locations);
            }
        }

        return $locations;
    }

    public function sortArray($a, $b)
    {
        return strcmp($a["title"], $b["title"]);
    }

    /**
     * Возвращает результаты расчета стоимости доставки
     */
    public function price($to_city, $weight, $pub_price, $to_method, $height = 0, $width = 0, $length = 0)
    {
        $url = 'http://api.cdek.ru/calculator/calculate_price_by_json.php';

        $data = array();
        $data['version'] = '1.0';
        //дата планируемой доставки, если не установлено, берётся сегодняшний день
        $data['dateExecute'] = date('Y-m-d', (time() + $this->getSettings("wait_for_send")));

        //Используется, если введен, для расчета с учетом персональных скидок.
        if ($this->getSettings("login") && $this->getSettings("password")) {
            $data['authLogin'] = $this->getSettings("login");
            $data['secure'] = md5($data['dateExecute'] . '&' . $this->getSettings("password"));
        }
        $data['senderCityId'] = $this->findTo(array('city' => $this->getSettings("city")));
        $data['receiverCityId'] = $to_city;

        //Массив допустимых тарифов
        $data['tariffList'] = $this->getTariffList();
        $method_map = $this->map();

        $data['modeId'] = $method_map[$this->getSettings("from_method")]['methods'][$to_method]['mode_id'];

        $data['goods'][0]['weight'] = $weight;
        $data['goods'][0]['height'] = $height;
        $data['goods'][0]['width'] = $width;
        $data['goods'][0]['length'] = $length;

        $result = $this->request($url, $data);

        if (isset($result['error'])) {
            return false;
        }

        return $result['result'];
    }

    /**
     * Возвращает результаты запроса.
     * По возможности используется cURL если не установлен то file_get_contents
     */
    private function request($url, $params = array())
    {

        $hint = '';

        $response = false;

        if (!empty($params)) {
            $postdata = json_encode($params);
        }
        if (extension_loaded('curl') && function_exists('curl_init')) {
            $curl_error = null;
            if (!($ch = curl_init())) {
                $curl_error = 'curl init error';
            }
            if (curl_errno($ch) != 0) {
                $curl_error = 'curl init error: ' . curl_errno($ch);
            }
            if (!$curl_error) {

                @curl_setopt($ch, CURLOPT_URL, $url);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                @curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json')
                );
                @curl_setopt($ch, CURLOPT_TIMEOUT, 10);


                if (isset($postdata)) {
                    @curl_setopt($ch, CURLOPT_POST, 1);
                    @curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                }
                $response = @curl_exec($ch);

                if (curl_errno($ch) != 0) {
                    $curl_error = 'curl error: ' . curl_errno($ch);
                }
                curl_close($ch);
            } else {
                throw new waException($curl_error);
            }
        } else {
            $hint .= " PHP extension curl are not loaded;";
            if (!ini_get('allow_url_fopen')) {
                $hint .= " PHP ini option 'allow_url_fopen' are disabled;";
            } else {
                $postdata = http_build_query($params);
                $context = stream_context_create(array('http' =>
                    array(
                        'method' => 'POST',
                        'content' => $postdata
                    )
                ));
                $response = file_get_contents($url, false, $context);
            }
        }

        $arr = json_decode($response, 1);

        return $arr;
    }

    /**
     * Возвращает список возможных способов передачи груза
     * используется в settings.php
     */
    public function getSendMethods()
    {
        $methods = array();
        foreach ($this->map() as $key => $value) {
            $methods[$key] = $value['name'];
        }
        return $methods;
    }


    //Временно отключенный метод
    /*
    public function checkAvailableMethod($method)
    {
        $tariffList = $this->getTariffList();

        foreach ($tariffList as $tariff) {
            if ($tariff['id'] == $method) {
                return true;
            }
        }
        return false;
    }
    */

    public function getTariffList()
    {
        $tariffs = $this->getSettings('tariff_list');
        $tariff_list = array();
        $i = 1;

        foreach (array_keys($tariffs) as $tarif) {
            $tariff_list[] = array('id' => $tarif, 'priority' => $i);
            $i++;
        }
        return $tariff_list;
    }

    public function tracking($tracking_id = null)
    {
        $url = "http://www.edostavka.ru/nakladnoy/?RegistrNum=" . urlencode($tracking_id);
        return 'Отслеживание отправления: <a href="' . $url . '" target="_blank">' . $url . '</a>';
    }

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    private function view()
    {
        static $view;
        if (!$view) {
            $view = wa()->getView();
        }
        return $view;
    }

    public function map()
    {
        $config_path = $this->path . '/lib/config/map.php';
        if (file_exists($config_path)) {
            $map = include($config_path);
        }
        return $map;
    }

}