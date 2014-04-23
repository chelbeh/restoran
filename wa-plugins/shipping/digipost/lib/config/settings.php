<?php
return array(
	'digipost_api_key'    => array(
	//	'value'        => '20',
		'title'        => 'API ключ',
		'description'  => 'API ключ необходим для работы приложения. Чтобы получить API ключ, необходимо зарегистрироваться на сайте <a href="http://digi-post.ru/user/registration">Digi-Post.ru</a>. <a href="http://digi-post.ru/user/registration">Как получить API ключ</a>.',
		'control_type' => waHtmlControl::INPUT,
	),
	
	'upload'  => array(
        'value'        => '1',
        'title'        => 'Передавать данные на сервер Digi-Post.ru',
        'description'  => 'Выгружать почтовые идентификаторы, email и телефон клиента для их уведомления о движении посылки.',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    
	'deliveries' => array(
		'value' => array('bookpost', 'valuable_bookpost', 'first_class_valuable_bookpost', 'valuable_bookpost_avia', 'registered_bookpost', 'first_class_registered_bookpost','valuable_parcel','avia_valuable_parcel','ems'),
		'title' => 'Доступные способы доставки',
		'description' => 'укажите способы доставки, которыми вы будете доставлять ваши заказы',
		'control_type' => waHtmlControl::GROUPBOX,
		'options' => array(
			array(
				'value' => 'bookpost',
				'title' => 'Простая бандероль',
				'description' => '',
			),
			array(
				'value' => 'valuable_bookpost',
				'title' => 'Ценная бандероль',
				'description' => '',
			),
			array(
				'value' => 'first_class_valuable_bookpost',
				'title' => 'Ценная бандероль 1 класс',
				'description' => '',
			),
			array(
				'value' => 'valuable_bookpost_avia',
				'title' => 'Ценная авиабандероль',
				'description' => '',
			),
			array(
				'value' => 'registered_bookpost',
				'title' => 'Заказная бандероль',
				'description' => '',
			),
			array(
				'value' => 'first_class_registered_bookpost',
				'title' => 'Заказная бандероль 1 класс',
				'description' => '',
			),
			array(
				'value' => 'valuable_parcel',
				'title' => 'Ценная посылка',
				'description' => '',
			),
			array(
				'value' => 'avia_valuable_parcel',
				'title' => 'Ценная авиапосылка',
				'description' => '',
			),
			array(
				'value' => 'ems',
				'title' => 'Курьерская доставка EMS',
				'description' => '',
			),
		),
	),

	'allowance'    => array(
		'value'        => '20',
		'title'        => 'Надбавка',
		'description'  => 'Сюда можно включить стоимость упаковки. Прибавляется к итоговой сумме доставки.',
		'control_type' => waHtmlControl::INPUT,
	),
	
	'default_weight'    => array(
		'value'        => '500',
		'title'        => 'Вес по умолчанию',
		'description'  => 'Если у товара не определен его вес, то расчет идет по этому значению.',
		'control_type' => waHtmlControl::INPUT,
	),

	'from_name' => array(
		'value'        => '',
		'title'        => 'Получатель наложенного платежа (магазин)',
		'description'  => 'Для юридического лица — полное или краткое наименование; для гражданина — ФИО полностью.',
		'control_type' => 'text',
	),

	'from_address_1' => array(
		'value'        => '',
		'title'        => 'Адрес получателя наложенного платежа (магазина), строка 1',
		'description'  => 'Почтовый адрес получателя наложенного платежа.',
		'control_type' => 'text',
	),
	'from_address_2' => array(
		'value'        => '',
		'title'        => 'Адрес получателя наложенного платежа (магазина), строка 2',
		'description'  => 'Заполните, если адрес не помещается в одну строку.',
		'control_type' => 'text',
	),
	'from_zip' => array(
		'value'        => '',
		'title'        => 'Индекс получателя наложенного платежа (магазина)',
		'description'  => 'Индекс должен состоять ровно из 6 цифр.',
		'control_type' => 'text',
	),
	'inn'    => array(
		'value'        => '',
		'title'        => 'ИНН получателя наложенного платежа (магазина)',
		'description'  => 'Заполняется только для юридических лиц. 10 цифр.',
		'control_type' => 'text',
	),
	'correspondent_account' => array(
		'value'        => '',
		'title'        => 'Кор. счет получателя наложенного платежа (магазина)',
		'description'  => 'Заполняется только для юридических лиц. 20 цифр.',
		'control_type' => 'text',
	),
	'bank_name' => array(
		'value'        => '',
		'title'        => 'Наименование банка получателя наложенного платежа (магазина)',
		'description'  => 'Заполняется только для юридических лиц.',
		'control_type' => 'text',
	),
	'current_account' => array(
		'value'        => '',
		'title'        => 'Расчетный счет получателя наложенного платежа (магазина)',
		'description'  => 'Заполняется только для юридических лиц. 20 цифр.',
		'control_type' => 'text',
	),
	'bik'    => array(
		'value'        => '',
		'title'        => 'БИК получателя наложенного платежа (магазина)',
		'description'  => 'Заполняется только для юридических лиц. 9 цифр.',
		'control_type' => 'text',
	),



);

//EOF