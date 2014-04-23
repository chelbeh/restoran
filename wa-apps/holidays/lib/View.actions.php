<?php
/**
 * Base class instead of waViewActions.
 * Implements custom search logic for template files.
 */
class holidaysViewActions extends waViewActions
{
    protected function getTemplate()
    {
        if ($this->template === null) {
            if (preg_match('~^([a-z]+)~', get_class($this), $matches) && !empty($matches[1])) {
                $prefix = $app_id = $matches[1];
            } else {
                $app_id = wa()->getApp();
                $prefix = wa()->getConfig()->getPrefix();
            }
            $module = strtolower(substr(get_class($this), strlen($prefix), -7));
            $action = $this->action;

            // First look in templates/module/action.htm
            $path1 = wa()->getAppPath('templates/'.$module.'/'.$action.$this->view->getPostfix(), $app_id);
            if (file_exists($path1)) {
                return $path1;
            }

            // Then look in templates root: moduleAction.html
            $path2 = wa()->getAppPath('templates/'.$module.ucfirst($action).$this->view->getPostfix(), $app_id);
            if (file_exists($path2)) {
                return $path2;
            }
            
            throw new waException('Template not found: '.$path1.' OR '.$path2);
        }
        return parent::getTemplate();
    }
}
