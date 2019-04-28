<?php

class BlueMedia_BluePayment_Block_Payment_Gateways extends Mage_Core_Block_Template
{
    protected $_gatewayList = array();
    protected $_cardsList = array();

    public function getGatewaysList($currency = 'PLN')
    {
        if (!$this->_gatewayList) {
            $this->_gatewayList = Mage::getModel('bluepayment/bluegateways')
                ->getCollection()
                ->addFieldToFilter('gateway_status', 1)
                ->addFieldToFilter('gateway_currency', $currency)
                ->addFieldToFilter('is_separated_method', 0)
                ->setOrder('gateway_sort_order', 'ASC');

            // Order by gateway_sort_order
            // but 0 goes to the end of the list
             $this->_gatewayList->getSelect()->order(
                 new Zend_Db_Expr(
                     "CASE WHEN `gateway_sort_order` = 0 THEN 999999
                    ELSE `gateway_sort_order` END"
                 )
             );

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