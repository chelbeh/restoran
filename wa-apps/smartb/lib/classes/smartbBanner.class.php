<?php

class smartbBanner
{
    private $id;
    private $fields;
    private $model;
    private $info;
    private $exist;

    public function __construct($id){
        $this->id = $id;
        $this->fields = array(
            'width',
            'height',
            'title',
            'scale',
            'params',
        );
        $this->model = new smartbBannerModel();
        $this->getInfo();
    }

    public function getId(){
        return $this->id;
    }

    public function exist(){
        return $this->exist;
    }

    public function save($data){
        $update_array = array();
        $old_fields = $this->info;
        foreach($this->fields as $field){
            if(isset($data[$field])){
                $new_array[$field] = $data[$field];
            }
        }
        if(isset($new_array['params'])){
            $new_array['params'] = json_encode($new_array['params']);
        }
        $new_array['scale'] = isset($new_array['scale'])?1:0;
        foreach($this->fields as $field){
            if(isset($new_array[$field])){
                if($new_array[$field]!=$old_fields[$field]){
                    $update_array[$field] = $new_array[$field];
                }
            }
        }
        $this->model->updateById($this->id, $update_array);
        if(isset($update_array['width'])||isset($update_array['height'])){
            $this->scaleImages();
        }
    }

    public function getInfo(){
        if(!$this->info){
            $this->info = $this->model->getById($this->id);
            if($this->info){
                self::processBannerInfo($this->info);
                $this->exist = true;
            }
        }
        return $this->info;
    }

    public function getImages($enabled = false){
        $model = new smartbImageModel();
        $d = $model->where("banner_id = '$this->id'");
        if($enabled){
            $d = $d->where("disabled = '0'");
        }
        $images = $d->order('sort, id')->fetchAll();
        foreach($images as &$image){
            smartbImage::processImageInfo($this, $image);
        }
        return $images;
    }

    private function scaleImages(){
        $images = $this->getImages();
        foreach($images as $row){
            $image = new smartbImage($row['id']);
            $image->scale();
        }
    }

    public function delete(){
        $this->model->deleteById($this->id);
    }

    public function getSizes(){
        return array(
            'width' => $this->info['width'],
            'height' => $this->info['height'],
            'scale' => $this->info['scale'],
        );
    }

    public function saveImages($data){
        if(isset($data['image_params'])){
            $data = $data['image_params'];
        }
        else{
            $data = array();
        }
        $images = $this->getImages();
        foreach($images as $row){
            $image = new smartbImage($row['id']);
            if(isset($data[$row['id']])){
                $image->save($data[$row['id']]);
            }
            else{
                $image->delete();
            }
        }
    }

    public function addView(){
        if(wa()->getEnv()=='frontend'){
            $click_model = new smartbViewModel();
            $click_model->insert(array(
                'ip' => waRequest::getIp(),
                'banner_id' => $this->id,
                'user_agent' => waRequest::getUserAgent(),
                'url' => 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}",
                'create_datetime'=>date("Y-m-d H:i:s"),
            ));
        }
    }

    public function getCTR(){
        $views_model = new smartbViewModel();
        $clicks_model = new smartbClickModel();
        $images_model = new smartbImageModel();
        $images = $images_model->where("banner_id = '$this->id'")->fetchAll('id');
        $result = array();
        $result['views'] = 0;
        $result['clicks'] = 0;
        $result['CTR'] = 0;
        $result['images'] = array();
        if(count($images)==0) return $result;
        $views = $views_model->select('COUNT(*)')->where("banner_id = '{$this->id}'")->fetchField();
        if($views==0) return $result;
        $sql = 'SELECT image_id, count(id) as c FROM `smartb_click` where image_id IN ('.implode(',', array_keys($images)).') group by image_id';
        $clicks = $clicks_model->query($sql)->fetchAll('image_id');
        foreach($images as &$image){
            $im = array('clicks'=>0, 'CTR'=>0);
            if(isset($clicks[$image['id']])){
                $im['clicks'] = $clicks[$image['id']]['c'];
                $im['CTR'] = round(($im['clicks']/$views)*100*100)/100;
                $result['clicks'] += $im['clicks'];
            }
            $result['images'][$image['id']] = $im;
        }
        $result['views'] = $views;
        $result['CTR'] = round(($result['clicks']/$views)*100*100)/100;
        return $result;
    }

    //=====

    public static function create(){
        $model = new smartbBannerModel();
        $id = $model->insert(array('create_datetime'=>date("Y-m-d H:i:s"), 'params'=>''));
        return new self($id);
    }

    public static function getAll(){
        $model = new smartbBannerModel();
        $banners = $model->order('title, create_datetime')->fetchAll('id');
        foreach($banners as &$banner){
            self::processBannerInfo($banner);
        }
        return $banners;
    }

    private static function processBannerInfo(&$banner){
        if(trim($banner['title'])==''){
            $banner['title'] = "Баннер {$banner['id']}";
        }
        $db_params = json_decode($banner['params'], true);
        $params = array();
        foreach(self::getParams() as $key=>$value){
            if(isset($db_params[$key])){
                $params[$key] = $db_params[$key];
            }
            else{
                $params[$key] = $value;
            }
        }
        $banner['params'] = $params;
    }

    public static function getFolder($banner_id){
        $str = str_pad($banner_id, 4, '0', STR_PAD_LEFT);
        $path = 'smartb/'.substr($str, -2).'/'.substr($str, -4, 2)."/$banner_id/";
        return $path;
    }

    public static function getAvailableAnimationIn(){
        return array(
          'bounceIn' => _w('bounce'),
          'bounceInDown' => _w('bounceDown'),
          'bounceInLeft' => _w('bounceLeft'),
          'bounceInRight' => _w('bounceRight'),
          'bounceInUp' => _w('bounceUp'),

          'fadeIn' => _w('fade'),
          'fadeInDown' => _w('fadeDown'),
          'fadeInDownBig' => _w('fadeDownBig'),
          'fadeInLeft' => _w('fadeLeft'),
          'fadeInLeftBig' => _w('fadeLeftBig'),
          'fadeInRight' => _w('fadeRight'),
          'fadeInRightBig' => _w('fadeRightBig'),
          'fadeInUp' => _w('fadeUp'),
          'fadeInUpBig' => _w('fadeUpBig'),

          'flipInX' => _w('flipX'),
          'flipInY' => _w('flipY'),

          'lightSpeedIn' => _w('lightSpeed'),

          'rotateIn' => _w('rotate'),
          'rotateInDownLeft' => _w('rotateDownLeft'),
          'rotateInDownRight' => _w('rotateDownRight'),
          'rotateInUpLeft' => _w('rotateUpLeft'),
          'rotateInUpRight' => _w('rotateUpRight'),

          'rollIn' => _w('roll'),

          'zoomIn' => _w('zoom'),
          'zoomInDown' => _w('zoomDown'),
          'zoomInLeft' => _w('zoomLeft'),
          'zoomInRight' => _w('zoomRight'),
          'zoomInUp' => _w('zoomUp'),
        );
    }

    public static function getAvailableAnimationOut(){
        return array(
            'bounceOut' => _w('bounce'),
            'bounceOutDown' => _w('bounceDown'),
            'bounceOutLeft' => _w('bounceLeft'),
            'bounceOutRight' => _w('bounceRight'),
            'bounceOutUp' => _w('bounceUp'),

            'fadeOut' => _w('fade'),
            'fadeOutDown' => _w('fadeDown'),
            'fadeOutDownBig' => _w('fadeDownBig'),
            'fadeOutLeft' => _w('fadeLeft'),
            'fadeOutLeftBig' => _w('fadeLeftBig'),
            'fadeOutRight' => _w('fadeRight'),
            'fadeOutRightBig' => _w('fadeRightBig'),
            'fadeOutUp' => _w('fadeUp'),
            'fadeOutUpBig' => _w('fadeUpBig'),

            'flipOutX' => _w('flipX'),
            'flipOutY' => _w('flipY'),

            'lightSpeedOut' => _w('lightSpeed'),

            'rotateOut' => _w('rotate'),
            'rotateOutDownLeft' => _w('rotateDownLeft'),
            'rotateOutDownRight' => _w('rotateDownRight'),
            'rotateOutUpLeft' => _w('rotateUpLeft'),
            'rotateOutUpRight' => _w('rotateUpRight'),

            'rollOut' => _w('roll'),

            'zoomOut' => _w('zoom'),
            'zoomOutDown' => _w('zoomDown'),
            'zoomOutLeft' => _w('zoomLeft'),
            'zoomOutRight' => _w('zoomRight'),
            'zoomOutUp' => _w('zoomUp'),
        );
    }

    public static function getAvailableAnimationSpeeds(){
        return array(
            '300' => _w("Very fast"),
            '700' => _w("Fast"),
            '1000' => _w("Regular"),
            '1500' => _w("Slow"),
            '2000' => _w("Very slow"),
        );
    }

    public static function getAvailableSizes(){
        return array(
            'none' => _w("None"),
            'small' => _w("Small buttons"),
            'normal' => _w("Normal buttons"),
            'big' => _w("Big buttons"),
        );
    }

    private static function getParams(){
        return array(
            'animation_in' => 'fadeInRight',
            'animation_out' => 'fadeOutLeft',
            'animation_speed' => '1000',
            'time' => '5000',
            'arrow_buttons' => 'normal',
            'navigation' => 'normal',
            'button_color' => '000000',
            'button_background' => '999999',
        );
    }

    public static function addCTR(&$banner_info, &$images, $ctr){
        $banner_info['CTR'] = $ctr['CTR'];
        $banner_info['views'] = $ctr['views'];
        $banner_info['clicks'] = $ctr['clicks'];
        foreach($images as &$image){
            if(isset($ctr['images'][$image['id']])){
                $image['clicks'] = $ctr['images'][$image['id']]['clicks'];
                $image['CTR'] = $ctr['images'][$image['id']]['CTR'];
            }
            else{
                $image['clicks'] = 0;
                $image['CTR'] = 0;
            }
        }
    }
}