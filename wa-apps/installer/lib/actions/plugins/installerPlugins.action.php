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

class installerPluginsAction extends installerItemsAction
{
    protected $module = 'plugins';


    protected function getExtrasOptions()
    {
        $options = parent::getExtrasOptions();
        $options['local'] = true;
        return $options;
    }

    protected function getAppOptions()
    {
        return parent::getAppOptions() + array('system' => true);
    }
}
//EOF
