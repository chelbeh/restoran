<?php

class fixShipping extends waShipping
{

    public function calculate(){
        if (wa()->getEnv() == 'backend') {
            $rate = 0;
            if(isset($this->rate))$rate = $this->rate;
            $result['delivery'] = array(
                'est_delivery' => '',
                'currency'     => 'RUB',
                'rate'         => $rate,
            );
            return $result;
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
}
