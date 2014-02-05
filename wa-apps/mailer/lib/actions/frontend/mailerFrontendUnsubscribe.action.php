<?php

/**
 * Frontend action to serve unsubscribe links from mail.
 */
class mailerFrontendUnsubscribeAction extends waViewAction
{
    public function execute()
    {
        $hash = waRequest::get('hash');
        if (!$hash) {
            $hash = waRequest::param('hash');
        }
        $log_id = substr($hash, 16, -16);

        // When log_id not specified, unsubscribe by email, if present
        if (!$log_id) {
            $email = waRequest::get('email');
            if (!$email) {
                $email = waRequest::param('email');
            }
            if (!$email) {
                throw new waException('Page not found', 404);
            }

            $this->unsubscribeByEmail($email);
            $this->view->assign('email', $email);
            return;
        }

        $mailer_log_model = new mailerMessageLogModel();
        $log = $mailer_log_model->getById($log_id);
        if (!$log || $hash !== mailerMessage::getUnsubscribeHash($log)) {
            throw new waException('Page not found', 404);
        }

        $message_model = new mailerMessageModel();
        $message = $message_model->getById($log['message_id']);

        $list_id = waRequest::get('id');
        if ($list_id === null) {
            $list_id = $message['list_id'];
        }

        // Add email to mailer_unsubscriber
        $unsubscribe_model = new mailerUnsubscriberModel();
        if (!$list_id) {
            // list_id == 0 means: all lists
            $unsubscribe_model->deleteByField('email', $log['email']);
        }
        $unsubscribe_model->insert(array(
            'email' => $log['email'],
            'list_id' => $list_id,
            'datetime' => date('Y-m-d H:i:s'),
            'message_id' => $log['message_id'],
        ), 2);

        // Remove email from mailer_subscriber
        $subscribe_model = new mailerSubscriberModel();
        if ($list_id) {
            $subscribe_model->deleteByField(array(
                'contact_id' => $log['contact_id'],
                'list_id' => $list_id
            ));
        } else {
            $subscribe_model->deleteByField('contact_id', $log['contact_id']);
        }

        // Update campaign statistics
        $mailer_log_model->updateById($log_id, array(
            'status' => mailerMessageLogModel::STATUS_UNSUBSCRIBED,
        ));

        // Add to wa_log
        $this->log('unsubscribe', 1, $log['contact_id'], 'list:'.$list_id.";message:".$message['id']);

        // Prepare view
        $this->view->assign('email', $log['email']);
    }

    protected function unsubscribeByEmail($email) {
        // Add email to mailer_unsubscriber
        $unsubscribe_model = new mailerUnsubscriberModel();
        $unsubscribe_model->deleteByField('email', $email);
        $unsubscribe_model->insert(array(
            'email' => $email,
            'list_id' => 0,
            'datetime' => date('Y-m-d H:i:s'),
            'message_id' => 0, // most probably a test send
        ), 2);

        // Remove email from mailer_subscriber
        $subscribe_model = new mailerSubscriberModel();
        $subscribe_model->deleteByField('email', $email);

        // Add to wa_log
        $this->log('unsubscribe', 1, 0);
    }
}

