<?php

class BlueMedia_BluePayment_Model_Bluegateways extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
       $this->_init("bluepayment/bluegateways");
    }

    public function getName()
    {
        $ownName = $this->getData('own_name');

        if ($ownName !== null) {
            return $ownName;
        }

        return $this->getData('gateway_name');
    }
}
