<?php

class waMetroModel extends waModel
{
    protected $table = 'wa_metro';
    public function all()
    {
        $stations = array();
        $data = $this->order('name')->fetchAll();
        foreach($data as $d){
            $stations[$d['id']] = $d['name'];
        }
        return $stations;
    }
}
