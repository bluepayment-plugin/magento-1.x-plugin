<?php

class BlueMedia_BluePayment_Helper_Gateways extends Mage_Core_Helper_Abstract
{

    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH = 32;
    const UPLOAD_PATH = '/BlueMedia/';

    public function syncGateways()
    {
        $result = array();
        $hashMethod = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");
        $gatewayListAPIUrl = $this->getGatewayListUrl();
        $serviceId = Mage::getStoreConfig("payment/bluepayment/service_id");
        $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);
        $hashKey = Mage::getStoreConfig('payment/bluepayment/shared_key');
        $tryCount = 0;
        $loadResult = false;
        while (!$loadResult) {
            $loadResult = $this->loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl);
            if ($loadResult) {
                $result['success'] = $this->saveGateways((array)$loadResult);
                break;
            } else {
                if ($tryCount >= self::FAILED_CONNECTION_RETRY_COUNT) {
                    $result['error'] = 'Exceeded the limit of attempts to sync gateways list!';
                    break;
                }
            }
            $tryCount++;
        }
    }

    private function loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl)
    {
        $hash = hash($hashMethod, $serviceId . '|' . $messageId . '|' . $hashKey);
        $data = array(
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'Hash' => $hash
        );
        $fields = (is_array($data)) ? http_build_query($data) : $data;
        try {
            $curl = curl_init($gatewayListAPIUrl);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $curlResponse = curl_exec($curl);
            curl_close($curl);
            if ($curlResponse == 'ERROR') {
                return false;
            } else {
                $response = simplexml_load_string($curlResponse);
                return $response;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
            return false;
        }
    }

    public function showBlueMediaInCheckout()
    {
        $gatewaysCount = Mage::getModel('bluepayment/bluegateways')->getCollection()
            ->addFieldToFilter('gateway_status', 1)
            ->getSize();
        if ($gatewaysCount > 0) {
            return true;
        }
        return false;
    }

    public function isCheckoutGatewaysActive()
    {
        return Mage::getStoreConfig("payment/bluepayment/checkout_gateways_active");
    }

    private function saveGateways($gatewayList)
    {
        $existingGateways = $this->getSimpleGatewaysList();
        if (isset($gatewayList['gateway'])) {
            foreach ($gatewayList['gateway'] as $gatewayXMLObject) {
                $gateway = (array)$gatewayXMLObject;
                if (isset($gateway['gatewayID']) && isset($gateway['gatewayName']) && isset($gateway['gatewayType']) && isset($gateway['bankName']) && isset($gateway['iconURL']) && isset($gateway['statusDate'])) {
                    if (isset($existingGateways[$gateway['gatewayID']])) {
                        $gatewayModel = Mage::getModel('bluepayment/bluegateways')->load($existingGateways[$gateway['gatewayID']]['entity_id']);
                    } else {
                        $gatewayModel = Mage::getModel('bluepayment/bluegateways');
                    }

                    $gatewayModel->setData('gateway_id', $gateway['gatewayID']);
                    $gatewayModel->setData('bank_name', $gateway['bankName']);
                    $gatewayModel->setData('gateway_name', $gateway['gatewayName']);
                    $gatewayModel->setData('gateway_type', $gateway['gatewayType']);
                    $gatewayModel->setData('gateway_logo_url', $gateway['iconURL']);
                    $gatewayModel->setData('status_date', $gateway['statusDate']);
                    try {
                        $gatewayModel->save();
                    } catch (Mage_Core_Exception $e) {
                        Mage::log($e->getMessage());
                    }
                }
            }
        }
    }

    public function getSimpleGatewaysList()
    {
        $bluegatewaysCollection = Mage::getModel('bluepayment/bluegateways')->getCollection();

        $existingGateways = array();
        foreach ($bluegatewaysCollection as $blueGateways) {
            $existingGateways[$blueGateways->getData('gateway_id')] = array(
                'entity_id' => $blueGateways->getId(),
                'gateway_status' => $blueGateways->getData('gateway_status'),
                'bank_name' => $blueGateways->getData('bank_name'),
                'gateway_name' => $blueGateways->getData('gateway_name'),
                'gateway_description' => $blueGateways->getData('gateway_description'),
                'gateway_sort_order' => $blueGateways->getData('gateway_sort_order'),
                'gateway_type' => $blueGateways->getData('gateway_type'),
                'gateway_logo_url' => $blueGateways->getData('gateway_logo_url'),
                'use_own_logo' => $blueGateways->getData('use_own_logo'),
                'gateway_logo_path' => $blueGateways->getData('gateway_logo_path'),
                'status_date' => $blueGateways->getData('status_date')
            );
        }
        return $existingGateways;
    }

    private function randomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randstring;
    }

    private function getGatewayListUrl()
    {
        $mode = Mage::getStoreConfig('payment/bluepayment/test_mode');
        if ($mode) {
            return Mage::getStoreConfig('payment/bluepayment/test_address_gateways_url');
        }
        return Mage::getStoreConfig('payment/bluepayment/prod_address_gateways_url');
    }

    public function setQuoteGatewayId($gatewayId)
    {
        Mage::getSingleton('checkout/session')->setQuoteGatewayId($gatewayId);
    }

    public function getQuoteGatewayId()
    {
        $gatewayId = Mage::getSingleton('checkout/session')->getQuoteGatewayId();
        if ($gatewayId) {
            return $gatewayId;
        }
        return false;
    }

    public function showGatewayLogo()
    {
        if (Mage::getStoreConfig('payment/bluepayment/show_gateway_logo') == 1) {
            return true;
        }
        return false;
    }

    /**
     * save uploaded image in the post data
     *
     * @param type $postData reference to the uploaded post data
     * @param string $fieldName form field name
     * @return bool true on success
     */
    public function _getFormImage(&$postData, $fieldName)
    {
        $path = Mage::getBaseDir('media') . self::UPLOAD_PATH;
        try {
            if (isset($postData[$fieldName]['delete']) && $postData[$fieldName]['delete'] == 1) {
                unlink(Mage::getBaseDir('media') . $postData[$fieldName]['value']);
                $postData[$fieldName] = '';
            } else {
                unset($postData[$fieldName]);

                if (isset($_FILES)) {
                    if (isset($_FILES[$fieldName]['name'])) {
                        // $model = Mage::getModel('slider/slider');
                        // replace file with the same name
                        //if ($model->getData($fieldName))
                        $filePath = $path . $_FILES[$fieldName]['name'];
                        if (file_exists($filePath)) {
                            $io = new Varien_Io_File();
                            $io->rm($filePath);
                        }
                        $uploader = new Varien_File_Uploader($fieldName);
                        $uploader->setAllowedExtensions(array('jpg', 'png', 'gif'));
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setFilesDispersion(false);
                        $destFile = $path . $_FILES[$fieldName]['name'];
                        $filename = $uploader->getNewFileName($destFile);
                        $uploader->save($path, $filename);
                        $postData[$fieldName] = self::UPLOAD_PATH . $filename;
                    }
                }
            }
        } catch (Exception $e) {
            // prevent from displaying 'File was not uploaded' message
            if ($e->getCode() !== 666) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            return false;
        }
        return true;
    }

}
