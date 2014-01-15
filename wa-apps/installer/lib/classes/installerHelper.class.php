<?php
class installerHelper
{
    /**
     *
     * @var waAppSettingsModel
     */
    private static $model;
    /**
     *
     * @var waInstallerApps
     */
    private static $installer;

    private static $counter;

    /**
     *
     * @return waInstallerApps
     */
    public static function &getInstaller()
    {
        if (!self::$model) {
            self::$model = new waAppSettingsModel();
        }
        if (!self::$installer) {
            $license = self::$model->get('webasyst', 'license', false);
            $ttl = 600;
            $locale = wa()->getSetting('locale', wa()->getLocale(), 'webasyst');
            self::$installer = new waInstallerApps($license, $locale, $ttl, !!waRequest::get('refresh'));
        }
        return self::$installer;
    }

    /**
     *
     * Get hash of installed framework
     * @return string
     */
    public static function getHash()
    {
        return self::getInstaller()->getHash();
    }

    /**
     * Get current domain name
     * @return string
     */
    public static function getDomain()
    {
        return self::getInstaller()->getDomain();
    }

    public static function flushCache($apps = array())
    {
        $path_cache = waConfig::get('wa_path_cache');
        waFiles::protect($path_cache);

        $caches = array();
        $paths = waFiles::listdir($path_cache);
        foreach ($paths as $path) {
            #skip long action & data path
            if ($path != 'temp') {
                $path = $path_cache.'/'.$path;
                if (is_dir($path)) {
                    $caches[] = $path;
                }
            }
        }

        if ($apps) {
            $app_path = waConfig::get('wa_path_apps');
            foreach ($apps as $app) {
                if (!empty($app['installed'])) {
                    $caches[] = $app_path.'/'.$app['slug'].'/js/compiled';
                }
            }
        }

        $caches[] = $path_cache.'/temp';
        $root_path = wa()->getConfig()->getRootPath();
        $errors = array();
        foreach ($caches as $path) {
            try {
                waFiles::delete($path);
            } catch (Exception $ex) {
                $errors[] = str_replace($root_path.DIRECTORY_SEPARATOR, '', $ex->getMessage());
                waFiles::delete($path, true);
            }
        }
        return $errors;
    }

    public static function checkUpdates(&$messages)
    {
        try {
            self::getInstaller()->checkUpdates();
        } catch (Exception $ex) {
            $text = $ex->getMessage();
            $message = array('text' => $text, 'result' => 'fail');
            if (strpos($text, "\n")) {
                $texts = array_filter(array_map('trim', explode("\n", $message['text'])), 'strlen');
                while ($message['text'] = array_shift($texts)) {
                    $messages[] = $message;
                }
            } else {
                $messages[] = $message;
            }
        }

    }

    /**
     *
     * @param array $filter
     * @param array [string]string $filter['extras'] select apps with specified extras type
     * @return array
     * @throws Exception
     */
    public static function getApps($filter = array())
    {
        return self::getInstaller()->getApps(array(), $filter);
    }

    public static function getUpdatesCounter($field = 'total')
    {
        if (empty(self::$counter)) {
            self::getUpdates();
        }
        return $field ? self::$counter[$field] : self::$counter;
    }

    public static function getUpdates($vendor = null)
    {
        static $items = null;
        if ($items === null) {
            self::$counter = array(
                'total'      => 0,
                'applicable' => 0,
                'payware'    => 0,
            );

            $items = self::getInstaller()->getUpdates($vendor);
            foreach ($items as $item) {
                if (isset($item['version'])) {
                    ++self::$counter['total'];
                    if (!empty($item['applicable'])) {
                        ++self::$counter['applicable'];
                    }
                }
                foreach (array('themes', 'plugins') as $extras) {
                    if (isset($item[$extras])) {
                        self::$counter['total'] += count($item[$extras]);
                        foreach ($item[$extras] as $extras_item) {
                            if (!empty($extras_item['applicable'])) {
                                ++self::$counter['applicable'];
                            }
                        }
                    }
                }
            }
            wa('installer')->getConfig()->setCount(self::$counter['total'] ? self::$counter['total'] : null);
        }
        return $items;
    }

    public static function isDeveloper()
    {
        $result = false;
        $paths = array();
        $paths[] = dirname(__FILE__).'/.svn';
        $paths[] = dirname(__FILE__).'/.git';
        $root_path = wa()->getConfig()->getRootPath();
        $paths[] = $root_path.'/.svn';
        $paths[] = $root_path.'/.git';
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     *
     * Search first entry condition
     * @param array $items
     * @param array $filter
     * @param bool $return_key
     * @return mixed
     */
    public static function &search($items, $filter, $return_key = false)
    {
        $matches = array();

        foreach ($items as $key => $item) {
            $matched = true;
            foreach ($filter as $field => $value) {
                if ($value) {
                    if (is_array($value)) {
                        if (!in_array($item[$field], $value)) {
                            $matched = false;
                            break;
                        }
                    } elseif ($item[$field] != $value) {
                        $matched = false;
                        break;
                    }
                }
            }
            if ($matched) {
                $matches[] = $return_key ? $key : $items[$key];
            }
        }
        return $matches;
    }

    /**
     *
     * Compare arrays by specified fields
     * @param array $a
     * @param array $b
     * @param array $fields
     * @return bool
     */
    public static function equals($a, $b, $fields = array('vendor', 'edition'))
    {
        $equals = true;
        foreach ($fields as $field) {
            if (empty($a[$field]) && empty($b[$field])) {
                /*do nothing*/
            } elseif ($a[$field] != $b[$field]) {
                $equals = false;
                break;
            }
        }

        return $equals;
    }

    /**
     * @return string
     */
    public static function getModule()
    {
        $module = 'apps';
        $url = parse_url(waRequest::server('HTTP_REFERER'), PHP_URL_QUERY);
        if (preg_match('/(^|&)module=(update|apps|plugins)($|&)/', $url, $matches)) {
            $module = $matches[2];
        }
        return $module;
    }

    /**
     * @param Exception $ex
     * @param $messages
     * @throws Exception
     */
    private static function handleException($ex, &$messages)
    {
        if ($messages === null) {
            throw $ex;
        } else {
            $messages[] = array('text' => $ex->getMessage(), 'result' => 'fail');
        }
    }
}
