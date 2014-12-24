<?php
return array(
    'banner_items' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'banner_id' => array('int', 11, 'null' => 0),
        'on' => array('int', 1, 'null' => 0, 'default' => 1),
        'url' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'link' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'alt' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'title' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'new_window' => array('int', 1, 'null' => 0, 'default' => 0),
        'click' => array('int', 11, 'null' => 0, 'default' => 0),
        'nofollow' => array('int', 1, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    
    'banner_banners' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'title' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'width' => array('int', 11, 'null' => 0, 'default' => 0),
        'height' => array('int', 11, 'null' => 0, 'default' => 0),
        'click' => array('int', 11, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);