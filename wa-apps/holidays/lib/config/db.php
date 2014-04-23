<?php
return array(
    'holidays' => array(
        'id' => array('bigint', 20, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('bigint', 20, 'unsigned' => 1, 'null' => 0),
        'start' => array('date', 'null' => 0),
        'end' => array('date', 'null' => 0),
	'number_of_days' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'comment' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id',
            'start_end' => array('start', 'end'),
        ),
    ),
);
