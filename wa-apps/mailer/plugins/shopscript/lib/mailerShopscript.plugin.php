<?php

class mailerShopscriptPlugin extends waPlugin
{
    public function headBlocks($id)
    {
        $plugin_static_url = $this->getPluginStaticUrl();
        return array(
            'html' => '<script src="'.$plugin_static_url.'js/controller.js"></script>',
        );
    }

    /** Handler for recipients.form event: HTML for recipients selection page. */
    public function recipientsForm(&$params)
    {
        $prefix = '@shopscript/';
        $something_selected = false;
        $prefix_len = strlen($prefix);
        foreach($params['recipients'] as $id => $value) {
            if (strlen($value) > $prefix_len && substr($value, 0, $prefix_len) == $prefix) {
                $something_selected = true;
                break;
            }
        }

        $params['recipients_groups']['shopscript'] = array(
            'name' => _wp('Import from Shop-Script'),
            'content' => with(new mailerShopscriptPluginMRecipientsAction($params))->display(),
            'opened' => $something_selected,
            'comment' => _wp('Add newsletter subscribers from the database of your online store based on the WebAsyst Shop-Script application (previous version).'),
        );
    }

    public function recipientsPrepare(&$params)
    {
        $message_id = (int) $params['id'];
        $recipients = &$params['recipients'];

        try {
            $transport = self::getTransport();
        } catch (Exception $e) {
            // No transport set up. Oh, well.
            return null;
        }

        $insert_rows = array();
        $prefix = '@shopscript/';
        $prefix_len = strlen($prefix);
        $insert_sql = "INSERT IGNORE INTO mailer_draft_recipients (message_id, contact_id, name, email) VALUES ";

        $cem = new waContactEmailsModel();
        $mlm = new mailerMessageLogModel();

        foreach($recipients as $r_id => &$r) {
            $value = $r['value'];
            if (strlen($value) < $prefix_len || substr($value, 0, $prefix_len) != $prefix) {
                continue;
            }

            $list_type = substr($value, $prefix_len);
            switch($list_type) {
                case 'all_customers':
                    $r['name'] = _wp('All Shop-Script customers');
                    break;
                case 'subscribers':
                    $r['name'] = _wp('Shop-Script news subscribers');
                    break;
                default: continue 2;
            }

            $r['group'] = _wp('Import from Shop-Script');

            foreach($transport->get($list_type) as $email => $name) {
                $contact_id = (int) $cem->getContactIdByEmail($email);
                $insert_rows[] = sprintf("(%d,%d,'%s','%s')", $message_id, $contact_id, $mlm->escape($name), $mlm->escape($email));
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
    }

    public static function getTransport()
    {
        static $t = null;
        if (!$t) {
            $t = new mailerShopscriptPluginSQLTransport();
        }
        return $t;
    }
}

