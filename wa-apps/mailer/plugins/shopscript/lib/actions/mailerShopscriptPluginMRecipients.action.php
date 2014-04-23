<?php

/**
 * Used as a part of recipients.form event handler.
 * See mailerShopscriptPlugin->recipientsForm()
 */
class mailerShopscriptPluginMRecipientsAction extends waViewAction
{
    public function execute()
    {
        $campaign = $this->params['campaign'];
        $recipients = $this->params['recipients'];
        $campaign_id = $this->params['campaign']['id'];

        $options = array(
            'all_customers' => array(
                'count' => null,
                'label' => _wp('All Shop-Script customers'),
                'value' => '@shopscript/all_customers',
                'checked' => false,
            ),
            'subscribers' => array(
                'count' => null,
                'label' => _wp('Shop-Script news subscribers'),
                'value' => '@shopscript/subscribers',
                'checked' => false,
            ),
        );

        // Count addresses
        $settings_error = '';
        try {
            $transport = mailerShopscriptPlugin::getTransport();
            foreach($options as $list_type => &$o) {
                $o['count'] = $transport->count($list_type);
            }
            unset($o);
        } catch (Exception $e) {
            $settings_error = $e->getMessage();
        }

        // Lists that are previously selected
        $prefix = '@shopscript/';
        $prefix_len = strlen($prefix);
        foreach($recipients as $id => $value) {
            if (strlen($value) > $prefix_len && substr($value, 0, $prefix_len) == $prefix) {
                $list_type = substr($value, $prefix_len);
                $options[$list_type]['checked'] = true;
                $options[$list_type]['list_id'] = $id;
            }
        }

        // Plugin settings
        $asm = new waAppSettingsModel();
        $settings = $asm->get(array('mailer', 'shopscript'));

        $this->view->assign('options', $options);
        $this->view->assign('campaign_id', $campaign_id);
        $this->view->assign('settings_error', $settings_error);
        $this->view->assign('mysql_host', $settings['mysql_host']);
        $this->view->assign('mysql_db', $settings['mysql_db']);
    }
}

