<?php

/**
 * Collection of helper functions used throughout the app.
 */
class mailerHelper
{
    public static function getVars()
    {
        $fields = waContactFields::getAll();
        $vars = array(
            //'<a href="{$unsubscribe_link}">'._w('Unsubscribe').'</a>' => _w('Unsubscribe URL'),
        );
        foreach ($fields as $f) {
            $vars['{$'.$f->getId().'}'] = $f->getName();
        }
        return $vars;
    }

    public static function getTemplatePreviewUrl($template_id)
    {
        $file = self::getTemplatePreviewFile($template_id);
        if (!$file) {
            return null;
        }
        return wa('mailer')->getDataUrl('templates/preview/'.$template_id.'.jpg', true).'?'.filemtime($file);
    }

    public static function getTemplatePreviewFile($template_id, $force=false)
    {
        $file = wa('mailer')->getDataPath('templates/preview/'.$template_id.'.jpg', true);
        if (!$force && !is_readable($file)) {
            return null;
        }
        return $file;
    }

    /**
     * Make sure all images linked inside $message_body are inside the asset dir of message $message_id.
     * Create asset dir if necessary, copy files, replace in $message_body and update the message in DB.
     */
    public static function copyMessageFiles($message_id, &$message_body)
    {
        // Copy images mentioned in html code into new campaign's directory
        $url = wa()->getDataUrl('files/', true, 'mailer');
        $path = wa()->getDataPath('files/', true, 'mailer');
        if (preg_match_all('~'.$url.'([^\)"\']+)~is', $message_body, $m)) {
            waFiles::create($path.$message_id);
            foreach(array_flip(array_flip($m[1])) as $old_file) {
                $new_file = basename($old_file);
                $old_path = $path.$old_file;
                $new_path = $path.$message_id.'/'.$new_file;
                if ($old_path == $new_path) {
                    continue;
                }
                while(file_exists($new_path)) {
                    $new_file = rand(0, 9).$new_file;
                    $new_path = $path.$message_id.'/'.$new_file;
                }
                if (file_exists($old_path)) {
                    waFiles::copy($old_path, $new_path);
                }
                $message_body = str_replace($url.$old_file, $url.$message_id.'/'.$new_file, $message_body);
            }

            // Update campaign body
            $tm = new mailerTemplateModel();
            $tm->updateById($message_id, array(
                'body' => $message_body,
            ));
        }
    }

    public static function getContactsAppUrl()
    {
        if (wa()->appExists('contacts_full')) {
            return wa()->getAppUrl('contacts_full');
        }
        return wa()->getAppUrl('contacts');
    }

    public static function isReturnPathAlive($rp)
    {
        if ($rp && !is_array($rp)) {
            $rpm = new mailerReturnPathModel();
            $rp = $rpm->getById($rp);
        }
        if ($rp) {
            if (empty($rp['last_campaign_date'])) {
                return false;
            }
            return strtotime($rp['last_campaign_date']) > time() - mailerConfig::RETURN_PATH_CHECK_PERIOD;
        }
        return false;
    }

    public static function assignCampaignSidebarVars($view, $campaign, &$params=null, $recipients=null)
    {
        $view->assign('creator', new waContact($campaign['create_contact_id']));
        if ($campaign['status'] == mailerMessageModel::STATUS_DRAFT) {
            $view->assign('recipients_selected', self::countUniqueRecipients($campaign, $params, $recipients));
        } else {
            $view->assign('recipients_selected', true);
        }
        $view->assign('message_written', trim($campaign['body']) && trim($campaign['subject']));
    }

    public static function assignPagination($view, $start, $limit, $total_rows)
    {
        $pagination = array();
        $current_page = floor($start/$limit) + 1;
        $total_pages = floor(($total_rows-1)/$limit) + 1;
        $dots_added = false;
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i < 4) {
                $pagination[$i] = ($i-1)*$limit;
                $dots_added = false;
            } else if (abs($i-$current_page) < 3) {
                $pagination[$i] = ($i-1)*$limit;
                $dots_added = false;
            } else if ($total_pages - $i < 3) {
                $pagination[$i] = ($i-1)*$limit;
                $dots_added = false;
            } else if (!$dots_added) {
                $dots_added = true;
                $pagination[$i] = false;
            }
        }

        $view->assign('start', $start);
        $view->assign('total_rows', $total_rows);
        $view->assign('pagination', $pagination);
        $view->assign('current_page', $current_page);
    }

    public static function updateDraftRecipients($message_id)
    {
        // Remove previously saved data
        $drm = new mailerDraftRecipientsModel();
        $drm->deleteByField('message_id', $message_id);

        // Anything selected at all?
        $mrm = new mailerMessageRecipientsModel();
        $recipients = $mrm->getByField('message_id', $message_id, 'id');
        if (!$recipients) {
            return;
        }

        /**@/**
         * @event recipients.prepare
         *
         * This plugin hook is one of two which allow to implement custom recipient
         * selection criteria. This one is used to gather actual list of recipients
         * using previously saved criteria.
         *
         * Some criteria, such as by contact category or locale, are supported
         * by the core application and not plugins. Nonetheless, they are implemented
         * through the same interface using this event.
         * For mailer core implementation see: lib/handlers/mailer.recipients.prepare.handler.php
         *
         * Handlers are expected to process criteria from given list and do the following for ones they support:
         * 1) Populate mailer_message_log for given message_id using the criterion.
         * 2) Set $params['recipients'][...]['count'] to reflect number of matching addresses for the criterion.
         * 3) Set $params['recipients'][...]['name'] and $params['recipients'][...]['group']
         *    to human-readable name of this criterion to show in campaign report.
         *
         * @param array[string]array $params['id'] mailer_message.id
         * @param array[string]array $params['recipients'] list of rows from mailer_message_recipients
         * @return void
         */
        $params = array(
            'id' => $message_id,          // input
            'recipients' => &$recipients, // input / output
        );
        wa()->event('recipients.prepare', $params);

        // Save counts and human-readable names
        if (is_array($recipients)) {
            foreach($recipients as $r) {
                $mrm->updateById($r['id'], array(
                    'group' => ifempty($r['group']),
                    'count' => $r['count'],
                    'name' => $r['name'],
                ));
            }
        }
    }

    public static function countUniqueRecipients($campaign, &$params=null, $recipients=null, &$error=null)
    {
        if (empty($campaign['id'])) {
            return 0;
        }

        $mpm = new mailerMessageParamsModel();
        if ($params === null) {
            $params = $mpm->getByMessage($campaign['id']);
        }

        if (!isset($params['recipients_count'])) {
            if ($campaign['status'] != mailerMessageModel::STATUS_DRAFT) {
                $mlm = new mailerMessageLogModel();
                $params['recipients_count'] = (int) $mlm->countByMessage($campaign['id']);
            } else {
                if (!empty($params['recipients_update_error'])) {
                    $error = $params['recipients_update_error'];
                    return 0;
                }
                if (!empty($params['recipients_update_progress'])) {
                    if (time() - $params['recipients_update_progress'] > 100) {
                        $error = _w('Timeout error during recipient list preparation.');
                        $mpm->deleteByField(array(
                            'message_id' => $campaign['id'],
                            'name' => 'recipients_update_progress',
                        ));
                        unset($params['recipients_update_progress']);
                        $mpm->insert(array(
                            'message_id' => $campaign['id'],
                            'name' => 'recipients_update_error',
                            'value' => $error,
                        ));
                        $params['recipients_update_error'] = $error;
                        return 0;
                    } else {
                        $error = _w('Recipient list preparation is in progress.');
                        return 0;
                    }
                }

                try {
                    $mpm->deleteByField(array(
                        'message_id' => $campaign['id'],
                        'name' => array('recipients_update_error', 'recipients_update_progress'),
                    ));
                    unset($params['recipients_update_progress'], $params['recipients_update_error']);
                    $params['recipients_update_progress'] = time();
                    $mpm->insert(array(
                        'message_id' => $campaign['id'],
                        'name' => 'recipients_update_progress',
                        'value' => $params['recipients_update_progress'],
                    ));

                    self::updateDraftRecipients($campaign['id']);
                    $drm = new mailerDraftRecipientsModel();
                    $params['recipients_count'] = (int) $drm->countUniqueByMessage($campaign['id']);

                    $mpm->deleteByField(array(
                        'message_id' => $campaign['id'],
                        'name' => array('recipients_update_error', 'recipients_update_progress'),
                    ));
                    unset($params['recipients_update_progress'], $params['recipients_update_error']);
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    $mpm->deleteByField(array(
                        'message_id' => $campaign['id'],
                        'name' => array('recipients_update_error', 'recipients_update_progress'),
                    ));
                    unset($params['recipients_update_progress'], $params['recipients_update_error']);
                    $params['recipients_update_error'] = $error;
                    $mpm->insert(array(
                        'message_id' => $campaign['id'],
                        'name' => 'recipients_update_error',
                        'value' => $error,
                    ));
                    return 0;
                }
            }

            $mpm->query("REPLACE INTO mailer_message_params SET message_id=?, name='recipients_count', value=?", $campaign['id'], $params['recipients_count']);
        }

        return (int) $params['recipients_count'];
    }

    public static function isAdmin()
    {
        static $result = null;
        if ($result === null) {
            $result = wa()->getUser()->getRights('mailer', 'backend') > 1;
        }
        return $result;
    }

    public static function isAuthor()
    {
        static $result = null;
        if ($result === null) {
            $result = wa()->getUser()->getRights('mailer', 'author') > 0;
        }
        return $result;
    }

    public static function isInspector()
    {
        static $result = null;
        if ($result === null) {
            $result = wa()->getUser()->getRights('mailer', 'inspector') > 0;
        }
        return $result;
    }

    /** 0 - no access; 1 = read only; 2 = full access. */
    public static function campaignAccess($campaign)
    {
        if (self::isAdmin()) {
            return 2;
        }
        if (ifset($campaign['create_contact_id']) == wa()->getUser()->getId() && self::isAuthor()) {
            return 2;
        }
        if (self::isInspector()) {
            return 1;
        }
        return 0;
    }
}

