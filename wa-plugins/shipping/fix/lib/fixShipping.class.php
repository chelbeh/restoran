<?php

class fixShipping extends waShipping
{

    public function calculate(){
        if (wa()->getEnv() == 'backend') {
            $result['delivery'] = array(
                'est_delivery' => '',
                'currency'     => 'RUB',
                'rate'         => 0,
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
