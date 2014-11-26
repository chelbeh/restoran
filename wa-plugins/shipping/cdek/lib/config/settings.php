<?php
return array(
    'login' => array(
        'value' => '',
        'title' => 'Логин api',
        'description' => 'Введите логин доступа к api. Получить его можно по запросу.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'password' => array(
        'value' => '',
        'title' => 'Пароль api',
        'description' => 'Введите пароль доступа к api. Получить его можно по запросу.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'wait_for_send' => array(
        'value' => 1,
        'title' => 'Дней до отправки',
        'description' => 'Укажите через сколько дней после оформления заказа он передаётся в службу доставки',
        'control_type' => waHtmlControl::INPUT,
    ),
    'from_method' => array(
        'value' => '',
        'title' => 'Метод отправки грузов',
        'description' => 'Выберите тот тип отправки который используется в магазине.',
        'control_type' => waHtmlControl::SELECT,
        'options' => $this->getSendMethods()
    ),
    'city' => array(
        'value' => 'Москва',
        'title' => 'Город отправления',
        'control_type' => waHtmlControl::INPUT,
    ),
    'min_weight' => array(
        'value' => 0,
        'title' => 'Минимальный вес отправления (кг)',
        'description' => 'Если вес всех товаров в заказе меньше чем указанное значение то вариант доставки будет недоступен',
        'control_type' => waHtmlControl::INPUT,
    ),
    'max_weight' => array(
        'value' => 10,
        'title' => 'Максимальный вес отправления (кг)',
        'description' => 'Если вес всех товаров в заказе больше чем указанное значение то вариант доставки будет недоступен',
        'control_type' => waHtmlControl::INPUT,
    ),
    'height' => array(
        'value' => 30,
        'title' => 'Высота',
        'description' => 'Средняя высота посылки',
        'control_type' => waHtmlControl::INPUT,
    ),
    'width' => array(
        'value' => 30,
        'title' => 'Ширина',
        'description' => 'Средняя ширина посылки',
        'control_type' => waHtmlControl::INPUT,
    ),
    'length' => array(
        'value' => 30,
        'title' => 'Длина',
        'description' => 'Средняя длина посылки',
        'control_type' => waHtmlControl::INPUT,
    ),
    'surcharge_by_order' => array(
        'value' => 0,
        'title' => 'Надбавка (% от заказа)',
        'description' => 'Указанный процент берется от стоимости товаров в заказе.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'surcharge' => array(
        'value' => 0,
        'title' => 'Надбавка (% от доставки)',
        'description' => 'Указанный процент берется от стоимости доставки.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'tariff_list' => array(
        'value' => array('1', '3', '5', '10', '11', '12', '57', '58', '59', '60', '61', '136', '137'),
        'title' => 'Тарифы',
        'description' => 'Тарифы доступные для расчета',
        'control_type' => waHtmlControl::GROUPBOX,
        'options' => array(
            array(
                'value' => '136',
                'title' => 'Посылка склад-склад',
                'description' => '',
            ),
            array(
                'value' => '137',
                'title' => 'Посылка склад-дверь',
                'description' => '',
            ),
            array(
                'value' => '139',
                'title' => 'Посылка дверь-дверь',
                'description' => '',
            ),
            array(
                'value' => '1',
                'title' => 'Экспресс лайт дверь-дверь',
                'description' => '',
            ),
            array(
                'value' => '3',
                'title' => 'Супер-экспресс до 18',
                'description' => '',
            ),
            array(
                'value' => '5',
                'title' => 'Экономичный экспресс склад-склад',
                'description' => '',
            ),
            array(
                'value' => '10',
                'title' => 'Экспресс лайт склад-склад',
                'description' => '',
            ),
            array(
                'value' => '11',
                'title' => 'Экспресс лайт склад-дверь',
                'description' => '',
            ),
            array(
                'value' => '12',
                'title' => 'Экспресс лайт дверь-склад',
                'description' => '',
            ),
            array(
                'value' => '57',
                'title' => 'Супер-экспресс до 9',
                'description' => '',
            ),
            array(
                'value' => '58',
                'title' => 'Супер-экспресс до 10',
                'description' => '',
            ),
            array(
                'value' => '59',
                'title' => 'Супер-экспресс до 12',
                'description' => '',
            ),
            array(
                'value' => '60',
                'title' => 'Супер-экспресс до 14',
                'description' => '',
            ),
            array(
                'value' => '61',
                'title' => 'Супер-экспресс до 16',
                'description' => '',
            ),
        ),
    ),
);
