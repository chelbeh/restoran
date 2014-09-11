<?php

class holidaysModel extends waModel
{
    protected $table = 'holidays';

    public function getContacts()
    {
        $sql = "SELECT DISTINCT contact_id FROM {$this->table}";
        $known = array_keys($this->query($sql)->fetchAll('contact_id'));
        $known || $known = null;

        $sql = "SELECT id, name, birth_day, birth_month, birth_year, photo FROM wa_contact WHERE is_user > 0 OR id IN (?)"; // !!!
        $result = array();
        foreach($this->query($sql, array($known)) as $row) {
            if (!empty($row['birth_month']) && !empty($row['birth_day'])) {
                $row['birthday'] = date('Y-m-d', mktime(0, 0, 0, $row['birth_month'], $row['birth_day'], ifset($row['birth_year'], date('Y'))));
            } else {
                $row['birthday'] = null;
            }
            $result[$row['id']] = $row;
        }
        return $result;
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
