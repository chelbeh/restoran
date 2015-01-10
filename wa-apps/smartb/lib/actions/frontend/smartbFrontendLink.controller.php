<?php

class smartbFrontendLinkController extends waController{
    public function execute()
    {
        $id = waRequest::param('id');
        $image = new smartbImage($id);
        $image = $image->getInfo();
        if($image){
            $click_model = new smartbClickModel();
            $click_model->insert(array(
                'ip' => waRequest::getIp(),
                'user_agent' => waRequest::getUserAgent(),
                'image_id'=>$id,
                'create_datetime'=>date("Y-m-d H:i:s"),
            ));
            $this->redirect($image['url']);
        }
        $this->redirect(wa()->getRootUrl(true));
    }
}