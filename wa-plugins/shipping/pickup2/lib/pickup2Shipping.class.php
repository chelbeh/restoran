<?php

class pickup2Shipping extends waShipping
{

    public function calculate(){
        $region_id = $this->getAddress('region');
        $city = $this->getAddress('city');
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
                        foreach($this->regions[$region_id]['points'] as $id=>$params){
                            if($city==''||($city==$params['city'])){
                                $result['point_'.$id] = array(
                                    'est_delivery' => $time,
                                    'currency'     => 'RUB',
                                    'rate'         => $rate,
                                    'name' => $params['name']." (".$params['address'].")",
                                    'comment' => self::getComment($params),
                                );
                            }
                        }
                        return $result;
                    }
                }
            }
        }
        return null;
    }

    private static function getComment($params){
        $str = '';
        $str .= "<b>Адрес:</b>\n";
        $str .= $params['address']."\n";
        $str .= "<b>Время работы:</b>\n";
        $str .= $params['hours']."\n";
        $str .= "<b>Телефон:</b>\n";
        $str .= $params['phone']."\n";
        return str_replace("\n", "<br>", $str);
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
        $view->assign('point_template', self::getPointHTML());
        $view->assign('point_fields', self::getFields());
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    public static function getPointHTML(){
        return "<tr class='point'>".
            "<td><input type='text' name='shipping[settings][regions][%code%][points][%id%][name]' value='%name%'></td>".
            "<td><input type='text' name='shipping[settings][regions][%code%][points][%id%][city]' value='%city%'></td>".
            "<td><textarea name='shipping[settings][regions][%code%][points][%id%][address]'>%address%</textarea></td>".
            "<td><textarea name='shipping[settings][regions][%code%][points][%id%][hours]'>%hours%</textarea></td>".
            "<td><textarea name='shipping[settings][regions][%code%][points][%id%][phone]'>%phone%</textarea></td>".
            "<td><a href='#' class='delete_point'><i class='icon16 delete'></a></td>".
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
        $values = $params['value'];
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
                if(isset($values[$region['code']]['points'])){
                    foreach($values[$region['code']]['points'] as $key=>$params){
                        $replace = array();
                        foreach(self::getFields() as $field){
                            if(isset($params[$field])){
                                $replace[] = $params[$field];
                            }
                            else{
                                $replace[] = '';
                            }
                        }
                        $html = str_replace(self::getFields(true),$replace,$point_html);
                        $html = str_replace('%id%',$key,$html);
                        $html = str_replace('%code%',$region['code'],$html);
                        $points_block .= $html;
                        $count = $key;
                    }
                }
                $points = '';
                $points .= "<div class='points_block' data-code='{$region['code']}' data-points='{$count}'>";
                $points .= "<a href='#' class='add_point'><i class='icon16 add'></i> добавить пункт самовывоза</a>";
                $points .= "<table class='points zebra'>";
                $points .= "<tr><th>Название</th><th>Город</th><th>Адрес</th><th>Часы работы</th><th>Телефон</th><th> </th></tr>";
                $points .= $points_block;
                $points .= "</table></div>";
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
}
