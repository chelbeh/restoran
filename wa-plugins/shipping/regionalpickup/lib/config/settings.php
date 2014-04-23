<?php
/**
 * 
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.2
 */
return array(
    'rate'     => array(
        'value' => array(
        ),
    ),
    'rate_zone' => array(
        'value' => array(
            'country' => '',
            'region' => ''
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
    ),
    'prompt_address' =>array(
        'value'        => false,
        'title'        => /*_wp*/('Prompt for address'),
        'description'  => /*_wp*/('Request customer to fill in all address fields in case shipping address was not provided yet'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
