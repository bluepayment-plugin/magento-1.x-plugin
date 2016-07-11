<?php   
class BlueMedia_BluePayment_Block_Payment_Gateways extends Mage_Core_Block_Template{   


    protected $_gatewayList = array();


    public function getGatewaysList(){
        if (!$this->_gatewayList){
            $this->_gatewayList = Mage::getModel('bluepayment/bluegateways')->getCollection()
                    ->addFieldToFilter('gateway_status', 1);
        }
        return $this->_gatewayList;
    }

}