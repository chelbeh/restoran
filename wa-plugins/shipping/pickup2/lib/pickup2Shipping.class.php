<?php

class pickup2Shipping extends waShipping
{

    public function calculate(){
        $region_id = $this->getAddress('region');
        if ($region_id) {
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
                    if(isset($this->regions[$region_id]['points'])){
                        $result = array();
                        foreach($this->regions[$region_id]['points'] as $id=>$name){
                            $result['point_'.$id] = array(
                                'est_delivery' => $time,
                                'currency'     => 'RUB',
                                'rate'         => $rate,
                                'name' => $name,
                            );
                        }
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

            foreach ($regions as $region) {
                $title = $region['name'];
                if ($region['code']) {
                    $title .= " ({$region['code']})";
                }
                $count = 0;
                $points_block = '';
                if(isset($values[$region['code']]['points'])){
                    foreach($values[$region['code']]['points'] as $key=>$value){
                        $html = "<div class='point'>";
                        $html .= $value;
                        $html .= "<input type='hidden' name='shipping[settings][regions][{$region['code']}][points][$key]' value='{$value}'>";
                        $html .= " <a class='delete_point' href='#'><i class='icon16 delete'></i></a>";
                        $html .= "</div>";
                        $points_block .= $html;
                        $count = $key;
                    }
                }
                $points_block = "<div class='points_block' data-code='{$region['code']}' data-points='{$count}'><a href='#' class='add_point'><i class='icon16 add'></i> добавить пункт самовывоза</a><div class='points'>".$points_block."</div></div>";
                $title .= $points_block;
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

    public function saveSettings($settings = array())
    {

        return parent::saveSettings($settings);
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
}
