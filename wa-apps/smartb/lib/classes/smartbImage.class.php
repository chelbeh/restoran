<?php

class smartbImage
{
    private $id;
    private $fields;
    private $model;
    private $info;
    private $exist;
    private $banner_id;

    public function __construct($id){
        $this->id = $id;
        $this->fields = array(
            'url',
            'alt',
            'disabled',
            'sort'
        );
        $this->model = new smartbImageModel();
        $this->getInfo();
    }

    public function getId(){
        return $this->id;
    }

    public function exist(){
        return $this->exist;
    }

    public function save($data){
        if(isset($data['url'])){
            $data['url'] = trim($data['url']);
            if($data['url']==''){
                $data['url'] = wa()->getRootUrl(true);
            }
        }
        $update_array = array();
        $old_fields = $this->info;
        foreach($this->fields as $field){
            if(isset($data[$field])){
                $new_array[$field] = $data[$field];
            }
        }
        foreach($this->fields as $field){
            if(isset($new_array[$field])){
                if($new_array[$field]!=$old_fields[$field]){
                    $update_array[$field] = $new_array[$field];
                }
            }
        }
        $this->model->updateById($this->id, $update_array);
    }

    public function getInfo(){
        if(!$this->info){
            $this->info = $this->model->getById($this->id);
            if($this->info){
                $this->exist = true;
                $this->banner_id = $this->info['banner_id'];
            }
        }
        return $this->info;
    }

    public function delete(){
        $this->model->deleteById($this->id);
    }

    public function scale(){
        $config = wa('smartb')->getConfig();
        $banner = new smartbBanner($this->banner_id);
        $sizes = $banner->getSizes();
        $image = self::generateThumb(self::getPath($this->info), $sizes);
        if ($image) {
            $thumb_path = self::getThumbsPath($this->info, $sizes);
            if ((file_exists($thumb_path) && !is_writable($thumb_path)) || (!file_exists($thumb_path) && !waFiles::create($thumb_path))) {
                throw new waException(
                    sprintf("The insufficient file write permissions for the %s folder.",
                        $thumb_path
                    ));
            }
            $image->save($thumb_path, $config->getOption('image_quality'));
        }
        else{
        }
    }

    public function getThumbUrl(){
        $banner = new smartbBanner($this->banner_id);
        $sizes = $banner->getSizes();
        return self::getThumbsUrl($this->info, $sizes);
    }

    //=====

    public static function create($banner_id, waRequestFile $file){
        if (!($image = $file->waImage())) {
            throw new waException('Incorrect image');
        }
        $model = new smartbImageModel();
        $sort = $model->select("MAX(sort)")->where("banner_id = '$banner_id'")->fetchField()+1;
        $data = array(
            'banner_id'         => $banner_id,
            'upload_datetime'   => date('Y-m-d H:i:s'),
            'original_filename' => basename($file->name),
            'ext'               => $file->extension,
            'sort'              => $sort,
            'url'              =>  wa()->getRootUrl(true),
        );
        $image_id = $data['id'] = $model->insert($data);
        if (!$image_id) {
            throw new waException("Database error");
        }

        $image_path = self::getPath($data);
        if ((file_exists($image_path) && !is_writable($image_path)) || (!file_exists($image_path) && !waFiles::create($image_path))) {
            $model->deleteById($image_id);
            throw new waException(
                sprintf("The insufficient file write permissions for the %s folder.",
                    $image_path
                ));
        }
        $image->save($image_path);
        $image = new smartbImage($image_id);
        return $image;
    }

    public static function getPath($image){
        $folder = wa()->getDataPath(smartbBanner::getFolder($image['banner_id']), true, 'smartb');
        return $folder."images/{$image['id']}.{$image['ext']}";
    }

    public static function getThumbsPath($image, $sizes){
        $folder = wa()->getDataPath(smartbBanner::getFolder($image['banner_id']), true, 'smartb');
        return $folder."{$sizes['width']}x{$sizes['height']}/{$image['id']}.{$image['ext']}";
    }

    public static function getThumbsUrl($image, $sizes){
        $folder = wa()->getDataUrl(smartbBanner::getFolder($image['banner_id']), true, 'smartb', true);
        return $folder."{$sizes['width']}x{$sizes['height']}/{$image['id']}.{$image['ext']}";
    }

    public static function generateThumb($src_image_path, $sizes)
    {
        $image = waImage::factory($src_image_path);
        $width = $sizes['width'];
        $height = $sizes['height'];
        if($sizes['scale']){
            $image->resize($width, $height);
        }
        return $image;
    }

    public static function processImageInfo($banner, &$image){
        $image['url_thumb'] = self::getThumbsUrl($image, $banner->getSizes());
        $url = wa()->getRouteUrl('smartb/frontend/link/', array('id' => '%id%'), true);
        $image['link'] = $image['url'];
        if($url){
            $image['link'] = str_replace('%id%', $image['id'], $url);
        }
    }

    public static function addClicks(&$images){
        $ids = array();
        foreach($images as $image){
            $ids[] = $image['id'];
        }
        if(count($ids)==0) return;
        $model = new smartbClickModel();
        $sql = 'SELECT image_id, count(id) as c FROM `smartb_click` where image_id IN ('.implode(',', $ids).') group by image_id';
        $clicks = $model->query($sql)->fetchAll('image_id');
        foreach($images as &$image){
            $image['clicks'] = isset($clicks[$image['id']]['c'])?$clicks[$image['id']]['c']:0;
        }
    }
}