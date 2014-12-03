<?php
return array(
    'free_shipping'  => array(
        'value'        => '10000000',
        'title'        => 'Сумма для бесплатной доставки',
        'description'  => 'Укажите сумму начиная с которой начинается бесплатная доставка',
        'control_type' => waHtmlControl::INPUT,
    ),
    'regions' => array(
        'title' => 'Регионы',
        'control_type' => waHtmlControl::CUSTOM.' '.'hermespickupShipping::settingRegionControl',
    ),
);

//EOF