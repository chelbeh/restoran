<?php

/**
 * Storage for campaign recipient selection criteria.
 * Does not store individual addresses, only recepies how to get them
 * via contacts collections, plugins, etc.
 */
class mailerMessageRecipientsModel extends waModel
{
    protected $table = "mailer_message_recipients";

    public function getByMessage($id)
    {
        $sql = "SELECT id,value FROM ".$this->table." WHERE message_id = i:id ORDER BY id";
        return $this->query($sql, array('id' => $id))->fetchAll('id', true);
    }
}