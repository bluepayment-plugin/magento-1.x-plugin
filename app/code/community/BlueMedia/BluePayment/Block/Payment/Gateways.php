<?php

class BlueMedia_BluePayment_Block_Payment_Gateways extends Mage_Core_Block_Template
{
    protected $_gatewayList = array();
    protected $_cardsList = array();

    public function getGatewaysList($currency = 'PLN')
    {
        if (!$this->_gatewayList) {
            $order = Mage::registry('current_order');

            $this->_gatewayList = Mage::getModel('bluepayment/bluegateways')
                ->getCollection()
                ->addFieldToFilter('gateway_status', 1)
                ->addFieldToFilter('gateway_currency', $currency)
                ->setOrder('gateway_sort_order', 'DESC');
        }

        return $this->_gatewayList;
    }

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