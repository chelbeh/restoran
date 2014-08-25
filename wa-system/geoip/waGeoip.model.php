<?php

class GeoipModel extends waModel{
    function getData($ip)
    {
        if(empty($ip))return null;
        $long_ip = self::ip2long($ip);
        $sql = "SELECT * FROM `geo_base` WHERE `long_ip1`<='$long_ip' AND `long_ip2`>='$long_ip' LIMIT 1";
        $data = $this->query($sql)->fetchAssoc();
        if ($data){
            $sql = "SELECT * FROM `geo_cities` WHERE `city_id`='$data[city_id]' LIMIT 1";
            $data2 = $this->query($sql)->fetchAssoc();
            if ($data2){
                $data = array_merge($data, $data2);
            }
        }
        return $data;
    }

    private static function ip2long($ip) {
        if(!$ip) {
            return 0;
        } else {
            $dotted = preg_split( "/[.]+/", $ip);
            $ip=(double)0;
            $y=0x1000000;
            for($i=0;$i<4;$i++){
                $ip += ($dotted[$i] * ($y));
                $y = ($y >> 8);
            }
            return $ip;
        }
    }
}