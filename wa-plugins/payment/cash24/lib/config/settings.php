<?php

return array(
    'payment_system'         => array(
        'value'        => '',
        'title'        => 'Обработчик',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
		 'options'      => array ( 
		 array('title' => 'Универсальная интернет-касса Cash24',						'value' => ''),
			array('title' => 'Яндекс деньги (Cash24)',						'value' => 'yandex'),
			array('title' => 'АльфаБанк, AlfaClick (Cash24)',						'value' => 'alfa-click'),
			array('title' => 'Со счета мобильного Билайн (Cash24)',					'value' => 'card-beeline'),
			array('title' => 'Банковская карта (Cash24)',					'value' => 'card'),
			array('title' => 'Со счета мобильного Мегафон (Cash24)',		'value' => 'card-megafon'),
			array('title' => 'QIWI (Cash24)',		'value' => 'qiwi'),
			array('title' => 'Webmoney, WME (Cash24)',	'value' => 'wme'),
			array('title' => 'Webmoney, WMR (Cash24)',				'value' => 'wmr'),
			array('title' => 'Webmoney, WMU (Cash24)',					'value' => 'wmu'),
			array('title' => 'Webmoney, WMZ (Cash24)',					'value' => 'wmz'),
			),
    ),
    'merchant'   => array(
        'value'        => '',
        'title'        => 'Merchant Id',
        'description'  => 'Идентификатор магазина в системе Cash24',
        'control_type' => waHtmlControl::INPUT,
    ),

    'secret_key'   => array(
        'value'        => '',
        'title'        => 'Secret Key',
        'description'  => 'Секретный ключ.',
        'control_type' => waHtmlControl::INPUT,
    ),
	
    'command_key'   => array(
        'value'        => '',
        'title'        => 'Command Key',
        'description'  => 'Командный ключ.',
        'control_type' => waHtmlControl::INPUT,
    ),
	 'test_mode'         => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
