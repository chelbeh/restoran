<?php

class bannerBackendBannersAction extends waViewAction
{
	public function execute()
	{
		$banners = $this->getConfig()->getBanners();
		$this->view->assign('banners', $banners);
	}
}