<?php

/**
*
* @name WalletOne
* @description Плагин оплаты через сервис WalletOne.
*
*/

class walletonePayment extends waPayment implements waIPayment
{
    private $order_id;

    /**
    * Генерирует HTML-код формы оплаты.
    *
    * @param array $payment_form_data - содержимое POST-запроса, полученное при отправке платежной формы
    * @param waOrder $order_data - объект, содержащий всю доступную информацию о заказе
    * @param bool $auto_submit - флаг, обозначающий, должна ли платежная форма автоматически отправить данные без участия пользователя
    *     (удобно при оформлении заказа)
    * @return string - HTML-код платежной формы
    * @throws waException
    */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {

        // Массив параметров настроек плагина, указаных в файле lib/config/settings.php
        $settings = $this->getSettings();
		
        // Стандартизированные коды ISO 4217
		$iso_4217 = self::iso_4217();

        //Секретный ключ интернет-магазина
        $key = $settings['CONF_PAYMENT_SINGLECASH_SECRET'];

        $fields = array();
        // Добавление полей формы в ассоциативный массив
        $fields["ITEM_NUMBER"] = $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'];
        $fields["WMI_MERCHANT_ID"]    = $settings['CONF_PAYMENT_SINGLECASH_ESHOPID']; 
        $fields["WMI_CURRENCY_ID"] = $iso_4217[$order_data['currency']];
        $fields["WMI_PAYMENT_AMOUNT"] = number_format($order_data['total'], 2, '.', '');
        $fields["WMI_PAYMENT_NO"]     = $order_data['id'];
        $fields["WMI_DESCRIPTION"]    = "";

        $fields["WMI_SUCCESS_URL"] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, array('order_id' => $order_data['id']));
        $fields["WMI_FAIL_URL"] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, array('order_id' => $order_data['id']));

        $fields["WMI_AUTO_ACCEPT"]    = "1";

        // Добавление методов оплаты
        $fields["WMI_PTENABLED"] = array(); 
        $pay_method = "";

        $gateways = $settings['CONF_SINGLECASH_GATEWAY'];
        foreach (self::optionsGateways() as $type => $title) {
            if (!empty($gateways[$type])) {
                $fields["WMI_PTENABLED"][] = $type;
                $pay_method .='<input type=hidden name="WMI_PTENABLED" value="'.$type.'" />';
            }
        }

        // Тип CMS, с помощью которой формируется форма оплаты
        $fields["CMS"] = "shop-script5";

        // Сортировка значений внутри полей
        foreach ($fields as $name => $val) {
            if (is_array($val)) {
                usort($val, "strcasecmp");
                $fields[$name] = $val;
		    }
		}

        // Формирование сообщения, путем объединения значений формы,
        // отсортированных по именам ключей в порядке возрастания.
        uksort($fields, "strcasecmp");
        $fieldValues = "";

        foreach ($fields as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $fieldValues .= $v;
                }
            } else {
                    $fieldValues .= $value;
			    }
            
        }

        // Формирование значения параметра WMI_SIGNATURE, путем
        // вычисления отпечатка, сформированного выше сообщения, 
        // по алгоритму MD5 и представление его в Base64
        $method = $settings['CONF_PAYMENT_SINGLECASH_METHOD'];
        if ($method == "MD5") {
            $signature = base64_encode(pack("H*", md5($fieldValues . $key))); $fields{"WMI_SIGNATURE"} = $signature;
        } elseif ($method == "SHA1") {
            $signature = base64_encode(pack("H*", sha1($fieldValues . $key))); $fields{"WMI_SIGNATURE"} = $signature;
        }

        // Формирование скрытых input-полей HTML-кода платежной формы
        $hidden_fields_html = '';
        foreach ($fields as $field => $value) {
            if ($field != "WMI_PTENABLED") {
                $hidden_fields_html .= '<input type="hidden" name="'.$field.'" value="'.$value.'" />'."\n";
			}
		}

        // Формирование HTML-кода платежной формы
        $form = '<form action="'.$this->getEndpointUrl().'" name="SingleCashForm" method="post">';
        $form .= $hidden_fields_html;
        $form .= $pay_method;

        if ($auto_submit) {
            $form .= '<h1>Для оплаты перейдите на сервер Единой кассы...</h1>';
        } else {
            $form .= 'Для оплаты заказа используйте кнопку ниже';
        }
        $form .= '<input type="submit" value="Перейти на сервер Единой кассы"></form>';

        // Автоматич. переход на сервер Единой Кассы
        if ($auto_submit) {
        $form .= '
            <script>
                var old_onload = window.onload;
                window.onload = function(){
                    if(old_onload) old_onload();
                    setTimeout("document.SingleCashForm.submit()",2000);
                };
            </script>';
        }

        $view = wa()->getView();
        $view->assign('form', $form);

        // Шаблон отображения платежной формы
        return $view->fetch($this->path.'/templates/payment.html');
    }

    /**
    * Возвращает ассоциативный массив стандартизированных кодов валют ISO 4217.
    * @return array
    */
    public static function iso_4217()
    {
        return array(
            'RUB' => 643,
            'USD' => 840,
            'EUR' => 978,
            'UAH' => 980,
            'BYR' => 974,
            'KZT' => 398,
        );
    }

    /**
    * Возвращает ассоциативный массив типов оплаты, предоставляемых сервисом WalletOne.
    * @return array
    */
    public static function optionsGateways()
    {
        return array(
            'WalletOneRUB'     => 'Единый кошелек RUB',
            'WalletOneUAH'     => 'Единый кошелек UAH',
            'WalletOneZAR'     => 'Единый кошелек ZAR',
            'UkashEUR'         => 'Ukash',
            'MoneyMailRUB'     => 'MoneyMail',
            'RbkMoneyRUB'      => 'RBK Money',
            'ZPaymentRUB'      => 'Z-Payment',
            'WebCredsRUB'      => 'WebCreds',
            'EasyPayBYR'       => 'EasyPay',
            'CashTerminalRUB'  => 'Платежные терминалы России',
            'CashTerminalUAH'  => 'Платежные терминалы Украины',
            'MobileRetailsRUB' => 'Салоны сотовой связи: Евросеть, Связной, МТС ...',
            'SberbankRUB'      => 'Отделения Сбербанка России',
            'PrivatbankUAH'    => 'Банки Украины: Приватбанк, Правэкс-Банк, УкрСиббанк',
            'RussianPostRUB'   => 'Отделения Почты России',
            'ContactRUB'       => 'Денежные переводы «CONTACT»',
            'UnistreamRUB'     => 'Денежные переводы «Unistream»',
            'BankTransferRUB'  => 'Банковский перевод в рублях',
            'BankTransferUAH'  => 'Банковский перевод в гривнах',
            'LiqPayRUB'        => 'Банковские карты VISA, MasterCard (LiqPay) RUB',
            'LiqPayUSD'        => 'Банковские карты VISA, MasterCard (LiqPay) USD',
            'LiqPayEUR'        => 'Банковские карты VISA, MasterCard (LiqPay) EUR',
            'LiqPayUAH'        => 'Банковские карты VISA, MasterCard (LiqPay) UAH',
            'NsmepUAH'         => 'Банковские карты НСМЭП Украины',
        );
    }

    /**
    * Инициализация плагина для обработки вызовов от платежной системы.
    *
    * @param array $request - данные запроса
    * @return waPayment
    * @throws waPaymentException
    */
    protected function callbackInit($request)
    {
        // Определяем значения соответствующих идентификаторов приложения и заказа согласно заданному шаблону
        if (isset($request['ITEM_NUMBER']) && preg_match('/^(.+)_(.+)_(.+)$/', $request['ITEM_NUMBER'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        } else {
            self::log($this->id, array('error' => 'Invalid invoice number'));
            throw new waPaymentException('Invalid invoice number');
        }

        return parent::callbackInit($request);
    }

    /**
    * Обработка вызовов платежной системы.
    *
    * Проверяются параметры запроса, и при необходимости вызывается обработчик приложения.
    * @param array $request - данные запроса, полученного от платежной системы
    * @throws waPaymentException
    * @return array - ассоциативный массив необязательных параметров результата обработки вызова:
    *     'redirect' => URL для перенаправления пользователя
    */
    protected function callbackHandler($request)
    {
        // Массив параметров настроек плагина, указаных в файле lib/config/settings.php
        $settings = $this->getSettings();

        // Метод кодирования, указанный в настройках плагина
        $method_singlecash = $settings['CONF_PAYMENT_SINGLECASH_METHOD'];

        // Определяем наличие соответствующих идентификаторов приложения и заказа
        if (!$this->order_id || !$this->app_id || !$this->merchant_id) {
            self::log($this->id, array('error' => 'Invalid order number'));
            throw new waPaymentException('Invalid order number', 404);
        }

        // Проверяем правильность цифровой подписи, полученной от платежной системы
        if (!$this->verifySign($request)) {
            self::log($this->id, array('error' => 'Invalid signature'));
            throw new waPaymentException('Invalid signature', 404);
        }

        // Приводим данные о транзакции к универсальному виду
        $transaction_data = $this->formalizeData($request);

        $result = array();
        $method = null;

        // Определяем способ обработки транзакции приложением и URL перенаправления пользователя в зависимости от статуса транзакции
        if (!empty($transaction_data['state'])) {
            switch ($transaction_data['state']) {
                case self::STATE_CAPTURED:
                    $method = self::CALLBACK_PAYMENT;
                    $result['redirect'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
                    break;
                case self::CALLBACK_DECLINE:
                    $method = self::CALLBACK_DECLINE;
                    break;
                default:
                    $result['redirect'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
            }
        }

        // Сохраняем данные транзакции в базу данных
        $transaction_data = $this->saveTransaction($transaction_data, $request);

        // Вызываем соответствующий обработчик приложения
        if ($method && $method_singlecash !='NO') {
            $result_app = $this->execAppCallback($method, $transaction_data);

            // В зависимости от успешности или неудачи обработки транзакции приложением отправляем соответствующий HTTP-заголовок.
            // Информацию  о результате обработки дополнительно пишем в лог плагина
            if (!empty($result_app['result'])) {
                self::log($this->id, array('result' => 'success'));
            } else {
                $message = !empty($result_app['error']) ? $result_app['error'] : 'wa transaction error';
                self::log($this->id, array('error' => $message));
                header("HTTP/1.0 403 Forbidden");

                echo $message;
                exit;
            }
        }

        return $result;
    }

    /**
    * Конвертирует исходные данные о транзакции, полученные от платежной системы, в формат, удобный для сохранения в базе данных.
    *
    * @param array $transaction_raw_data - данные запроса, полученного от платежной системы
    * @return array $transaction_data - форматированные данные
    */
    protected function formalizeData($transaction_raw_data)
    {
        // формируем полный список полей, относящихся к транзакциям, которые обрабатываются платежной системой WalletOne
        $fields = array(
            'WMI_MERCHANT_ID',       // Идентификатор (номер кошелька) интернет-магазина
            'WMI_PAYMENT_AMOUNT',    // Сумма заказа
            'WMI_COMMISSION_AMOUNT', // Сумма удержанной комиссии
            'WMI_CURRENCY_ID',       // Идентификатор валюты заказа (код ISO 4217)
            'WMI_TO_USER_ID',        // Двенадцатизначный номер кошелька плательщика
            'WMI_PAYMENT_NO',        // Идентификатор заказа в системе учета интернет-магазина
            'WMI_ORDER_ID',          // Идентификатор заказа в системе учета WalletOne
            'WMI_DESCRIPTION',       // Описание заказа
            'WMI_SUCCESS_URL',       // Адрес (URL) страницы интернет-магазина, на которую будет отправлен покупатель после успешной оплаты
            'WMI_FAIL_URL',          // Адрес (URL) страницы интернет-магазина, на которую будет отправлен покупатель после неуспешной оплаты
            'WMI_EXPIRED_DATE',      // Срок истечения оплаты в западно-европейском часовом поясе (UTC+0)
            'WMI_CREATE_DATE',       // Дата создания заказа в западно-европейском часовом поясе (UTC+0)
            'WMI_UPDATE_DATE',       // Дата изменения заказа в западно-европейском часовом поясе (UTC+0)
            'WMI_ORDER_STATE',       // Состояние оплаты заказа (Accepted  — заказ оплачен)
            'WMI_SIGNATURE',         // Подпись уведомления об оплате, сформированная с использованием «секретного ключа» интернет-магазина
            'WMI_AUTO_ACCEPT',       // Идентификатро автоматического зачисления платежей ( 1-зачислять автоматически, 0-требуется подтверждение интернет-магазина)
            'WMI_LAST_NOTIFY_DATE',  // Дата последней регистрации пользователя в системе WalletOne
            'WMI_NOTIFY_COUNT',      // Количество регистраций пользователя в системе WalletOne
            'WMI_PAYMENT_TYPE',      // Тип оплаты используемый в заказе в системе WalletOne
            'ITEM_NUMBER',           // Заданный в форме оплаты шаблон идентификторов приложения и заказа
            'CMS',                   // Тип используемой CMS для оплаты в системе WalletOne
        );
        foreach ($fields as $f) {
            if (!isset($transaction_raw_data[$f])) {
                $transaction_raw_data[$f] = null;
            }
        }

        // Выполняем базовую обработку данных
        $transaction_data = parent::formalizeData($transaction_raw_data);

        // Добавляем дополнительные данные:
        $transaction_data = array_merge($transaction_data, array(
            'type'        => null,
            'native_id'   => $transaction_raw_data['WMI_ORDER_ID'],
            'amount'      => $transaction_raw_data['WMI_PAYMENT_AMOUNT'],
            'currency_id' => array_search($transaction_raw_data['WMI_CURRENCY_ID'],self::iso_4217()),
            'result'      => 1,
            'order_id'    => $this->order_id,
            'view_data'   => '<b>Тип оплаты: '.strip_tags($transaction_raw_data['WMI_PAYMENT_TYPE']).'</b>',
        ));

        if (mb_strtoupper($transaction_raw_data['WMI_ORDER_STATE']) == 'ACCEPTED'||mb_strtoupper($transaction_raw_data['WMI_ORDER_STATE']) == 'PROCESSING') {
            $transaction_data['state'] = self::STATE_CAPTURED;
        } elseif (mb_strtoupper($transaction_raw_data['WMI_ORDER_STATE']) == 'REJECTED') {
            $transaction_data['state'] = self::STATE_DECLINED;
        }

        return $transaction_data;
    }

    /**
    * Проверяет правильность формирования цифровой подписи, полученной от сервиса WalletOne.
    *
    * @param array $request - данные запроса, полученного от платежной системы
    * @return bool - правда/ложь истинности подтверждения цифровой подписи
    */
    private function verifySign($request)
    {
        // формируем полный список полей, которые обрабатываются платежной системой WalletOne
        // (см. описание полей в function formalizeData($transaction_raw_data) )
        $fields = array(
            'WMI_MERCHANT_ID',
            'WMI_PAYMENT_AMOUNT',
            'WMI_COMMISSION_AMOUNT',
            'WMI_CURRENCY_ID',
            'WMI_TO_USER_ID',
            'WMI_PAYMENT_NO',
            'WMI_ORDER_ID',
            'WMI_DESCRIPTION',
            'WMI_SUCCESS_URL',
            'WMI_FAIL_URL',
            'WMI_EXPIRED_DATE',
            'WMI_CREATE_DATE',
            'WMI_UPDATE_DATE',
            'WMI_ORDER_STATE',
            'WMI_SIGNATURE',
            'WMI_AUTO_ACCEPT',
            'WMI_LAST_NOTIFY_DATE',
            'WMI_NOTIFY_COUNT',
            'WMI_PAYMENT_TYPE',
            'ITEM_NUMBER',
            'CMS',
        );
        foreach ($fields as $f) {
            if (!isset($request[$f])) {
                $request[$f] = null;
            }
        }

        // Массив параметров настроек плагина, указаных в файле lib/config/settings.php
        $settings = $this->getSettings();
        // Секретный ключ интернет-магазина
        $key = $settings['CONF_PAYMENT_SINGLECASH_SECRET'];
        // Метод формирования цифровой подписи
        $method = $settings['CONF_PAYMENT_SINGLECASH_METHOD'];

        // Возвращаем "ложь" при отсутствии ключа
        if (empty($key)) {
            return false;
        }

        // Извлечение всех параметров POST-запроса, кроме WMI_SIGNATURE
        foreach ($request as $name => $value) {
            if ($name !== "WMI_SIGNATURE" && $name !=="transaction_result") {
                $params[$name] = $value;
            }
        }

        // Сортировка массива по именам ключей в порядке возрастания
        // и формирование сообщения, путем объединения значений формы
        uksort($params, "strcasecmp"); $values = "";
        foreach ($params as $name => $value) {
            $values .=$value;
        }

        $values = rawurldecode($values);
        $values = preg_replace("/\+/"," ",$values);
        $signature = null;
        if ($method == "MD5") {
            $signature = base64_encode(pack("H*", md5($values . $key)));
        } elseif ($method == "SHA1") {
            $signature = base64_encode(pack("H*", sha1($values . $key)));
        }

        $sign = rawurldecode($request["WMI_SIGNATURE"]);
        if ($signature == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Возвращает URL запроса к платежной системе WalletOne.
    * @return string
    */
    private function getEndpointUrl()
    {
        return 'https://merchant.w1.ru/checkout/default.aspx';
    }

    /**
    * Возвращает ISO3-коды валют, поддерживаемых платежной системой,
    *
    * @see waPayment::allowedCurrency()
    * @return array
    */
    public function allowedCurrency()
    {
        return array('RUB', 'UAH', 'USD', 'EUR', 'BYR', 'KZT');
    }

}
