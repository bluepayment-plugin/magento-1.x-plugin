<?php

class BlueMedia_BluePayment_CustomerController extends Mage_Core_Controller_Front_Action
{

    const MESSAGE_ID_STRING_LENGTH = 32;

    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * View Your Module
     */
    public function viewAction()
    {
        $card_index = $this->getRequest()->getParam('card_index');
        if ($card_index){
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                try {
                    $customerData = Mage::getSingleton('customer/session')->getCustomer();
                    $cards = Mage::getModel('bluepayment/bluecards')->getCollection()
                        ->addFieldToFilter('customer_id', $customerData->getId())
                        ->addFieldToFilter('card_index', $card_index);
                    foreach ($cards as $card) {
                        $this->deleteCard($card);
                    }
                } catch (Exception $e){
                    var_dump($e);
                }
            }
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Saved Credit Cards'));
        $this->renderLayout();
    }

    private function deleteCard($card){
        $clientHash = Mage::getModel('core/encryption')
            ->decrypt($card->getData('client_hash'));
        $serviceId = Mage::getStoreConfig("payment/bluepayment/service_id");
        $messageId = Mage::helper('bluepayment/gateways')->randomString(self::MESSAGE_ID_STRING_LENGTH);
        $hashKey = Mage::getStoreConfig('payment/bluepayment/shared_key');

        $data = array(
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'ClientHash' => $clientHash,
        );

        $data['Hash'] = Mage::helper('bluepayment')->generateAndReturnHash(
            array($serviceId, $messageId, $clientHash, $hashKey));

        $fields = (is_array($data)) ? http_build_query($data) : $data;
        try {
            $curl = curl_init($this->getDeleteCardUrl());
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $curlResponse = curl_exec($curl);
            curl_close($curl);
            if ($curlResponse == 'ERROR') {
                Mage::log('ERROR DELETE CARD');
            } else {
                $card->delete();
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
        }

    }

    private function getDeleteCardUrl()
    {
        $mode = Mage::getStoreConfig('payment/bluepayment/test_mode');
        if ($mode) {
            return Mage::getStoreConfig('payment/bluepayment/test_address_delete_card_url');
        }
        return Mage::getStoreConfig('payment/bluepayment/prod_address_delete_card_url');
    }
}    