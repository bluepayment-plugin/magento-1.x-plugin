<?php

class BlueMedia_BluePayment_Model_Mysql4_Bluegateways extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("bluepayment/bluegateways", "entity_id");
    }
}