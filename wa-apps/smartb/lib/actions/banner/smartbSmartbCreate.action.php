<?php
class smartbSmartbCreateAction extends waViewAction
{
	public function execute()
	{
        if(waRequest::post('save_banner')){
            $banner = smartbBanner::create();
            $banner->save(waRequest::post());
            $this->redirect('?module=smartb&id='.$banner->getId());
        }
        $this->getResponse()->setTitle(_w('Новый баннер'));
	}
}
