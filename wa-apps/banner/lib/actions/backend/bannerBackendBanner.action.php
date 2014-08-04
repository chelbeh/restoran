<?php 

class bannerBackendBannerAction extends waViewAction
{
	public function execute()
	{
		$id = waRequest::get('id', 0, 'int');
		$images = $this->getConfig()->getItems($id); 
        $banner = $this->getConfig()->getBanner($id); 
		
		if (isset($images)) {
            $this->view->assign('items', $images);
            $this->view->assign('banner_id', $id);
            $this->view->assign('banner', $banner);
            $this->view->assign('code', '{$wa->banner->getBanner('.$banner['id'].')}');
            return;
		} else {
			throw new waException("The requested banner was not found.", 404);
		}
	}
}