<?php

/**
 * Storage for individual subscribers.
 * By design, it is possible to subscribe to different lists.
 * In many places it is possible to give list_id=0, meaning 'all lists'.
 *
 * !!! Since there's currently no UI for managing lists and forms, `list_id` is barely used at all. 
 */
class mailerSubscriberModel extends waModel
{
    protected $table = 'mailer_subscriber';

    public function getByContact($contact_id)
    {
        $sql = "SELECT l.* FROM mailer_subscribe_list l JOIN ".$this->table." s ON l.id = s.list_id
                WHERE s.contact_id = i:contact_id";
        return $this->query($sql, array('contact_id' => $contact_id))->fetchAll();
    }

    public function add($contact_id, $list_id, $email)
    {
        return $this->insert(array(
           'contact_id' => $contact_id,
           'list_id' => $list_id,
           'datetime' => date('Y-m-d H:i:s'),
           'email' => $email,
        ), 2);
    }

    public function countListView($search)
    {
        $where_sql = '';
        $join_sql = '';
        if ($search) {
            $where_sql = "WHERE CONCAT(c.name, ' ', s.email) LIKE '%".$this->escape($search, 'like')."%'";
            $join_sql = 'JOIN wa_contact AS c ON s.contact_id=c.id';
        }

        $sql = "SELECT COUNT(*)
                FROM {$this->table} AS s
                {$join_sql}
                {$where_sql}";
        return $this->query($sql)->fetchField();
    }

    public function getListView($search, $start, $limit, $order)
    {
        // Search condition
        if ($search) {
            $where_sql = "WHERE CONCAT(c.name, ' ', s.email) LIKE '%".$this->escape($search, 'like')."%'";
            $contact_join = 'JOIN';
        } else {
            $where_sql = '';
            $contact_join = 'LEFT JOIN';
        }

        // Limit
        $limit_sql = '';
        if ($limit) {
            $limit = (int) $limit;
            if ($start) {
                $limit_sql = "LIMIT {$start}, {$limit}";
            } else {
                $limit_sql = "LIMIT {$limit}";
            }
        }

        // Order
        $order_sql = '';
        if ($order) {
            $possible_order = array(
                'name' => 'c.name',
                'email' => 's.email',
                'datetime' => 's.datetime',
                '!name' => 'c.name DESC',
                '!email' => 's.email DESC',
                '!datetime' => 's.datetime DESC',
            );
            if (!$order || empty($possible_order[$order])) {
                $order = key($possible_order);
            }
            $order_sql = "ORDER BY ".$possible_order[$order];
        }

        $sql = "SELECT s.*, c.name
                FROM {$this->table} AS s
                    {$contact_join} wa_contact AS c
                        ON s.contact_id=c.id
                {$where_sql}
                {$order_sql}
                {$limit_sql}";
        return $this->query($sql)->fetchAll();
    }
}

