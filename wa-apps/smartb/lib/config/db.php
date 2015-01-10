<?php
return array(
    'smartb_banner' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'title' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'width' => array('int', 11, 'null' => 0, 'default' => 0),
        'height' => array('int', 11, 'null' => 0, 'default' => 0),
        'scale' => array('int', 11, 'null' => 0, 'default' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'params' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),

    'smartb_click' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'image_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'ip' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'user_agent'  => array('varchar', 255, 'null' => 0, 'default' => ''),
        'create_datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),

    'smartb_view' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'banner_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'ip' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'user_agent'  => array('varchar', 255, 'null' => 0, 'default' => ''),
        'create_datetime' => array('datetime', 'null' => 0),
        'url'  => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),


    'smartb_image' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'banner_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'upload_datetime' => array('datetime', 'null' => 0),
        'original_filename'  => array('varchar', 255, 'null' => 0, 'default' => ''),
        'ext' => array('varchar', 10, 'null' => 0, 'default' => ''),
        'url'  => array('varchar', 255, 'null' => 0, 'default' => ''),
        'alt'  => array('varchar', 1000, 'null' => 0, 'default' => ''),
        'disabled' => array('int', 11, 'null' => 0, 'default' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);