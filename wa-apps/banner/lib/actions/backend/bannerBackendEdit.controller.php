<?php 

class bannerBackendEditController extends waJsonController
{
	public function execute()
	{
        
        $item_id = waRequest::post('item_id', 0, 'int');
        $banner_id = waRequest::post('banner_id', 0, 'int');
        
        $data = array();
        $data = waRequest::post('values');
        
        if ($item_id > 0) {
            $item_model = new bannerItemsModel();
            $item_model -> updateById($item_id, $data);
        } elseif ($banner_id > 0) {
            $banner_model = new bannerBannersModel();
            $banner_model -> updateById($banner_id, $data);
        }
        
	}
    
}