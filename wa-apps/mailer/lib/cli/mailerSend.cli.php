<?php

/**
 * /path/to/php /path/to/wa/cli.php mailer send
 *
 * This controller should be called by CRON to continue sending of big campaigns.
 */
class mailerSendCli extends waCliController
{
    public function execute()
    {
        $asm = new waAppSettingsModel();
        $asm->set('mailer', 'last_cron_time', time());

        if ( ( $id = waRequest::param(0)) && wa_is_int($id)) {
            $mailer_message = new mailerMessage($id);
            $mailer_message->send();
            return;
        }

        $message_model = new mailerMessageModel();
        $messages = $message_model->getByField('status', array(
            mailerMessageModel::STATUS_SENDING,
        ), true);

        foreach ($messages as $message) {
            $mailer_message = new mailerMessage($message);
            $mailer_message->send();
        }
    }
}

