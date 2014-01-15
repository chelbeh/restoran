<?php
class installerBackendLayout extends waLayout
{
    public function execute()
    {
        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));
        installerHelper::checkUpdates($messages);
        if ($m = $this->view->getVars('messages')) {
            $messages = array_merge($m, $messages);
        }
        $this->view->assign('messages', $messages);

        $model = new waAppSettingsModel();
        $this->view->assign('update_counter', $model->get($this->getApp(), 'update_counter'));
        $this->view->assign('module', waRequest::get('module', 'backend'));
        $this->view->assign('default_query', array(
            'plugins' => wa()->appExists('shop') ? 'shop' : 'wa-plugins/payment',
        ));
    }
}
