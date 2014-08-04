<?php 

class bannerBackendBannerDelController extends waJsonController
{
	public function execute()
	{        
        $banner_id = waRequest::post('banner_id');
        if ($banner_id > 0) {
            $item_model = new bannerItemsModel();
            $banner_model = new bannerBannersModel();
            $item_model -> deleteByField('banner_id', $banner_id);
            $banner_model -> deleteById($banner_id);
        }
        
        $this->redirect("./");
	}
    
}