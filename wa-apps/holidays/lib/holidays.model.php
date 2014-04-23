<?php

class holidaysModel extends waModel
{
    protected $table = 'holidays';

    public function getContacts()
    {
        $sql = "SELECT DISTINCT contact_id FROM {$this->table}";
        $known = array_keys($this->query($sql)->fetchAll('contact_id'));
        $known || $known = null;

        $sql = "SELECT id, name, birthday, photo FROM wa_contact WHERE is_user > 0 OR id IN (?)";
        return $this->query($sql, array($known))->fetchAll('id');
    }

    public function getByContact($contact_id)
    {
        if (!$contact_id) {
            return array();
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE contact_id IN (?)
                ORDER BY start";
        $result = $this->query($sql, array($contact_id))->fetchAll();
        return ifempty($result, array());
    }
}
