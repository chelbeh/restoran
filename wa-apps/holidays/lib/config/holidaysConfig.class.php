<?php
/**
 * Custom app config implements slightly different autoload search logic
 * so that we don't need app prefix for all .php files.
 */
class holidaysConfig extends waAppConfig
{
    // This allows to specify .php files without app prefix
    protected function getClassByFilename($filename)
    {
        $prefix = $this->getPrefix();
        $class = parent::getClassByFilename($filename);
        if (substr($class, 0, strlen($prefix)) !== $prefix) {
            $class = $prefix.$class;
        }
        return $class;
    }
}

