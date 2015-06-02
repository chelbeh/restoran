<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.1.0
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 * @package b2cpl
 */

return array(
    'api_url'       => array(
        'value'        => 'http://is.b2cpl.ru/portal/client_api.ashx',
        'title'        => 'URL API',
        'description'  => 'URL для доступа к API',
        'control_type' => waHtmlControl::INPUT
    ),
    'client_id'     => array(
        'value'        => '',
        'title'        => 'Идентификатор клиента',
        'description'  => 'Индивидуальный идентификатор для доступа к API',
        'control_type' => waHtmlControl::INPUT
    ),
    'api_key'       => array(
        'value'        => '',
        'title'        => 'Ключ API',
        'description'  => 'Ключ для доступа к API',
        'control_type' => waHtmlControl::INPUT
    ),
    'home_region'   => array(
        'value'        => '77',
        'title'        => 'Город отправки',
        'description'  => 'Город, в котором сдаются заказы на сортировочный центр',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '77' => 'Москва',
            '78' => 'Санкт-Петербург',
            '16' => 'Казань'
        ),
    ),
    'courier'       => array(
        'value'        => false,
        'title'        => 'Курьерская доставка',
        'description'  => 'Рассчитывать стоимость курьерской доставки',
        'control_type' => waHtmlControl::CHECKBOX
    ),
    'pickup'        => array(
        'value'        => true,
        'title'        => 'ПВЗ',
        'description'  => 'Рассчитывать стоимость доставки до ПВЗ',
        'control_type' => waHtmlControl::CHECKBOX
    ),
    'rus_post'      => array(
        'value'        => false,
        'title'        => 'Почта России',
        'description'  => 'Рассчитывать стоимость доставки Почтой России (через компанию Директ-Сервис)',
        'control_type' => waHtmlControl::CHECKBOX
    ),
    'handling_cost' => array(
        'value'        => 0,
        'title'        => 'Стоимость комплектации',
        'description'  => 'Дополнительная сумма, которая должна быть добавлена к результату расчета. Фиксированная сумма или проценты от расчетной стоимости доставки. Например "100" - 100 рублей, "10%" - 10 процентов',
        'control_type' => waHtmlControl::INPUT
    ),
    'handling_days' => array(
        'value'        => 0,
        'title'        => 'Срок комплектации',
        'description'  => 'Дополнительное количество дней на комплектацию заказа. Срок будет добавлен к расчетному сроку доставки. Можно указать точное значение, тогда на выходе получится дата, а также можно указать диапазон, тогда клиенту будет показан диапазон дат. Например точное значение "2", диапазон "2-3"',
        'control_type' => waHtmlControl::INPUT
    ),
    'limit_hour'    => array(
        'value'        => 18,
        'title'        => 'Час переноса отгрузки',
        'description'  => 'Час, после которого к сроку доставки прибавляется 1 день. Укажите 0, чтобы выключить эту функцию',
        'control_type' => waHtmlControl::INPUT
    )
);
