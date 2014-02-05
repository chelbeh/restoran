<?php

class mailerSendersDialogAction extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $id = waRequest::get('id');
        $sender_model = new mailerSenderModel();
        $sender = $sender_model->getById($id);

        $params_model = new mailerSenderParamsModel();
        $params = $params_model->getBySender($id);

        if (!isset($params['type'])) {
            $params['type'] = 'mail';
        }

        $this->assignSenderTypes(waSystemConfig::isDebug() || $params['type'] == 'test');
        $this->view->assign('show_delete_button', $id && $sender && $sender_model->countAll() > 1);
        $this->view->assign('sender', $sender);
        $this->view->assign('params', $params);
    }

    protected function assignSenderTypes($debug)
    {
        static $senders = null;
        if ($senders === null) {
            $senders = array(
                'default' => _w('System Default'),
                'mail' => _w('php mail() function'),
                'smtp' => _w('SMTP'),
            );
            if (function_exists('proc_open')) {
                $senders['sendmail'] = _w('Sendmail');
            }
            if ($debug) {
                $senders['test'] = _w('Debug mailer');
            }

            /**
             * !!! TODO: docs for sender.types event
             */
            wa()->event('sender.types', $senders);
        }

        $this->view->assign('sender_types', $senders);
    }
}

