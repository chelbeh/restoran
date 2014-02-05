<?php

/**
 * Storage for individual campaign recipients and delivery status.
 */
class mailerMessageLogModel extends waModel
{
    const STATUS_PREVIOUSLY_UNAVAILABLE     = -4;
    const STATUS_PREVIOUSLY_UNSUBSCRIBED    = -3;
    const STATUS_NOT_DELIVERED              = -2;
    const STATUS_SENDING_ERROR              = -1;
    const STATUS_AWAITING                   = 0;
    const STATUS_SENT                       = 1;
    const STATUS_DELIVERED                  = 2;
    const STATUS_VIEWED                     = 3;
    const STATUS_CLICKED                    = 4;
    const STATUS_UNSUBSCRIBED               = 5;

    protected $table = "mailer_message_log";

    public function setStatus($id, $status, $error = '', $error_class='', $error_fatal=true)
    {
        if ($error_fatal && ($status == -1 || $status == -2)) {
            // Mark email as unavailable in wa_contact_emails
            $sql = "UPDATE wa_contact_emails AS e
                        JOIN mailer_message_log AS l
                            ON e.email=l.email
                    SET e.status='unavailable'
                    WHERE l.id=i:log_id";
            $this->exec($sql, array('log_id' => $id));
        }

        // Update status in log
        $data = array('status' => $status, 'datetime' => date("Y-m-d H:i:s"));
        if ($error) {
            $data['error'] = trim($error);
        }
        if ($error_class) {
            $data['error_class'] = $error_class;
        }
        return $this->updateById($id, $data);
    }

    public function getStatsByMessage($message_ids) {
        if (!is_array($message_ids)) {
            $message_ids = array($message_ids);
        }
        if (empty($message_ids)) {
            return array();
        }
        $sql = "SELECT message_id, status, count(*) AS num
                FROM {$this->table}
                WHERE message_id IN (i:messages)
                GROUP BY message_id, status";
        $stats = array();
        foreach($this->query($sql, array('messages' => $message_ids)) as $row) {
            $stats[$row['message_id']][$row['status']] = $row['num'];
        }
        return $stats;
    }

    public function getByMessage($message_id, $start=null, $limit=null, $status=null, $search=null, $error_class=null, &$total_rows=null)
    {
        if ($limit) {
            $start = (int) $start;
            $limit = (int) $limit;
            $limit = "LIMIT {$start}, {$limit}";
        } else {
            $limit = "";
        }

        $status_sql = '';
        if ($status) {
            if (is_array($status)) {
                $status_sql = ' AND status IN (:status) ';
            } else {
                $status_sql = ' AND status=:status ';
            }
        }

        $search_sql = '';
        if ($search) {
            $search_sql = " AND CONCAT(name, ' ', email) LIKE :search ";
        }

        $error_class_sql = '';
        if ($error_class === 'null') {
            $error_class_sql = ' AND error_class IS NULL';
        } else if ($error_class) {
            $error_class_sql = ' AND error_class=:error_class';
        }

        $sql = "SELECT ".($total_rows !== null ? 'SQL_CALC_FOUND_ROWS' : '')." *
                FROM {$this->table}
                WHERE message_id=:m_id
                    {$status_sql}
                    {$search_sql}
                    {$error_class_sql}
                ORDER BY name, id
                {$limit}";
        $result = $this->query($sql, array(
            'm_id' => $message_id,
            'status' => $status,
            'search' => '%'.$search.'%',
            'error_class' => $error_class,
        ));
        if ($total_rows !== null) {
            $total_rows = $this->query('SELECT FOUND_ROWS()')->fetchField();
        }
        return $result;
    }

    public function countByMessage($id)
    {
        $sql = "SELECT COUNT(*) FROM ".$this->table." WHERE message_id = i:id";
        return $this->query($sql, array('id' => $id))->fetchField();
    }

    public function countSentToday()
    {
        $result = 0;

        // Sent today
        $sql = "SELECT COUNT(*)
                FROM {$this->table} AS ml
                    JOIN mailer_message AS m
                        ON ml.message_id=m.id
                WHERE ml.status<>0
                    AND (m.finished_datetime IS NOT NULL OR m.send_datetime IS NOT NULL)
                    AND IFNULL(m.finished_datetime, m.send_datetime) > :today";
        $result += $this->query($sql, array('today' => date('Y-m-d').' 00:00:00'))->fetchField();

        // Not sent yet
        $result += $this->countByField('status', self::STATUS_AWAITING);

        return $result;
    }
}

