<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package installer
 */

class installerUpdateAction extends waViewAction
{
    public function execute()
    {
        $messages = installerMessage::getInstance()->handle(waRequest::get('msg'));

        $counter = array(
            'total'      => 0,
            'applicable' => 0,
            'payware'    => 0,
        );
        $items = array();
        try {

            $items = installerHelper::getUpdates();
            $counter = installerHelper::getUpdatesCounter(null);
            if (isset($items['installer'])) {
                $items['installer']['name'] = _w('Webasyst Framework');
            };

            foreach ($items as &$item) {
                if (!empty($item['error'])) {
                    $model = new waAnnouncementModel();
                    $data = array(
                        'app_id'   => 'installer',
                        'text'     => $item['error'],
                        'datetime' => date('Y-m-d H:i:s', time() - 86400),
                    );
                    $count = $model->select('COUNT(1) `cnt`')->where('app_id=s:app_id AND datetime > s:datetime', $data)->fetchField('cnt');
                    if (!$count) {
                        $data['datetime'] = date('Y-m-d H:i:s');
                        $model->insert($data);
                    }
                    break;
                }
            }

        } catch (Exception $ex) {
            $messages[] = array('text' => $ex->getMessage(), 'result' => 'fail');
        }

        installerHelper::checkUpdates($messages);
        if (!waRequest::get('_')) {
            $this->setLayout(new installerBackendLayout());
            if ($messages) {
                $this->getLayout()->assign('messages', $messages);
            }
            $this->getLayout()->assign('update_counter', $counter['total']);
            $this->getLayout()->assign('no_ajax', true);
        } else {
            $this->view->assign('messages', $messages);
        }

        $this->view->assign('error', false);
        $this->view->assign('update_counter', $counter['total']);
        $this->view->assign('update_counter_applicable', $counter['applicable']);
        $this->view->assign('items', $items);
        $this->view->assign('domain', installerHelper::getDomain());

        $this->view->assign('title', _w('Updates'));
    }
}
//EOF