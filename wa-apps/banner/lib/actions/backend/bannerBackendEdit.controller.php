<?php 

class bannerBackendEditController extends waJsonController
{
	public function execute()
	{
        
        $item_id = waRequest::post('item_id');
        $data = array();
        $data['on'] = waRequest::post('on', 0, 'int');
        if ($item_id > 0) {
            $item_model = new bannerItemsModel();
            $item_model -> updateById($item_id, $data);
        }
        
	}
    
}