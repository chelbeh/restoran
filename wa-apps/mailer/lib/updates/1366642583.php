<?php

$tm = new mailerTemplateModel();
foreach($tm->getTemplates() as $t) {
    $count = null;
    $body = str_replace(array('%7B', '%24', '%7D'), array('{', '$', '}'), $t['body'], $count);
    if ($count > 0) {
        $tm->updateById($t['id'], array(
            'body' => $body,
        ));
    }
}

