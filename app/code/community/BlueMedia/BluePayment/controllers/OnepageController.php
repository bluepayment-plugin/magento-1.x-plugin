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
            }else{
                $result = array('error'=>'Nie wybrano kanału płatności!');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }
        }
        
        parent::savePaymentAction();
//        if ($this->_expireAjax()) {
//            return;
//        }
//        try {
//            if (!$this->getRequest()->isPost()) {
//                $this->_ajaxRedirectResponse();
//                return;
//            }
//
//            // set payment to quote
//            $result = array();
//            $data = $this->getRequest()->getPost('payment', array());
//            $result = $this->getOnepage()->savePayment($data);
//
//            // get section and redirect data
//            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
//            if (empty($result['error']) && !$redirectUrl) {
//                $this->loadLayout('checkout_onepage_review');
//                $result['goto_section'] = 'review';
//                $result['update_section'] = array(
//                    'name' => 'review',
//                    'html' => $this->_getReviewHtml()
//                );
//            }
//            if ($redirectUrl) {
//                $result['redirect'] = $redirectUrl;
//            }
//        } catch (Mage_Payment_Exception $e) {
//            if ($e->getFields()) {
//                $result['fields'] = $e->getFields();
//            }
//            $result['error'] = $e->getMessage();
//        } catch (Mage_Core_Exception $e) {
//            $result['error'] = $e->getMessage();
//        } catch (Exception $e) {
//            Mage::logException($e);
//            $result['error'] = $this->__('Unable to set Payment Method.');
//        }
//        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
