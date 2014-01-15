<?php
return array(

    'email'    => array(
        'value'        => '',
        'title'        => /*_wp*/('Merchant email'),
        'description'  => /*_wp*/('Your PayPal account email address'),
        'control_type' => 'input',
    ),

    'currency' => array(
        'value'        => array('USD'=>1),
        'title'        => /*_wp*/('Transaction currency'),
        'description'  => /*_wp*/('Must be acceptable at merchant settings'),
        'control_type' => waHtmlControl::GROUPBOX.' paypalPayment::availableCurrency',
    ),
    'sandbox'  => array(
        'value'        => '',
        'title'        => /*_wp*/('Sandbox'),
        'description'  => /*_wp*/('Enable for test mode'),
        'control_type' => 'checkbox',
    ),
);
