<?php

class mailerShopscriptPluginSQLTransport extends waModel
{
    public function __construct($type = null, $writable = false)
    {
        $asm = new waAppSettingsModel();
        $settings = $asm->get(array('mailer', 'shopscript'));

        if (empty($settings['mysql_db'])) {
            throw new waException('No DB settings specified.');
        }

        parent::__construct(array(
            'host' => ifset($settings['mysql_host'], ''),
            'user' => ifset($settings['mysql_login'], ''),
            'password' => ifset($settings['mysql_password'], ''),
            'database' => ifset($settings['mysql_db'], ''),
        ));

        // Check that settings work
        $this->query('SELECT * FROM SC_subscribers LIMIT 0');
    }

    public function count($list_type)
    {
        switch($list_type) {
            case 'all_customers':
                $sql = "SELECT COUNT(DISTINCT o.customer_email) FROM SC_orders AS o";
                break;
            case 'subscribers':
                $sql = "SELECT COUNT(DISTINCT s.email) FROM SC_subscribers AS s";
                break;
            default:
                return 0;
        }
        return (int) $this->query($sql)->fetchField();
    }

    public function get($list_type)
    {
        $result = array();
        switch($list_type) {
            case 'all_customers':
                $sql = "SELECT o.customer_email AS email, o.customer_firstname AS first_name, o.customer_lastname AS last_name
                        FROM SC_orders AS o";
                break;
            case 'subscribers':
                $sql = "SELECT s.email, c.first_name, c.last_name
                        FROM SC_subscribers AS s
                            LEFT JOIN SC_customers AS c
                                ON c.customerID = s.customerID";
                break;
        }

        foreach ($this->query($sql) as $row) {
            $email = strtolower(trim($row['email']));
            if (!$email) {
                continue;
            }
            $name = trim($row['first_name'].' '.$row['last_name']);
            if (!$name) {
                $name = true;
            }
            $result[$email] = $name;
        }

        return $result;
    }
}

