<?php

class BlueMedia_BluePayment_Block_Customer extends Mage_Core_Block_Template
{
    function getLoggedUserCards()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            return Mage::getModel('bluepayment/bluecards')->getCollection()
                ->addFieldToFilter('customer_id', $customerData->getId());
        }
        return array();
    }
}