<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php');

class BlueMedia_BluePayment_OnepageController extends Mage_Checkout_OnepageController
{
    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

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
            $cardIndex = $this->getRequest()->getPost('payment_method_bluepayment_card_index');
            $autopayGatewayId = Mage::getStoreConfig("payment/bluepayment/autopay_gateway");
            $gPayGatewayId = Mage::getStoreConfig("payment/bluepayment/gpay_gateway");

            if ($gatewayId == $gPayGatewayId) {
                $token = $this->getRequest()->getPost('gpay_token');
                Mage::helper('bluepayment/gateways')->setQuoteGPayToken($token);
            } else {
                if ($gatewayId || $cardIndex) {
                    if ($cardIndex !== null) {
                        Mage::helper('bluepayment/gateways')->setQuoteGatewayId($autopayGatewayId);
                        Mage::helper('bluepayment/gateways')->setQuoteCardIndex($cardIndex);
                    } else {
                        Mage::helper('bluepayment/gateways')->setQuoteGatewayId($gatewayId);
                    }
                } else {
                    if (Mage::helper('bluepayment/gateways')->isCheckoutGatewaysActive()) {
                        $result = array('error'=>'Nie wybrano kanału płatności lub karty !');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }
                }
            }
        }
        
        parent::savePaymentAction();
    }

    public function gpayAction()
    {
        /** @var BlueMedia_BluePayment_Helper_Webapi $webapi */
        $webapi = Mage::helper('bluepayment/webapi');

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $total = $quote->getGrandTotal();
        $currency = $quote->getQuoteCurrencyCode();

        $merchantInfo = $webapi->getMerchantInfo();

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode([
            'testMode' => $webapi->isTestMode(),
            'acceptorId' => (string)$merchantInfo->acceptorId,
            'merchantInfo' => [
                'merchantId' => (string)$merchantInfo->merchantId,
                'merchantOrigin' => (string)$merchantInfo->merchantOrigin,
                'merchantName' => (string)$merchantInfo->merchantName,
                'authJwt' => (string)$merchantInfo->authJwt,
            ],
            'total' => (string)$total,
            'currency' => (string)$currency
        ]));
        return;
    }
}
