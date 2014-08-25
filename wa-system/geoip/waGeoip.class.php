<?php

class Geoip {
    private $model;
    public function __construct(){
        $this->model = new GeoipModel();
    }

    public function getIP(){
        $ip = false;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipa[] = trim(strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ','));
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipa[] = $_SERVER['HTTP_CLIENT_IP'];
        if (isset($_SERVER['REMOTE_ADDR']))
            $ipa[] = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_REAL_IP']))
            $ipa[] = $_SERVER['HTTP_X_REAL_IP'];
        foreach ($ipa as $ips) {
            if ($this->isValidIP($ips)) {
                $ip = $ips;
                break;
            }
        }
        return $ip;
    }

    private function isValidIP($ip = null)    {
        if (preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip))
            return true;
        return false;
    }

    public function getGeoData(){
        $ip = $this->getIP();
        if($ip=='127.0.0.1'){
            $ip = '188.244.45.3'; // test
        }
        $data = $this->model->getData($ip);
        return $data;
    }

    public function getCity(){
        $cookie_sity = waRequest::cookie('city');
        if($cookie_sity) return $cookie_sity;
        $data = $this->getGeoData();
        if(isset($data['city'])) return $data['city'];
        return '';
    }

    public function getRegion(){
        //return "48";
        $cookie_region = waRequest::cookie('region');
        if($cookie_region) return $cookie_region;
        $data = $this->getGeoData();
        if(isset($data['region_id'])) return $data['region_id'];
        return '';
    }

    public function getRegionName($id){
        return $this->model->query("SELECT name FROM wa_region WHERE country_iso3 = 'rus' AND code = '$id'")->fetchField();
    }

    public function getData(){
        $city = '';
        $region = 0;
        $cookie_sity = waRequest::cookie('city');
        $cookie_region = waRequest::cookie('region');
        if($cookie_sity) {
            $city = $cookie_sity;
        }
        if($cookie_region) {
            $region = $cookie_region;
        }
        if(($city=='')||($region==0)){
            $data = $this->getGeoData();
            if(isset($data['city'])) {
                $city = $data['city'];
            }
            if(isset($data['region_id'])) {
                $region = $data['region_id'];
            }
        }
        if($region>0)
            return array('city'=>$city, 'region'=>$region);
        return null;
    }

    public function saveData($data){
        if(isset($data['address.shipping']['region'])&&($data['address.shipping']['region']>0)){
            wa()->getResponse()->setCookie('region', $data['address.shipping']['region'], time() + 365 * 86400, null, '/', false, false);
        }
        if(isset($data['address.shipping']['city'])&&($data['address.shipping']['city']!='')){
            wa()->getResponse()->setCookie('city', $data['address.shipping']['city'], time() + 365 * 86400, null, '/', false, false);
        }
    }

    public function allRegions(){
        return $this->model->query("SELECT code, name FROM wa_region WHERE country_iso3 = 'rus' ORDER BY fav_sort IS NULL, fav_sort, name")->fetchAll();
    }

    public function allAreas(){
        return $this->model->query("SELECT * FROM geo_area ORDER BY name")->fetchAll();
    }

    public function getCities($string, $region = 0){
        $cities = array();
        $sql = "SELECT name FROM geo_citi WHERE name LIKE '".$this->model->escape($string)."%' AND area_code = '$region' GROUP BY name";
        $data = $this->model->query($sql)->fetchAll();
        foreach($data as $line){
            $cities[] = $line['name'];
        }
        return $cities;
    }
}