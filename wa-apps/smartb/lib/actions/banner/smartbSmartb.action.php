<?php
class smartbSmartbAction extends waViewAction
{
	public function execute()
	{
        $id = waRequest::get('id');
        if($id){
            $banner = new smartbBanner($id);
            if($banner->exist()){
                if(waRequest::post('save_smartb')){
                    $banner->save(waRequest::post());
                    $this->redirect('?module=smartb&id='.$banner->getId());
                }
                if(waRequest::post('save_images')){
                    $banner->saveImages(waRequest::post());
                    $this->redirect('?module=smartb&id='.$banner->getId());
                }
                if(waRequest::post('delete_smartb')){
                    $banner->delete();
                    $this->redirect('?module=smartb');
                }
                $images = $banner->getImages();
                $url = wa()->getRouteUrl('smartb/frontend/link/', array('id' => '%id%'), true);
                if(!$url){
                    $this->view->assign('url_error', true);
                }
                $info = $banner->getInfo();
                smartbBanner::addCTR($info, $images, $banner->getCTR());
                $this->view->assign('banner', $info);
                $this->view->assign('images', $images);
                $this->view->assign('animation_in', smartbBanner::getAvailableAnimationIn());
                $this->view->assign('animation_out', smartbBanner::getAvailableAnimationOut());
                $this->view->assign('animation_speeds', smartbBanner::getAvailableAnimationSpeeds());
                $this->view->assign('sizes', smartbBanner::getAvailableSizes());
                $this->getResponse()->setTitle($info['title']);
                return;
            }
            $this->redirect('?module=smartb');
        }
        else{
            $banners = smartbBanner::getAll();
            if(count($banners)==0){
                $this->redirect('?module=smartb&action=create');
            }
            else{
                foreach($banners as $b){
                    $this->redirect('?module=smartb&id='.$b['id']);
                }
            }
        }
	}
}
