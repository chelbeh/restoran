<?php

return array(
    'name' => "Import from WebAsyst Shop-Script",
    'description' => "Allows to create recipients list from buyers and subscribers in old version of Shop-Script",
    'handlers' => array(
        //'sidebar.blocks' => 'sidebarBlocks',
        'head.blocks' => 'headBlocks',
        'recipients.form' => 'recipientsForm',
        'recipients.prepare' => 'recipientsPrepare',
    ),
    'version'=>'1.0.1',
    'vendor' => 'webasyst',
    'rights'   => false,
);
