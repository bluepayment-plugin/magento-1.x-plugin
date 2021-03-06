<?php

class BlueMedia_BluePayment_Block_Payment_Gateways extends Mage_Core_Block_Template
{
    protected $_gatewayList = array();
    protected $_cardsList = array();

    public function getGatewaysList($currency = 'PLN')
    {
        if (!$this->_gatewayList) {
            $regulations = $this->getParsedRegulations();

            $q = Mage::getModel('bluepayment/bluegateways')
                ->getCollection()
                ->addFieldToFilter('gateway_status', 1)
                ->addFieldToFilter('gateway_currency', $currency)
                ->addFieldToFilter('is_separated_method', 0)
                ->setOrder('gateway_sort_order', 'ASC');

            // Order by gateway_sort_order
            // but 0 goes to the end of the list
            $q->getSelect()->order(
                new Zend_Db_Expr(
                    "CASE WHEN `gateway_sort_order` = 0 THEN 999999
                    ELSE `gateway_sort_order` END"
                )
            );

            $alwaysSeparatedMethods = [
                BlueMedia_BluePayment_Helper_Gateways::getAutoPaymentsGatewayId(),
                BlueMedia_BluePayment_Helper_Gateways::getGPayGatewayId(),
                BlueMedia_BluePayment_Helper_Gateways::getCreditGatewayId()
            ];

            $gatewayList = array();
            foreach ($q as $gateway) {
                $id = $gateway['gateway_id'];

                if (isset($regulations[$id])) {
                    $gateway->setData('regulation', $regulations[$id]['text']);
                }

                if (!in_array($gateway['gateway_id'], $alwaysSeparatedMethods)) {
                    $gatewayList[] = $gateway;
                }
            }

            BlueMedia_BluePayment_Helper_Gateways::sortGateways($gatewayList);
            $this->_gatewayList = $gatewayList;
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

    private function getParsedRegulations()
    {
        $list = Mage::helper('bluepayment/webapi')->regulationsGet();

        if (!is_object($list)) {
            return [];
        }

        $regulations = [];
        foreach ($list->regulations->regulation as $regulation)
        {
            $regulation = (array)$regulation;

            $regulations[$regulation['gatewayID']] = [
                'id' => $regulation['regulationID'],
                'type' => $regulation['type'],
                'text' => $regulation['inputLabel'],
                'language' => $regulation['language'],
            ];
        }

        return $regulations;
    }
}
