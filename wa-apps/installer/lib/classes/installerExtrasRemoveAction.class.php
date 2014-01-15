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

abstract class installerExtrasRemoveAction extends waViewAction
{
    protected $extras_type = false;
    /**
     *
     * @var waInstallerApps
     */
    protected $installer;

    abstract protected function removeExtras($app_id, $extras_id);


    private function init()
    {
        $url = parse_url($r = waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(themes|plugins)($|&)/', $url, $matches)) {
            $this->extras_type = $matches[2];
        }
        if (installerHelper::isDeveloper()) {
            switch ($this->extras_type) {
                case 'themes':
                    $msg = _w("Unable to delete application's themes (developer version is on)");
                    break;
                case 'plugins':
                    $msg = _w("Unable to delete application's plugins (developer version is on)");
                    break;
                default:
                    $msg = '???';
                    break;
            }
            $this->redirect(array(
                'module' => $this->extras_type,
                'msg'    => installerMessage::getInstance()->raiseMessage($msg, 'fail'),
            ));
        }
    }

    function execute()
    {
        $this->init();
        if (!$this->extras_type && preg_match('/^installer(\w+)RemoveAction$/', get_class($this), $matches)) {
            $this->extras_type = strtolower($matches[1]);
        }


        $module = $this->extras_type;
        $url = parse_url(waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match("/(^|&)module=(update|apps| {$this->extras_type})($|&)/", $url, $matches)) {
            $module = $matches[2];
        }

        $extras_ids = waRequest::post('extras_id');
        try {
            /*
             _w('Application themes not found');
             _w('Application plugins not found');
             */
            foreach ($extras_ids as & $info) {
                if (!is_array($info)) {
                    $info = array('vendor' => $info);
                }
                unset($info);
            }

            $options = array(
                'installed' => true,
            );


            if ($module == 'plugins') {
                $options['system'] = true;
            }

            $this->installer = installerHelper::getInstaller();
            $app_list = $this->installer->getItems();

            $queue = array();


            foreach ($extras_ids as $slug => $info) {
                $slug_chunks = explode('/', $slug);
                if ($slug_chunks == 'wa-plugins') {
                    $app_id = $slug_chunks[0].'/'.$slug_chunks[1];
                } else {
                    $app_id = reset($slug_chunks);
                }
                if (isset($app_list[$app_id])) {
                    $app = $app_list[$app_id];
                    $installed = $this->installer->getItemInfo($slug, $options);
                    if ($info['vendor'] == $installed['installed']['vendor']) {
                        if (!empty($installed['installed']['system'])) {
                            /*
                             _w("Can not delete system application's themes \"%s\"");
                             _w("Can not delete system application's plugins \"%s\"");
                             */

                            $message = "Can not delete system application's {$this->extras_type} \"%s\"";
                            throw new waException(sprintf(_w($message), $info['name']));
                        }
                        $queue[] = array(
                            'app_slug' => $app_id,
                            'ext_id'   => $installed['id'],
                            'name'     => "{$installed['installed']['name']} ({$app['name']})",
                        );
                        unset($extras_ids[$slug]);
                    }
                }
            }

            $deleted_extras = array();
            foreach ($queue as $q) {
                if ($this->removeExtras($q['app_slug'], $q['ext_id'])) {
                    $deleted_extras[] = $q['name'];
                }
            }


            foreach ($extras_ids as $slug => $data) {
                $slug = preg_replace('@^wa-plugins/([^/]+)/plugins/(.+)$@', 'wa-plugins/$1/$2', $slug);
                if (preg_match('@^wa-plugins/(([^/]+/)[^/]+)$@', $slug, $matches)) {
                    $path = wa()->getConfig()->getPath('plugins').'/'.$matches[1];
                    $info_path = $path.'/lib/config/plugin.php';
                    if (file_exists($info_path) && ($info = include($info_path))) {
                        waFiles::delete($path, true);
                        $deleted_extras[] = empty($info['name']) ? $matches[1] : $info['name'];
                    }
                }
            }
            if (!$deleted_extras) {
                $message = sprintf('Application %s not found', $this->extras_type);
                throw new waException(_w($message));
            }
            /*
             _w('Application plugin %s has been deleted', 'Applications plugins %s have been deleted');
             _w('Application theme %s has been deleted', 'Applications themes %s have been deleted');
             */
            $message_singular = sprintf('Application %s %%s has been deleted', preg_replace('/s$/', '', $this->extras_type));
            $message_plural = sprintf('Applications %a %%s have been deleted', $this->extras_type);
            $message = sprintf(_w($message_singular, $message_plural, count($deleted_extras), false), implode(', ', $deleted_extras));
            $msg = installerMessage::getInstance()->raiseMessage($message);
            $this->redirect('?msg='.$msg.'#/'.$module.'/');
        } catch (Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            $this->redirect('?msg='.$msg.'#/'.$module.'/');
        }

    }
}
