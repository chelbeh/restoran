<?php

class smartbSmartbUploadController extends waJsonController{
    public function execute()
    {
        $this->response['files'] = array();
        $files = waRequest::file('image');
        foreach ($files as $file) {
            if ($file->error_code != UPLOAD_ERR_OK) {
                $this->response['files'][] = array(
                    'error' => $file->error
                );
            } else {
                try {
                    $this->response['files'][] = $this->save($file);
                } catch (Exception $e) {
                    $this->response['files'][] = array(
                        'name'  => $file->name,
                        'error' => $e->getMessage()
                    );
                }
            }
        }
    }
    private function save(waRequestFile $file){
        $banner_id = waRequest::get('id');
        $banner = new smartbBanner($banner_id);

        if(!$banner->exist()){
            throw new waException('Incorrect banner');
        }
        // check image
        if (!($image = $file->waImage())) {
            throw new waException('Incorrect image');
        }
        unset($image);
        $image = smartbImage::create($banner_id, $file);
        $image->scale();
        $info = $image->getInfo();
        $info['url_thumb'] = $image->getThumbUrl();

        return $info;
    }
}