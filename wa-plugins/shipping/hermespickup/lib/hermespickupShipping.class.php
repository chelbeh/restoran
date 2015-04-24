<?php

class hermespickupShipping extends waShipping
{

    public function calculate(){
        $region_code = $this->getAddress('region');
        $city = $this->getAddress('city');
        $db_region_points = self::getRegionPoints();
        if ($region_code) {
            if (isset($this->regions[$region_code]['price'])) {
                $price = $this->regions[$region_code]['price'];
                $time = $this->regions[$region_code]['time'];
                if($price>0){
                    //мы доставляем в этот регион
                    $rate = $price;
                    $order_price = $this->getTotalPrice();
                    if($order_price>=$this->free_shipping){
                        $rate = 0;
                    }
                    if(isset($db_region_points[$region_code])){
                        $result = array();
                        foreach($db_region_points[$region_code] as $id=>$params){
                            if($city==''||($city==$params['city'])){
                                $result['point_'.$params['id']] = array(
                                    'est_delivery' => $time,
                                    'currency'     => 'RUB',
                                    'rate'         => $rate,
                                    'name' => self::getComment($params),//$params['id'].' - '.$params['name']." (".$params['address'].")",
                                    'comment' => self::getComment($params),
                                    'force_subrates' => true,
                                    'params' => array(
                                        'subcomment' => self::getSubComment($params),
                                        'id' => $params['id'],
                                        'link' => 'http://pschooser.hermes-dpd.ru/PSChooser/PSDetails?PSId='.$params['id'],
                                    ),
                                );
                            }
                        }
                        ksort($result);
                        return $result;
                    }
                }
            }
        }
        return null;
    }

    private static function getComment($params){
        return self::getSubComment($params);
        $str = '';
        $str .= "<b>Адрес:</b>\n";
        $str .= $params['address']."\n";
        return str_replace("\n", "<br>", $str);
    }

    private static function getSubComment($params){
        return $params['id'].' - '.$params['city']." - ".$params['address'].' - '.$params['name'];
    }

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function tracking($tracking_id = null)
    {
        return $this->myTracking($tracking_id);
    }

    public function getSettingsHTML($params = array()){
        $view = wa()->getView();
        $html = '';
        $view->assign('point_fields', self::getFields());
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    public static function getPointHTML(){
        return "<tr class='point'>".
            "<td><input readonly type='text' value='%name%'></td>".
            "<td><input readonly type='text' value='%city%'></td>".
            "<td><textarea readonly>%address%</textarea></td>".
            "</tr>";
    }

    public static function getFields($for_replace = false){
        $fields = array(
            'name',
            'address',
            'hours',
            'phone',
            'city',
        );
        if($for_replace){
            foreach($fields as &$field){
                $field = '%'.$field.'%';
            }
        }
        return $fields;
    }

    public static function settingRegionControl($name, $params = array()){
        $control = '';
        $db_region_points = self::getRegionPoints();
        $instance = $params['instance'];
        $db_regions = isset($instance->regions)?$instance->regions:array();
        $rm = new waRegionModel();
        if ($regions = $rm->getByCountry('rus')) {

            $point_html = self::getPointHTML();

            $control .= "<table class=\"zebra\"><thead>";
            $string = '<tr><td>%s</td><td class="inp_price">%s</td><td class="inp_time">%s</td></tr><tr><td colspan="3">%s</td></tr>';
            $c_params = array();
            $control .= "<tr class=\"gridsheader\">";
            $control .= "<th>Регион</th>";
            $control .= "<th>Стоимость доставки<br>(0 - доставка не осуществляется)</th>";
            $control .= "<th>Срок доставки</th>";
            $control .= "</tr></thead><tbody>";

            foreach ($regions as $region) {
                $title = $region['name'];
                if ($region['code']) {
                    $title .= " ({$region['code']})";
                }
                $count = 0;
                $points_block = '';
                if(isset($db_region_points[$region['code']])){
                    foreach($db_region_points[$region['code']] as $key=>$point){
                        $replace = array();
                        foreach(self::getFields() as $field){
                            if(isset($point[$field])){
                                $replace[] = $point[$field];
                            }
                            else{
                                $replace[] = '';
                            }
                        }
                        $html = str_replace(self::getFields(true),$replace,$point_html);
                        $html = str_replace('%id%',$key,$html);
                        $html = str_replace('%code%',$region['code'],$html);
                        $points_block .= $html;
                        $count++;
                    }
                }
                $points = '';
                $points .= "<div class='points_block' data-code='{$region['code']}' data-points='{$count}'>";
                $points .= "<table class='points zebra'>";
                $points .= "<tr><th>Название</th><th>Город</th><th>Адрес</th></tr>";
                $points .= $points_block;
                $points .= "</table></div>";
                $c_params['namespace'] = $name."[{$region['code']}]";

                $c_params['value'] = '';
                if(isset($db_regions[$region['code']]['price'])){
                    $c_params['value'] = $db_regions[$region['code']]['price'];
                }
                $price = waHtmlControl::getControl(waHtmlControl::INPUT, 'price', $c_params);

                $c_params['value'] = '';
                if(isset($db_regions[$region['code']]['time'])){
                    $c_params['value'] = $db_regions[$region['code']]['time'];
                }
                $time = waHtmlControl::getControl(waHtmlControl::INPUT, 'time', $c_params);
                $control .= sprintf($string, $title, $price, $time, $points);
            }
            $control .= "</tbody>";
            $control .= "</table>";
        } else {
            $control .= 'Не определено ни одной области. Для работы модуля необходимо определить хотя бы одну область в России (см. раздел «Страны и области»).';
        }
        return $control;
    }

    public function saveSettings($settings = array())
    {

        return parent::saveSettings($settings);
    }

    private function myTracking($barcode){
        $model = new shipmentCodeCheckModel();
        $data = $model->where("barcode = '$barcode'")->order('operation_date, datetime, id')->fetchAll();
        $result = "";
        if(count($data)>0){
            $result = "<table class='tracking_table table'>";
            foreach($data as $line){
                $result .= "<tr>";
                $result .= "<td>".date('d.m.Y H:i', strtotime($line['operation_date']))."</td>";
                $result .= "<td>{$line['operation_place']}</td>";
                $result .= "<td>{$line['operation_type']}</td>";
                $result .= "<td>{$line['operation_text']}</td>";
                $result .= "</tr>";
            }
            $result .= "</table>";
        }
        return $result;
    }

    public function requestedAddressFields(){
        return false;
    }

    private static function getRegionPoints($region_id = 0){
        $data = self::getAllPoints();
        $regions = array();
        foreach($data as $row){
            if(!isset($regions[$row['region']])){
                $regions[$row['region']] = array();
            }
            $regions[$row['region']][] = $row;
        }
        if($region_id>0){
            if(isset($regions[$region_id])){
                return $regions[$region_id];
            }
            return array();
        }
        return $regions;
    }

    private static function getAllPoints(){
        $model = new waModel();
        $data = $model->query("SELECT * FROM shipment_hermes_points")->fetchAll();
        return $data;
    }

    public function mapAction(){
        $view = wa()->getView();
        $view->assign('points', self::getAllPoints());
        $html = $view->fetch($this->path.'/templates/map.html');
        echo $html;
    }
}
