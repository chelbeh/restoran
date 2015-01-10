<?php

class smartbSidebarSmartbAction extends waViewAction
{
    public function execute(){
        $this->view->assign('banners', smartbBanner::getAll());
    }
}