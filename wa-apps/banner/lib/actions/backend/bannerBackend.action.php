<?php 

class bannerBackendAction extends waViewAction
{	
	public function execute()
	{
		$this->view->assign('banners', $this->getConfig()->getBanners(true));
	}
}