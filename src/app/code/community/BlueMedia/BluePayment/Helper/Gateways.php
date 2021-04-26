<?php

class BlueMedia_BluePayment_Helper_Gateways extends Mage_Core_Helper_Abstract
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH = 32;
    const UPLOAD_PATH = '/BlueMedia/';

    public $currencies = array('PLN', 'GBP', 'EUR', 'USD', 'CZK');

    const DEFAULT_SORT_ORDER = array(
        '', // Avoid pushing first element to the end
        509, // BLIK
        1503, // Kartowa płatność automatyczna
        1500, // Płatność kartą
        1512, // Google Pay
        1513, // Apple Pay
        1511, // Visa Checkout
        700, // Smartney
        106, // Tylko na teście
        68, // Płać z ING
        1808, // Płatność z ING
        3, // mTransfer
        1800, // Płatność z mBank
        1063, // Płacę z IPKO
        1803, // Płatność z PKOBP
        27, // Santander online
        1806, // Płatność z Santander
        52, // Pekao24 PBL
        1805, // Płatność z PekaoSA
        85, // Millennium Bank PBL
        1807, // Płatność z Millenium
        95, // Płacę z Alior Bankiem
        1802, // Płatność z Alior
        59, // CA przelew online
        1809, // Płatność z Credit Agricole
        79, // Eurobank - płatność online
        1064, // Płacę z Inteligo
        1810, // Płatność z Inteligo
        1035, // BNP Paribas - płacę z Pl@net
        1804, // Płatność z BNP Paribas
        513, // Getin Bank
        1801, // Płatność z Getin
        1010, // T-Mobile Usługi Bankowe
        90, // Płacę z Citi Handlowy
        76, // BNP Paribas-Płacę z żółty online
        108, // e-transfer Pocztowy24
        517, // NestPrzelew
        131, //
        86, // Płać z BOŚ Bank
        98, // PBS Bank - przelew 24
        117, // Toyota Bank Pay Way
        1050, // Płacę z neoBANK
        514, // Noble Bank
        109, // EnveloBank
        1507, // Bank Spółdzielczy w Sztumie PBL
        1510, // Bank Spółdzielczy Lututów PBL
        1515, // Bank Spółdzielczy w Toruniu PBL
        1517, // Bank Spółdzielczy w Rumi PBL
        21, // Przelew Volkswagen Bank
        35, // Spółdzielcza Grupa Bankowa
        9, // Mam konto w innym banku
        1506, // Alior Raty
    );

    public function syncGateways()
    {
        foreach ($this->currencies as $currency) {
            $result = array();

            $serviceId = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/service_id");
            $hashKey = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/shared_key");

            if ($serviceId && $hashKey) {
                $hashMethod = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");
                $gatewayListAPIUrl = $this->getGatewayListUrl();
                $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);
                $tryCount = 0;
                $loadResult = false;
                while (!$loadResult) {
                    $loadResult = $this->loadGatewaysFromAPI(
                        $hashMethod,
                        $serviceId,
                        $messageId,
                        $hashKey,
                        $gatewayListAPIUrl
                    );

                    if ($loadResult) {
                        $result['success'] = $this->saveGateways((array)$loadResult, $currency);
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
        }
    }

    protected function loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl)
    {
        $hash = hash($hashMethod, $serviceId . '|' . $messageId . '|' . $hashKey);
        $data = array(
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'Hash' => $hash
        );
        $fields = (is_array($data)) ? http_build_query($data) : $data;
        try {
            Mage::log('[Gateways] Response - '.json_encode($data), null, 'bluemedia.log', true);

            $curl = curl_init($gatewayListAPIUrl);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $curlResponse = curl_exec($curl);

            Mage::log('[Gateways] Response - '.$curlResponse, null, 'bluemedia.log', true);

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

    public function showBlueMediaInCheckout($currency = 'PLN')
    {
        $gatewaysCount = Mage::getModel('bluepayment/bluegateways')->getCollection()
            ->addFieldToFilter('gateway_status', 1)
            ->addFieldToFilter('gateway_currency', $currency)
            ->addFieldToFilter('is_separated_method', 0)
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

    public function getRegFileUrl()
    {
        return Mage::getStoreConfig("payment/bluepayment/regulations_auto_payments");
    }

    private function saveGateways($gatewayList, $currency = 'PLN')
    {
        $existingGateways = $this->getSimpleGatewaysList();

        if (isset($gatewayList['gateway'])) {
            if (is_array($gatewayList['gateway'])) {
                $gatewayXMLObjects = $gatewayList['gateway'];
            } else {
                $gatewayXMLObjects = array($gatewayList['gateway']);
            }

            foreach ($gatewayXMLObjects as $gatewayXMLObject) {
                $gateway = (array)$gatewayXMLObject;
                if (isset($gateway['gatewayID'])
                    && isset($gateway['gatewayName'])
                    && isset($gateway['bankName'])
                    && isset($gateway['iconURL'])
                    && isset($gateway['statusDate'])
                ) {
                    if (isset($existingGateways[$currency][$gateway['gatewayID']])) {
                        $gatewayModel = Mage::getModel('bluepayment/bluegateways')->load(
                            $existingGateways[$currency][$gateway['gatewayID']]['entity_id']
                        );
                    } else {
                        $gatewayModel = Mage::getModel('bluepayment/bluegateways');
                    }

                    $gatewayModel->setData('gateway_currency', $currency);
                    $gatewayModel->setData('gateway_id', $gateway['gatewayID']);
                    $gatewayModel->setData('bank_name', $gateway['bankName']);
                    $gatewayModel->setData('gateway_name', $gateway['gatewayName']);
                    $gatewayModel->setData('gateway_logo_url', $gateway['iconURL']);
                    $gatewayModel->setData('status_date', $gateway['statusDate']);

                    if (isset($gateway['gatewayType'])) {
                        $gatewayModel->setData('gateway_type', $gateway['gatewayType']);
                    } else {
                        $gatewayModel->setData('gateway_type', '');
                    }

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
        foreach ($this->currencies as $currency) {
            $existingGateways[$currency] = array();
        }

        foreach ($bluegatewaysCollection as $blueGateways) {
            $existingGateways[$blueGateways->getData('gateway_currency')][$blueGateways->getData('gateway_id')] = array(
                'entity_id' => $blueGateways->getId(),
                'gateway_currency' => $blueGateways->getData('gateway_currency'),
                'gateway_status' => $blueGateways->getData('gateway_status'),
                'bank_name' => $blueGateways->getData('bank_name'),
                'gateway_name' => $blueGateways->getData('gateway_name'),
                'own_name' => $blueGateways->getData('own_name'),
                'gateway_description' => $blueGateways->getData('gateway_description'),
                'is_separated_method' => $blueGateways->getData('is_separated_method'),
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

    public function getSeparatedGatewaysList($currency = 'PLN')
    {
        $regulations = $this->getParsedRegulations();

        $q = Mage::getModel('bluepayment/bluegateways')->getCollection()
            ->addFieldToFilter('gateway_status', 1)
            ->addFieldToFilter('gateway_currency', $currency);

        // Order by gateway_sort_order
        // but 0 goes to the end of the list
        $q->getSelect()->order(
            new Zend_Db_Expr(
                "CASE WHEN `gateway_sort_order` = 0 THEN 999999
                    ELSE `gateway_sort_order` END"
            )
        );

        $alwaysSeparatedMethods = [
            self::getGPayGatewayId()
        ];
        $dontShowMethods = [
            self::getAutoPaymentsGatewayId(),
        ];

        // Add Smartney
        $grandTotal = Mage::getModel('checkout/session')->getQuote()->getGrandTotal();
        if ($grandTotal >= 100 && $grandTotal <= 2500) {
            $alwaysSeparatedMethods[] = self::getCreditGatewayId();
        }

        $gateways = array();
        foreach ($q as $gateway) {
            $id = $gateway['gateway_id'];

            if (isset($regulations[$id])) {
                $gateway->setData('regulation', $regulations[$id]['text']);
            }

            if (
                ($gateway['is_separated_method'] == 1 && !in_array($gateway['gateway_id'], $dontShowMethods))
                || in_array($gateway['gateway_id'], $alwaysSeparatedMethods)
            ) {
                $gateways[] = $gateway;
            }
        }

        $this->sortGateways($gateways);

        return $gateways;
    }

    public function randomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';

        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randstring;
    }

    protected function getGatewayListUrl()
    {
        $testMode = Mage::getStoreConfig('payment/bluepayment/test_mode');

        if ($testMode) {
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

    public function setQuoteCardIndex($cardIndex)
    {
        Mage::getSingleton('checkout/session')->setQuoteCardIndex($cardIndex);
    }

    public function getQuoteCardIndex()
    {
        $cardIndex = Mage::getSingleton('checkout/session')->getQuoteCardIndex();
        if ($cardIndex) {
            return $cardIndex;
        }

        return false;
    }

    public function setQuoteGPayToken($token)
    {
        Mage::getSingleton('checkout/session')->setQuoteGPayToken($token);
    }

    public function getQuoteGPayToken()
    {
        $token = Mage::getSingleton('checkout/session')->getQuoteGPayToken();
        if ($token) {
            return $token;
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

    public function showAutoPayments()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()
            && Mage::getStoreConfig('payment/bluepayment/auto_payments') == 1
            && Mage::app()->getStore()->getCurrentCurrencyCode() == 'PLN'
        ) {
            return true;
        }

        return false;
    }

    public static function getAutoPaymentsGatewayId()
    {
        return Mage::getStoreConfig('payment/bluepayment/autopay_gateway');
    }

    public static function getGPayGatewayId()
    {
        return Mage::getStoreConfig('payment/bluepayment/gpay_gateway');
    }

    public static function getCreditGatewayId()
    {
        return Mage::getStoreConfig('payment/bluepayment/credit_gateway');
    }

    public static function sortGateways(&$array)
    {
        usort(
            $array, function ($a, $b) {
            $aPos = (int)$a->getData('gateway_sort_order');
            $bPos = (int)$b->getData('gateway_sort_order');

            if ($aPos == $bPos) {
                $aPos = array_search($a->getData('gateway_id'), self::DEFAULT_SORT_ORDER);
                $bPos = array_search($b->getData('gateway_id'), self::DEFAULT_SORT_ORDER);
            } elseif ($aPos == 0) {
                return true;
            } elseif ($bPos == 0) {
                return false;
            }

            return $aPos >= $bPos;
        }
        );

        return $array;
    }

    /**
     * save uploaded image in the post data
     *
     * @param array $postData reference to the uploaded post data
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
