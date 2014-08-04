<?php 

class bannerBackendLoadController extends waJsonController
{
	public function execute()
	{

        $banner_id = waRequest::post('banner_id');
        if ($banner_id > 0) {
            $banner_url = $this->bannerSave();
            if ($banner_url) {
                $data['url'] = $banner_url;
                $data['banner_id'] = $banner_id;
                $data['link'] = htmlspecialchars(waRequest::post('link'), ENT_QUOTES);
                $data['alt'] = htmlspecialchars(waRequest::post('alt'), ENT_QUOTES);
                $data['title'] = htmlspecialchars(waRequest::post('title'), ENT_QUOTES);
                $data['on'] = waRequest::post('on', 0, 'int');
                $data['new_window'] = waRequest::post('new_window', 0, 'int');

                $banner_model = new bannerItemsModel();

                $banner_model -> insert($data);
            }   
            
        }
        
        $this->redirect("banner#/banner/$banner_id");     
        
	}
    
    private function bannerSave() {
        
        $file = waRequest::file('banner_img');
        
        if ($file->uploaded()) {
            
            try {
                $file->waImage();
            } catch(Exception $e) {
                echo _w("The file is not an image, or another error occurred: ").$e->getMessage();
                return;
            }

            $path = wa()->getDataPath('data/banner/', true);
            
            if (!file_exists($path) || !is_writable($path)) {
                $this->errors = _w('File could not be saved due to the insufficient file write permissions for the upload folder.'); 
                return;
            } elseif (!$file->moveTo($path, $file->name)) {
                $this->errors = _w('Failed to upload file').$file->error;    
                return ;
            } else {
                return wa()->getDataUrl('data/banner/'.$file->name, TRUE, 'banner', true);
            }

        } elseif ($file->error_code != UPLOAD_ERR_NO_FILE) {
            $this->errors = $file->error;
        }
        
    }
}