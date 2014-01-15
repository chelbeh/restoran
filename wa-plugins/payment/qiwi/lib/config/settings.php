<?php
return array(
    'protocol'       => array(
        'value'        => 'soap',
        'title'        => 'Способ подключения',
        'control_type' => 'select',
        'options'      => array(
            'soap' => 'SOAP-протокол',
            'rest' => 'Новый протокол',
        ),
    ),
    'login'          => array(
        'value'        => '',
        'title'        => 'Идентификатор (логин)',
        'description'  => '',
        'control_type' => 'input',
    ),
    'password'       => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => '',
        'control_type' => 'password',
    ),
    'api_password'   => array(
        'value'        => '',
        'title'        => 'QIWI PULL API',
        'description'  => '',
        'control_type' => 'password',
    ),
    'prv_name'       => array(
        'value'        => '',
        'title'        => 'Продавец',
        'description'  => 'Название провайдера, которое будет показано клиенту (произвольная строка до 100 символов)',
        'control_type' => 'text',
    ),
    'lifetime'       => array(
        'value'        => 24,
        'title'        => 'Время жизни счета',
        'description'  => 'Укажите срок оплаты счета в часах.',
        'control_type' => 'input',
    ),
    'alarm'          => array(
        'value'        => 0,
        'title'        => 'Уведомления',
        'description'  => 'Способ уведомления покупателя о состоянии счета в системе QIWI',
        'control_type' => 'select qiwiPayment::_getAlarmVariants',
    ),
    'prefix'         => array(
        'value'        => '',
        'title'        => 'Префикс счета',
        'description'  => 'Введите префикс номера счета в системе QIWI с использованием цифр и латинских букв.',
        'control_type' => 'input',
    ),
    'customer_phone' => array(
        'value'        => 'phone',
        'title'        => 'Телефон клиента',
        'description'  => 'Выберите поле вашей формы регистрации, предназначенное для ввода телефонного номера клиента.',
        'control_type' => 'contactfield',
    ),
    'TESTMODE'       => array(
        'value'        => false,
        'title'        => 'Обрабатывать запросы без пароля',
        'description'  => 'Используйте этот режим для обработки запросов, инициированных вручную из личного кабинета QIWI.',
        'control_type' => 'checkbox',
    ),
);
