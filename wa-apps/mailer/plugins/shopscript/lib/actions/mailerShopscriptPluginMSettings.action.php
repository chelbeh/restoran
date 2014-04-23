<?php

/**
 * Show plugin settings form and save POST data from that form.
 */
class mailerShopscriptPluginMSettingsAction extends waViewAction
{
    public function execute()
    {
        // Load settings from DB
        $asm = new waAppSettingsModel();
        $data = $asm->get(array('mailer', 'shopscript'));
        if (!$data || !is_array($data)) {
            $data = array();
        }
        $data += array(
            'mysql_host' => '',
            'mysql_db' => '',
            'mysql_login' => '',
            'mysql_password' => '',
        );
        unset($data['update_time']);

        // Save from POST if data came
        $saved = $this->saveFromPost($data, $asm);

        // Settings work?
        $error = false;
        if ($saved) {
            try {
                mailerShopscriptPlugin::getTransport();

                // Settings seem to work.
                // Since settings changed, we should clear recipients cache
                // for all drafts that have @shopscript recipient selection criteria selected.
                $sql = "SELECT m.id
                        FROM mailer_message AS m
                            JOIN mailer_message_recipients AS mr
                                ON mr.message_id=m.id
                        WHERE m.status <= 0
                            AND mr.value LIKE '@shopscript/%'";
                $message_ids = array_keys($asm->query($sql)->fetchAll('id'));
                if ($message_ids) {
                    $sql = "DELETE FROM mailer_draft_recipients WHERE message_id IN (i:ids)";
                    $asm->query($sql, array('ids' => $message_ids));

                    $sql  = "DELETE FROM mailer_message_params WHERE name=? AND message_id IN (?)";
                    $asm->query($sql, 'recipients_count', $message_ids);
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        // show HTML form
        $this->view->assign('data', $data);
        $this->view->assign('saved', $saved);
        $this->view->assign('error', $error);
        $this->view->assign('campaign_id', waRequest::request('campaign_id'));
    }

    protected function saveFromPost(&$data, $asm)
    {
        $post = waRequest::post('data');
        if (!$post || !is_array($post)) {
            return false;
        }

        foreach($data as $k => $old_v) {
            $v = $post[$k];
            if ($old_v != $v) {
                $asm->set(array('mailer', 'shopscript'), $k, $v);
                $data[$k] = $v;
            }
        }

        return true;
    }
}

