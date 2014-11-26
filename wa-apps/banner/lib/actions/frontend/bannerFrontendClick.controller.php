<?php

class bannerFrontendClickController extends waController
{

    public function execute()
    {
        



        $item_id = waRequest::param('id', 0, 'int');
                if (!$item_id) {
                    throw new waException(_w('Page not found', 404));
                }
        
        $banner_model = new bannerBannersModel();
        $item_model = new bannerItemsModel();
        
        $item_param = $item_model->getById($item_id);
        $banner_param = $banner_model->getById($item_param['banner_id']);
        
        $new_item_data['click'] = $item_param['click'] + 1;
        $new_banner_data['click'] = $banner_param['click'] + 1;
        
        $item_model->updateById($item_id, $new_item_data);
        $banner_model->updateById($item_param['banner_id'], $new_banner_data);
        $this->redirect($item_param['link']);
    }
}