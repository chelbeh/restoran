<?php
return array(
    'door'  => array(
        'name'    => 'От дверей',
        'methods' => array(
            'sklad' => array(
                'name'    => 'До склада',
                'mode_id' => '2',
            ),
            'door'  => array(
                'name'    => 'До дверей',
                'mode_id' => '1',
            ),
        ),
    ),
    'sklad' => array(
        'name'    => 'От склада',
        'methods' => array(
            'sklad' => array(
                'name'      => 'До склада',
                'mode_id'   => '4',
            ),
            'door'  => array(
                'name'      => 'До дверей',
                'mode_id'   => '3',
            ),
        ),
    ),
);
