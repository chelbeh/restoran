<?php

class installerBackendSetController extends waController
{
    public function execute()
    {
        $name = 'banners';
        $apps = installerHelper::getInstaller();
        $apps->installWebAsystItem($name, null, true);
        wa($name)->getConfig()->install();
        echo "OK";
    }
}
//EOF
