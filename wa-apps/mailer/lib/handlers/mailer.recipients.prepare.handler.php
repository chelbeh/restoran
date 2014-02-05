<?php

/**
 * Implements core recipient selection criteria.
 * See recipients.prepare event description for details.
 */
class mailerMailerRecipientsPrepareHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $message_id = (int) $params['id'];
        $recipients = &$params['recipients'];
        $mlm = new mailerMessageLogModel();
        $msm = new mailerSubscriberModel();
        $cem = new waContactEmailsModel();

        $categories = null;
        $insert_rows = array();
        $insert_sql = "INSERT IGNORE INTO mailer_draft_recipients (message_id, contact_id, name, email) VALUES ";
        foreach($recipients as $r_id => &$r) {
            $value = $r['value'];

            // Being paranoid...
            if (!strlen($value)) {
                continue;
            }

            // Skip list types supported by plugins
            if ($value{0} == '@') {
                continue;
            }

            // Is it subscribers list id?
            if (wa_is_int($value)) {
                $where_sql = '';
                if ($value !== '0') {
                    $list = $msm->getById($value);
                    if (!$list) {
                        continue;
                    }
                    $r['name'] = $list['name'];
                    $where_sql = "WHERE s.list_id IN (0, ".((int)$value).")";
                } else {
                    $r['name'] = _w('All subscribers');
                }
                $sql = "INSERT IGNORE INTO mailer_draft_recipients (message_id, contact_id, name, email)
                        SELECT ".$message_id.", IFNULL(c.id, 0), IFNULL(c.name, ''), s.email
                        FROM mailer_subscriber AS s
                            LEFT JOIN wa_contact AS c
                                ON c.id = s.contact_id
                        ".$where_sql;
                $mlm->exec($sql);

                $sql = "SELECT COUNT(*) FROM mailer_subscriber AS s ".$where_sql;
                $r['count'] = $mlm->query($sql)->fetchField();
                $r['group'] = _w('Subscribers');
                continue;
            }

            // Is it a ContactsCollection hash?
            if ($value{0} == '/') {
                $cc = new waContactsCollection($value);
                $cc->saveToTable("mailer_draft_recipients", array(
                    'contact_id' => 'id',
                    'message_id' => $message_id,
                    'name',
                    'email' => '_email',
                ), true);

                $r['count'] = $cc->count();
                $r['group'] = null;
                $r['name'] = null;

                // See if the hash is of one of supported types
                if (FALSE !== strpos($value, '/category/')) {
                    $category_id = explode('/', $value);
                    $category_id = end($category_id);
                    if ($category_id && wa_is_int($category_id)) {
                        if ($categories === null) {
                            $ccm = new waContactCategoryModel();
                            $categories = $ccm->getNames();
                        }
                        $r['name'] = ifset($categories[$category_id], $category_id);
                        $r['group'] = _w('Categories');
                    }
                } else if (FALSE !== strpos($value, '/locale=')) {
                    $locale = explode('=', $value);
                    $locale = end($locale);
                    if ($locale) {
                        $l = waLocale::getInfo($locale);
                        if ($l) {
                            $r['name'] = $l['name'];
                        }
                    }
                    $r['group'] = _w('Languages');
                    if (!$r['name']) {
                        $r['name'] = $locale;
                    }
                } else if ($value == '/') {
                    $r['name'] = _w('All contacts');
                }
                if (!$r['name']) {
                    $r['name'] = $value;
                }
                continue;
            }

            // Otherwise, it is a list of emails.
            $r['count'] = 0;
            $r['group'] = null;
            $r['name'] = _w('Additional emails');
            foreach (wao(new mailerMailAddressParser($value))->parse() as $address) {
                $contact_id = (int) $cem->getContactIdByEmail($address['email']);
                $insert_rows[] = sprintf("(%d,%d,'%s','%s')", $message_id, $contact_id, $mlm->escape($address['name']), $mlm->escape($address['email']));
                $r['count']++;
                if (count($insert_rows) > 50) {
                    $mlm->exec($insert_sql.implode(',', $insert_rows));
                    $insert_rows = array();
                }
            }
        }

        if ($insert_rows) {
            $mlm->exec($insert_sql.implode(",", $insert_rows));
        }
        unset($insert_rows);
    }
}

