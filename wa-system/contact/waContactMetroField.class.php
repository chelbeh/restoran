<?php

class waContactMetroField extends waContactSelectField
{
    /**
     * @var waMetroModel
     */
    protected $model = null;
    private $stations = null;

    public function getOptions($id = null)
    {
        if (!$this->model) {
            $this->model = new waMetroModel();
        }
        if(!$this->stations){
            $this->stations = $this->model->all();
        }

        return $this->stations;
    }

    public function getType()
    {
        return 'Metro';
    }

}