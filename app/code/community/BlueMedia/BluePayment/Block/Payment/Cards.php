<?php

class BlueMedia_BluePayment_Block_Payment_Cards extends Mage_Core_Block_Template
{
    protected $_cardsList = array();

    public function getUserCardList()
    {
        if (!$this->_cardsList) {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $this->_cardsList = Mage::getModel('bluepayment/bluecards')->getCollection()
                    ->addFieldToFilter('customer_id', $customerData->getId());
            }
        }
        return $this->_cardsList;
    }
}
