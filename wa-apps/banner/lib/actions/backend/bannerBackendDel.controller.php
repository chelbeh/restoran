<?php 

class bannerBackendDelController extends waJsonController
{
	public function execute()
	{
        
        $item_id = waRequest::post('item_id');
        $banner_id = waRequest::post('banner_id');
        if ($item_id > 0) {
            $item_model = new bannerItemsModel();
            $item_model -> deleteById($item_id);
        }
        
        $this->redirect("banner#/banner/$banner_id");     
	}
    
}