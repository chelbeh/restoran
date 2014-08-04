<?php 

class bannerBackendAddController extends waController
{
	public function execute()
	{
        $data['title'] = htmlspecialchars(waRequest::post('title'), ENT_QUOTES);
        $data['width'] = waRequest::post('width', 0, 'int');
        $data['height'] = waRequest::post('height', 0, 'int');

        $banners_model = new bannerBannersModel();
        if (!empty($data['title'])) {
            $banners_model -> insert($data);
        }
        
        $this->redirect('./');
	}

}