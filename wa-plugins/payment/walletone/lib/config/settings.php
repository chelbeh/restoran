<?php 

return array(
    'CONF_PAYMENT_SINGLECASH_METHOD' => array(
        'value' 		=> 'MD5',
        'title' 		=> 'Способ хэширования',
        'description' 	=> 'Способ хэширования, установленный в настройках Единой кассы.',
        'control_type'=> waHtmlControl::RADIOGROUP,
        'options' => array(
            array('value' => 'NO', 'title' =>	'Выключено'),
            array('value' => 'MD5', 'title' => 	'MD5'),
            array('value' => 'SHA1', 'title' => 'SHA1'),
        )
    ),
    'CONF_PAYMENT_SINGLECASH_ESHOPID' => array(
        'value' 		=> '',
        'title' 		=> 'Номер кошелька продавца',
        'description' 	=> 'Номер вашего аккаунта в платежной системе Единая касса, на который будет поступать оплата по заказам.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'CONF_PAYMENT_SINGLECASH_SECRET' => array(
        'value' 		=> '',
        'title' 		=> 'Секретный ключ',
        'description' 	=> 'Ваш секретный ключ в системе Единая касса, известный только вам. Необходим для проверки ответа от платежной системы Единая касса.',
        'control_type' => waHtmlControl::INPUT,
    ),
    //доступные способы оплаты
    'CONF_SINGLECASH_GATEWAY' => array(
        'value'            => array('WalletOneRUB' => true, 'WalletOneUAH' => true),
        'title'            => 'Доступные способы оплаты',
        'description'      => '',
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('walletonePayment', 'optionsGateways'),
    ),
);

