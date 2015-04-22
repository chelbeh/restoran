<?php

/**
 *
 * @author Hunter
 * @name Cash24
 * @description Плагин оплаты через Cash24.
 *
 */
class cash24Payment extends waPayment implements waIPayment
{

    public function allowedCurrency()
    {
        $currency = array(
            'RUB',
            'USD',
            'EUR',
            'UAH'
        );
        return $currency;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {

        if (!extension_loaded('curl')) {
            throw new waException('Оплата выбранным способом невозможна. Не установлено Curl расширение PHP.');
        }
        $order          = waOrder::factory($order_data);
        $amount         = number_format($order_data['amount'], 2, '.', '');
        $test_mode      = (!empty($this->test_mode)) ? $this->test_mode : 0;
        $payment_system = $this->payment_system;
        $phone          = $order->contact_phone;
        $email          = $order->contact_email;
        $result_url     = $this->getRelayUrl();
        /**
         * ниже добавляем к Result Url следующие параметры:
         * @app_id - идентификатор приложения,
         * @merchant_id - идентификатор экземпляра настроек плагина оплаты
         * это нужно для того, чтобы при обработке запроса на Result Url можно было получить настройки модуля оплаты (merchant_id, secret_key, hidden_key)
         * см. тему на форуме - http://forum.webasyst.ru/viewtopic.php?id=20340
         */
        if (strpos($result_url, "?"))
            $result_url .= '&app_id=' . $this->app_id . '&merchant_id=' . $this->merchant_id;
        else
            $result_url .= '?app_id=' . $this->app_id . '&merchant_id=' . $this->merchant_id;
        $furl        = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL);
        $surl        = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, array(
            'order_id' => $order_data['order_id']
        ));
        $time        = time() + 24 * 60 * 60;
        $exptime     = gmdate('Y-m-d', $time) . 'T' . gmdate('H:i:s', $time);
        $sign        = md5('create-invoice-' . $amount . '-' . $order->currency . '-' . $email . '-Order ' . $order->id_str . '-' . $order->id . '-' . $surl . '-' . $furl . '-' . $result_url . '-' . $payment_system . '--' . $this->command_key);
        $objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><cash24/>');
        $first       = $objResponse->addChild('request');
        $second      = $objResponse->addChild('envelope');
        $objResponse->addAttribute('xmlns', 'http://api.cash24.ru/1.0/');
        $first->addAttribute('xmlns', 'http://api.cash24.ru/1.0/create-invoice/');
        $first->addChild('amount', $amount);
        $first->addChild('currency', $order->currency);
        $first->addChild('email', $email);
        $first->addChild('description', 'Order ' . $order->id_str);
        $first->addChild('order', $order->id);
        $first->addChild('success', htmlspecialchars($surl));
        $first->addChild('cancel', htmlspecialchars($furl));
        $first->addChild('callback', htmlspecialchars($result_url));
        $first->addChild('method', $payment_system);
        $first->addChild('phone', $phone);
        $first->addChild('wallet', '');
        $first->addChild('expires', $exptime);
        $second->addChild('auth', $this->merchant);
        $second->addChild('sign', $sign);

        if ($test_mode == '1') {
            $respurl = "http://api.staging.cash24.ru/1.0/";
        } else {
            $respurl = "https://api.cash24.ru/1.0/";
        }
        $headers = array(
            "Content-type: text/xml"

        );
        $curl    = curl_init();
        curl_setopt($curl, CURLOPT_URL, $respurl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $objResponse->asXML());
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $out = curl_exec($curl);
        if (!$out) {
		     self::log(
                    preg_replace('/payment$/', '', strtolower(__CLASS__)),
                    array(
                        'error' => curl_errno($curl).':'.curl_error($curl),
                    )
                );

        } else {
		    curl_close($curl);
			$url = @simplexml_load_string($out);
		}


        $turl = $url;

        $url = (string)$url->response->url; //формируем строку url

        if (!empty($turl->envelope->error)) {


            self::log($this->id, array(
                'error' => (string)$turl->envelope->text
            ));
            throw new waPaymentException('<font color="red"><b>Ошибка. Свяжитесь с администратором магазина</b></font>', 403);
        }

        if (empty($url)) {


            self::log($this->id, array(
                'error' => 'bad xml'
            ));
            throw new waPaymentException('<font color="red"><b>Ошибка. Свяжитесь с администратором магазина</b></font>', 403);
        }

        $view = wa()->getView();
        $view->assign('url', $url);
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path . '/templates/payment.html');
    }

    protected function callbackInit($request)
    {
        if (!empty($request['order'])) {
            $this->app_id      = ifset($request['app_id']);
            $this->merchant_id = ifset($request['merchant_id']);

        } else {
            self::log($this->id, array(
                'error' => 'empty required field(s)'
            ));
            throw new waPaymentException('Empty required field(s)');
        }
        return parent::callbackInit($request);
    }

    public function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);
        if (!$this->verifySign($request)) {
            self::log($this->id, array(
                'error' => 'invalid hash'
            ));
            throw new waPaymentException('Invalid hash', 403);
        }

        $app_payment_method = null;

        if ($request['status'] == '20' or $request['status'] == '40') {
            $app_payment_method = self::CALLBACK_PAYMENT;
			$transaction_data['state'] = self::STATE_CAPTURED;
        }
        if ($request['status'] == '30') {
            $transaction_data['state'] = self::STATE_CANCELED;
            $app_payment_method        = self::CALLBACK_CANCEL;
        }
        $transaction_data = $this->saveTransaction($transaction_data, $request);
        if ($app_payment_method) {
            $result = $this->execAppCallback($app_payment_method, $transaction_data);
        }
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data                = parent::formalizeData($transaction_raw_data);
        $transaction_data['native_id']   = ifset($transaction_raw_data['order']);
        $transaction_data['order_id']    = ifset($transaction_raw_data['order']);
        $transaction_data['amount']      = ifempty($transaction_raw_data['amount'], '');
        $transaction_data['currency_id'] = ifset($transaction_raw_data['currency']);
		if ($this->test_mode == '1') {
            $transaction_data['view_data'] = 'Тестовый режим';
        }
        return $transaction_data;
    }

    private function verifySign($request)
    {
        $result = false;

        $toSign = 'callback';
        $toSign .= '-' . ifset($request['status']);
        $toSign .= '-' . ifset($request['reason']);
        $toSign .= '-' . ifset($request['amount']);
        $toSign .= '-' . ifset($request['currency']);
        $toSign .= '-' . ifset($request['order']);
        $toSign .= '-' . ifset($request['url']);
        $toSign .= '-' . ifset($request['wallet']);
        $toSign .= '-' . ifset($request['method']);
        $toSign .= '-' . '';
        $toSign .= '-' . ifset($request['refund-made-amount']);
        $toSign .= '-' . ifset($request['salt']);
        $toSign .= '-' . $this->command_key;
        $sign = strtoupper(md5($toSign));

        if ($sign == ifset($request['sign']) && !empty($this->command_key)) {
            $result = true;
        }


        return $result;

    }
}
