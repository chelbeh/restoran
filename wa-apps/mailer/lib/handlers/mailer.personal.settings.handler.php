<?php

class mailerMailerPersonalSettingsHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $theme_id = 'default';
        $routes = $params['routes'];
        if ($routes) {
            $route = current($routes);
            if (!empty($route['theme'])) {
                $theme_id = $route['theme'];
            }
        }

        $theme = new waTheme($theme_id, 'mailer');
        $theme_path = $theme->getPath();

        $files = array();
        foreach (array('my.subscriptions.html') as $f) {
                $file = $theme->getFile($f);
            $file['id'] = $f;
            $file_path = $theme_path.'/'.$f;
            $content = file_exists($file_path) ? file_get_contents($file_path) : '';
            $file['content'] = $content;
            $files[] = $file;
        }

        $view = wa()->getView();
        $view->assign('files', $files);

        $view->assign('theme_id', $theme_id);

        $template = wa()->getAppPath('templates/handlers/PersonalSettings.html', 'mailer');
        return $view->fetch($template);
    }
}