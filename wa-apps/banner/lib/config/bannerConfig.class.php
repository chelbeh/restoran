<?php 

class bannerConfig extends waAppConfig
{

	protected $banners = null;

	public function getBanners()
	{
		if ($this->banners === null) {
            $banners_model = new bannerBannersModel();
			$this->banners = $banners_model->getAll('id');
		}

        return $this->banners;
	}

	public function getItems($banner)
	{
        $items_model = new bannerItemsModel();
        $field = array('banner_id'=>$banner);
    	$items = $items_model->getByField($field, true);

        return $items;
	}
    
    public function getBanner($id)
	{
        $banners_model = new bannerBannersModel();
		$banner = $banners_model->getById($id);

        return $banner;
	}
}