<?php
/**
 * Base class instead of waViewAction.
 * Implements custom search logic for template files.
 */
class holidaysViewAction extends waViewAction
{
    protected function getTemplate()
    {
        if ($this->template === null) {
            // Look in templates/module/action.htm
            if (preg_match("/^([a-z]+)([A-Z][a-z]*)(.*)$/", get_class($this), $match) && !empty($match[1]) && !empty($match[2]) && !empty($match[3])) {
                $app_id = $match[1];
                $module = strtolower($match[2]{0}).substr($match[2], 1); // lcfirst() is PHP 5.3+ only :(
                $action = strtolower(substr($match[3], 0, -6));

                if ($module && $action) {
                    $path1 = wa()->getAppPath('templates/'.$module.'/'.$action.$this->view->getPostfix(), $app_id);
                    if (file_exists($path1)) {
                        return $path1;
                    }
                }
            }

            // Look in templates/moduleAction.html
            if (!empty($app_id)) {
                $prefix = $app_id;
            } else {
                $app_id = wa()->getApp();
                $prefix = wa()->getConfig()->getPrefix();
            }
            $template = lcfirst(substr(get_class($this), strlen($prefix), -6));
            $path2 = wa()->getAppPath('templates/'.$template.$this->view->getPostfix(), $app_id);
            if (file_exists($path2)) {
                return $path2;
            }

            $paths = implode(' OR ', array_filter(array(ifset($path1), $path2)));
            throw new waException('Template not found: '.$paths);
        }

        return parent::getTemplate();
    }
}

