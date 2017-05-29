<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php');

class BlueMedia_BluePayment_OnepageController extends Mage_Checkout_OnepageController {

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction() {
        $paymentMethod = $this->getRequest()->getPost('payment', array());
        if (isset($paymentMethod['method']) && $paymentMethod['method'] == 'bluepayment'){
            $gatewayId = $this->getRequest()->getPost('payment_method_bluepayment_gateway');            
            if ($gatewayId){
                Mage::helper('bluepayment/gateways')->setQuoteGatewayId($gatewayId);
                $autoPay = $this->getRequest()->getPost('payment_method_bluepayment_auto_payment');
                if ($gatewayId == Mage::getStoreConfig("payment/bluepayment/autopay_gateway") && !$autoPay){
                    $result = array('error'=>'Nie zaakceptowano regulaminu!');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }else{
                if (Mage::helper('bluepayment/gateways')->isCheckoutGatewaysActive()){
                    $result = array('error'=>'Nie wybrano kanału płatności!');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }
        }
        
        parent::savePaymentAction();
    }
}
