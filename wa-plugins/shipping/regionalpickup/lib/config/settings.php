<?php
/**
 * 
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.3.1
 */
return array(
    'rate' => array(
            1 => array(
                'location'=>'',
                'cost'=>'0.0',
                'maxweight'=>'0.0',
                'free'=>'0.0'
            )
    ),
    'rate_zone' => array(
        'value' => array(
            'country' => 'rus',
            'region' => '77'
        ),
        'title' => 'Pick-up point country and region',
        'description' => '',
        'control_type' => waHtmlControl::CUSTOM . ' waShipping::settingRegionZoneControl',
        'items' => array(
            'country' => array(
                'value' => '',
                'description' => ''
            ),
            'region' => array(
                'value' => '',
                'description' => ''
            )
        )
    ),
    'currency' => array(
        'value' => 'RUB',
        'title'        => /*_wp*/('Currency'),
        'description'  => /*_wp*/('Currency in which shipping rate is provided'),
        'control_type' => waHtmlControl::SELECT.' waShipping::settingCurrencySelect',
    )
);
