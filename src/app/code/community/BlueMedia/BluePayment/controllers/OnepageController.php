<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php');

class BlueMedia_BluePayment_OnepageController extends Mage_Checkout_OnepageController {

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction()
    {
        $paymentMethod = $this->getRequest()->getPost('payment', array());
        if (isset($paymentMethod['method']) && $paymentMethod['method'] == 'bluepayment') {
            $gatewayId = $this->getRequest()->getPost('payment_method_bluepayment_gateway');
            $card_index = $this->getRequest()->getPost('payment_method_bluepayment_card_index');

            if ($gatewayId || $card_index) {
                if ($card_index !== null){
                    Mage::helper('bluepayment/gateways')->setQuoteGatewayId(Mage::getStoreConfig("payment/bluepayment/autopay_gateway"));
                    Mage::helper('bluepayment/gateways')->setQuoteCardIndex($card_index);
                } else {
                    Mage::helper('bluepayment/gateways')->setQuoteGatewayId($gatewayId);
                }
            }else{
                if (Mage::helper('bluepayment/gateways')->isCheckoutGatewaysActive()) {
                    $result = array('error'=>'Nie wybrano kanału płatności lub karty !');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }
        }
        
        parent::savePaymentAction();
    }
}
