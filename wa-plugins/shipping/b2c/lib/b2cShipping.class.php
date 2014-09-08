<?php

class b2cShipping extends waShipping
{

    public function calculate(){
        $region_id = $this->getAddress('region');
        $region_int = intval($region_id);
        $city = $this->getAddress('city');
        if ($region_id>0) {
            if (isset($this->regions[$region_id]['price'])) {
                $price = $this->regions[$region_id]['price'];
                $time = $this->regions[$region_id]['time'];
                if($price>0){
                    //мы доставляем в этот регион
                    $rate = $price;
                    $order_price = $this->getTotalPrice();
                    if($order_price>=$this->free_shipping){
                        $rate = 0;
                    }
                    $cities = self::getCities();
                    if(isset($cities[$region_int])){
                        if(in_array($city, $cities[$region_int])){
                            $result['delivery'] = array(
                                'est_delivery' => $time,
                                'currency'     => 'RUB',
                                'rate'         => $rate,
                            );
                            return $result;
                        }
                    }
                    if($city==''){
                        $result['delivery'] = array(
                            'est_delivery' => $time,
                            'currency'     => 'RUB',
                            'rate'         => $rate,
                        );
                        return $result;
                    }
                }
            }
        }
        return null;
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
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    public static function getCities(){
        $model = new waModel();
        $data = $model->query("SELECT distinct city, r.code FROM `shipment_b2c_zones` z left join b2c_regions r ON z.region = r.name ORDER BY city")->fetchAll();
        $cities = array();
        foreach($data as $line){
            if(!isset($cities[$line['code']])){
                $cities[$line['code']] = array();
            }
            $cities[$line['code']][] = $line['city'];
        }
        return $cities;
    }

    public static function settingRegionControl($name, $params = array()){
        $control = '';
        $values = $params['value'];
        $rm = new waRegionModel();
        if ($regions = $rm->getByCountry('rus')) {

            $control .= "<table class=\"zebra\"><thead>";
            $string = '<tr><td>%s</td><td class="inp_price">%s</td><td class="inp_time">%s</td></tr>';
            $c_params = array();
            $control .= "<tr class=\"gridsheader\">";
            $control .= "<th>Регион</th>";
            $control .= "<th>Стоимость доставки<br>(0 - доставка не осуществляется)</th>";
            $control .= "<th>Срок доставки</th>";
            $control .= "</tr></thead><tbody>";

            $cities = self::getCities();

            foreach ($regions as $region) {
                $title = $region['name'];
                if ($region['code']) {
                    $title .= " ({$region['code']})";
                }

                $cities_block = '';
                if(isset($cities[intval($region['code'])])){
                    $cities_block .= '<div class="cities">';
                    foreach($cities[intval($region['code'])] as $city){
                        $cities_block .= "<div class='city'>$city</div> ";
                    }
                }
                $cities_block .= '</div>';
                $title .= $cities_block;

                $c_params['namespace'] = $name."[{$region['code']}]";

                $c_params['value'] = '';
                if(isset($values[$region['code']]['price'])){
                    $c_params['value'] = $values[$region['code']]['price'];
                }
                $price = waHtmlControl::getControl(waHtmlControl::INPUT, 'price', $c_params);

                $c_params['value'] = '';
                if(isset($values[$region['code']]['time'])){
                    $c_params['value'] = $values[$region['code']]['time'];
                }
                $time = waHtmlControl::getControl(waHtmlControl::INPUT, 'time', $c_params);
                $control .= sprintf($string, $title, $price, $time);
            }
            $control .= "</tbody>";
            $control .= "</table>";
        } else {
            $control .= 'Не определено ни одной области. Для работы модуля необходимо определить хотя бы одну область в России (см. раздел «Страны и области»).';
        }
        return $control;
    }

    private function myTracking($barcode){
        $model = new shipmentCodeCheckModel();
        $data = $model->where("barcode = '$barcode'")->order('operation_date, datetime, id')->fetchAll();
        $result = "";
        if(count($data)>0){
            $result = "<table class='tracking_table'>";
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
}
