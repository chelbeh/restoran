<?php

/**
 * !!! Never used except in obsolete code in FrontendSubscribe.
 */
class mailerFormModel extends waModel
{
    protected $table = 'mailer_form';
    
    public function getForms()
    {
        $sql = "SELECT f.id, f.name, f.list_id, l.name list_name FROM ".$this->table." f
        		LEFT JOIN mailer_subscribe_list l ON f.list_id = l.id";
        return $this->query($sql)->fetchAll();
    }
}