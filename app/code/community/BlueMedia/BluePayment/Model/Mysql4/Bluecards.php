<?php

class BlueMedia_BluePayment_Model_Mysql4_Bluecards extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("bluepayment/bluecards", "entity_id");
    }
}