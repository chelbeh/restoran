<?php

return array(

    'unsubscribe/' => array(
        'url' => 'unsubscribe/?',
        'module' => 'frontend',
        'action' => 'unsubscribe',
        'secure' => false,
    ),

    'unsubscribe/<hash>/<email>/' => array(
        'url' => 'unsubscribe/<hash>/<email>/?',
        'module' => 'frontend',
        'action' => 'unsubscribe',
        'secure' => false,
    ),

    'unsubscribe/<hash>/' => array(
        'url' => 'unsubscribe/<hash>/?',
        'module' => 'frontend',
        'action' => 'unsubscribe',
        'secure' => false,
    ),

    'subscribe/' => 'frontend/subscribe'
);