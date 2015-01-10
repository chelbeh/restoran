<?php

class smartbViewHelper
{
    public static function banner($id){
        $banner = new smartbBanner($id);
        $info = $banner->getInfo();
        if(!$info) return '';
        $images = $banner->getImages(true);
        if(count($images)==0) return '';
        $banner->addView();
        $view = wa()->getView();
        $uniq = mt_rand(1, 100000);
        $view->assign('banner', $info);
        $view->assign('images', $images);
        $view->assign('uniq', $uniq);
        if(count($images)>1){
            $template_path = wa()->getAppPath('templates/actions/frontend/Banner.html', 'smartb');
        }
        else{
            $template_path = wa()->getAppPath('templates/actions/frontend/BannerOne.html', 'smartb');
        }
        $html = $view->fetch($template_path);
        return $html;
    }
}