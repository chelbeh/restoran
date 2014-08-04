<?php
  
class bannerViewHelper
{
  
    public function getBanner($banner_id)
    {
        $html = "";
        $banner_model = new bannerBannersModel();
        $banner_options = $banner_model->getById($banner_id);
        
        $item_model = new bannerItemsModel();
        $data['banner_id'] = $banner_id;
        $data['on'] = 1;
        $items = $item_model->getByField($data, true);

        if (!empty($items)) {
            $banner = $items[array_rand($items, 1)];
            
            $attr = "";
            $target_blank = "";
            
            if ($banner_options['width'] > 0) {
                $attr .= " width='$banner_options[width]' ";
            }
            if ($banner_options['height'] > 0) {
                $attr .= " height='$banner_options[height]' ";
            }
            
            if ($banner['new_window'] > 0) {
                $target_blank = " target='_blank' ";
            }
            
            $banner['title'] = addslashes($banner['title']);
            $html = "<a href='$banner[link]' title='$banner[title]' $target_blank ><img src='$banner[url]' alt='$banner[alt]' $attr></a>";
        } 
        return $html;
        
    }
  
}