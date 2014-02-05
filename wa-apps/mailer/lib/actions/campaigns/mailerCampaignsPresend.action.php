<?php

/**
 * Returns HTML to insert into CampaignsSettings page
 * when user clicks the 'Send to selected recipients' link.
 * Validates campaign and either show error messages, or a button to proceed.
 * When user clicks the button, a POST is sent to this controller again,
 * and the sending starts.
 */
class mailerCampaignsPresendAction extends waViewAction
{
    public function execute()
    {
        $campaign_id = waRequest::get('campaign_id', 0, 'int');
        if (!$campaign_id) {
            throw new waException('No campaign id given.', 404);
        }

        // Campaign data
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($campaign_id);
        if (!$campaign) {
            throw new waException('Campaign not found.', 404);
        }
        if ($campaign['status'] > 0) {
            echo "<script>window.location.hash = '#/campaigns/report/{$campaign_id}/';</script>";
            exit;
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 2) {
            throw new waException('Access denied.', 403);
        }

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Start sending the campaign if POST came and validation passes.
        $errormsg = self::localValidate($campaign, $params);
        if (waRequest::post('send') && !$errormsg) {
            $errormsg = self::eventValidate($campaign, $params);
            if (!$errormsg) {
                self::prepareRecipients($campaign, $params);
                echo "<script>window.location.hash='#/campaigns/report/{$campaign_id}/';</script>";
                exit;
            }
        }

        $this->view->assign('errormsg', $errormsg);
        $this->view->assign('cron_command', 'php '.wa()->getConfig()->getRootPath().'/cli.php mailer send<br>php '.wa()->getConfig()->getRootPath().'/cli.php mailer check');
        $this->view->assign('cron_ok', wa()->getSetting('last_cron_time') + 3600*2 > time());
        $this->view->assign('last_cron_time', wa()->getSetting('last_cron_time'));
        $this->view->assign('return_path_ok', $this->isReturnPathOk($campaign, $params));
        $this->view->assign('unique_recipients', mailerHelper::countUniqueRecipients($campaign, $params));
        $this->view->assign('routing_ok', !!wa()->getRouteUrl('mailer', true));
    }

    /** Local validation: check basic campaign properties. */
    public static function localValidate($campaign, $params)
    {
        $errormsg = array();
        if (!trim($campaign['body'])) {
            $errormsg[] = _w('No message body.');
        }
        if (!trim($campaign['subject'])) {
            $errormsg[] = _w('No message subject.');
        }

        // Check if there are recipients selected
        $unique_recipients = mailerHelper::countUniqueRecipients($campaign, $params);
        if ($unique_recipients <= 0) {
            $errormsg[] = _w('No recipients selected.');
        }

        // Check if this campaign has more recipients than it is allowed
        $max_recipients_count = wa('mailer')->getConfig()->getOption('max_recipients_count');
        if ($max_recipients_count && $max_recipients_count < $unique_recipients) {
            $errormsg[] = _w('Maximum recipients limit has been exceeded:').' '.$max_recipients_count;
        }

        // Being paranoid: check that wa-data and wa-cache are available for writing
        foreach(array(wa()->getDataPath('', false, 'mailer'), waConfig::get('wa_path_cache')) as $path) {
            if (!file_exists($path)) {
                @waFiles::create($path);
            }
            if (!is_writable($path)) {
                $errormsg[] = sprintf_wp('%s is not writable', $path);
            }
        }

        // Check if daily limit exceeded
        $max_recipients_daily = wa('mailer')->getConfig()->getOption('max_recipients_daily');
        if ($max_recipients_daily) {
            $mlm = new mailerMessageLogModel();
            if ($max_recipients_daily < $mlm->countSentToday() + $unique_recipients) {
                $errormsg[] = _w('Maximum recipients daily limit has been exceeded:').' '.$max_recipients_daily;
            }
        }

        return $errormsg;
    }

    /** Allows plugins to validate campaign before sending. */
    public static function eventValidate($campaign_or_id, $params=null)
    {

        if (is_array($campaign_or_id)) {
            $campaign = $campaign_or_id;
        } else {
            $mm = new mailerMessageModel();
            $campaign = $mm->getById($campaign_or_id);
        }
        if ($params === null) {
            $mpm = new mailerMessageParamsModel();
            $params = $mpm->getByMessage($campaign['id']);
        }

        /**@/**
         * @event campaign.validate
         *
         * Allows to validate and cancel campaign before sending
         *
         * @param array[string]array $params['campaign'] input: row from mailer_message
         * @param array[string]array $params['params'] input: campaign params from mailer_message_params, key => value
         * @param array[string]array $params['errors'] output: list of error message strings to show to user
         * @return void
         */
        $evt_params = array(
            'campaign' => &$campaign, // INPUT
            'params' => &$params,     // INPUT
            'errors' => array(),      // OUTPUT
        );
        wa()->event('campaign.validate', $evt_params);
        return (array) $evt_params['errors'];
    }

    /**
     * Prepare given campaign for sending:
     * - Change status to mailerMessageModel::STATUS_CONTACTS
     * - Create recipient records in `mailer_message_log`
     * - Exclude unsubscribers and broken emails
     * - Create contacts for each new recipient
     * - Change status to mailerMessageModel::STATUS_SENDING
     * - Trigger `campaign.contacts_prepared` event.
     */
    protected static function prepareRecipients(&$campaign, &$params)
    {
        // Change campaign state: 'preparing recipients'
        $m = new mailerMessage($campaign['id']);
        $m->status(mailerMessageModel::STATUS_CONTACTS);

        // Move to message_log
        $drm = new mailerDraftRecipientsModel();
        $drm->moveToMessageLog($campaign['id']);

        // Mark unsubscriber's emails
        $mm = new mailerMessageModel();
        $sql = "UPDATE mailer_message_log AS l
                    JOIN mailer_unsubscriber AS u
                        ON l.email = u.email
                SET l.status = ".mailerMessageLogModel::STATUS_PREVIOUSLY_UNSUBSCRIBED."
                WHERE l.message_id = ".$campaign['id']."
                    AND u.list_id = 0";
        $mm->exec($sql);

        // Mark emails known to be unavailable
        if (empty($params['send_to_unavailable'])) {
            $sql = "UPDATE mailer_message_log AS l
                        JOIN wa_contact_emails AS e
                            ON l.email = e.email
                    SET l.status = ".mailerMessageLogModel::STATUS_PREVIOUSLY_UNAVAILABLE."
                    WHERE l.message_id = ".$campaign['id']."
                        AND e.status = 'unavailable'";
            $mm->exec($sql);
        }

        // Make sure all contacts are created
        $mlm = new mailerMessageLogModel();
        $replace_values = array();
        $replace_sql = "INSERT INTO mailer_message_log (id, contact_id) VALUES %s ON DUPLICATE KEY UPDATE contact_id=VALUES(contact_id)";
        foreach($mlm->where('message_id=? AND contact_id=0', $campaign['id'])->query() as $row) {
            $contact = new waContact();
            $contact->save(array(
                'name' => $row['name'],
                'email' => $row['email'],
                'create_app_id' => 'mailer',
                'create_method' => 'recipient',
            ));
            $replace_values[] = sprintf('(%d,%d)', $row['id'], $contact->getId());
            if (count($replace_values) > 50) {
                $mlm->exec(sprintf($replace_sql, implode(',', $replace_values)));
            }
        }
        if ($replace_values) {
            $mlm->exec(sprintf($replace_sql, implode(',', $replace_values)));
        }

        // Change campaign state: 'sending'
        $m->status(mailerMessageModel::STATUS_SENDING);
        $campaign['status'] = mailerMessageModel::STATUS_SENDING;

        /**@/**
         * @event campaign.contacts_prepared
         *
         * Campaign just moved to SENDING status
         *
         * @param array[string]array $params['campaign'] row from mailer_message
         * @param array[string]array $params['params'] campaign params from mailer_message_params, key => value
         * @return void
         */
        $evt_params = array(
            'campaign' => $campaign,
            'params' => $params,
        );
        wa()->event('campaign.contacts_prepared', $evt_params);
    }

    /** Returns false if there's a problem connecting to this campaign's return path */
    protected function isReturnPathOk($campaign)
    {
        if (empty($campaign['return_path'])) {
            return true;
        }

        $rpm = new mailerReturnPathModel();
        $rp = $rpm->getByEmail($campaign['return_path']);
        if (!$rp) {
            return false;
        }

        // Check if SSL is supported
        if (!defined('OPENSSL_VERSION_NUMBER') && !empty($data['ssl'])) {
            return false;
        }

        // Check cache in session
        $status = wa()->getStorage()->get('mailer_rp_status_'.$rp['id']);
        if (isset($status)) {
            return $status;
        }

        // Try to connect using given settings
        try {
            $mail_reader = new waMailPOP3($rp);
            $mail_reader->count();
            wa()->getStorage()->set('mailer_rp_status_'.$rp['id'], true);
            return true;
        } catch (Exception $e) {
        }

        wa()->getStorage()->set('mailer_rp_status_'.$rp['id'], false);
        return false;
    }
}

