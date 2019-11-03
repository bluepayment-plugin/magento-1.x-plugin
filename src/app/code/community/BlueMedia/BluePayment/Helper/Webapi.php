<?php

class BlueMedia_BluePayment_Helper_Webapi extends Mage_Core_Helper_Abstract
{
    const MESSAGE_ID_STRING_LENGTH = 32;

    const TYPE_XML = 'xml';
    const TYPE_JSON = 'json';

    public function getMerchantInfo()
    {
        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $serviceId = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/service_id");
        $hashKey = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/shared_key");
        $merchantInfoUrl = $this->getMerchantInfoUrl();

        $url = parse_url(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
        $domain = $url['host'];

        return $this->loadFromAPI(
            ['ServiceID' => $serviceId, 'MerchantDomain' => $domain],
            $hashKey,
            $merchantInfoUrl
        );
    }

    public function regulationsGet()
    {
        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $serviceId = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/service_id");
        $hashKey = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/shared_key");
        $url = $this->getRegulationsURL();

        $messageId = Mage::helper('bluepayment/gateways')->randomString(self::MESSAGE_ID_STRING_LENGTH);

        return $this->loadFromAPI(
            ['ServiceID' => $serviceId, 'MessageID' => $messageId],
            $hashKey,
            $url,
            self::TYPE_XML
        );
    }

    protected function loadFromAPI($data, $hashKey, $merchantInfoUrl, $type = self::TYPE_JSON)
    {
        $hashMethod = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");

        $data['Hash'] = hash($hashMethod, implode('|', $data) . '|' . $hashKey);
        $fields = http_build_query($data);

        try {
            Mage::log('[Webapi] merchantInfo - '.json_encode($data), null, 'bluemedia.log', true);

            $curl = curl_init($merchantInfoUrl);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['BmHeader: pay-bm']);
            $curlResponse = curl_exec($curl);
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            Mage::log('[Webapi] Response - ['.$http_status.'] '.$curlResponse, null, 'bluemedia.log', true);

            curl_close($curl);

            if ($curlResponse == 'ERROR' || $curlResponse == '') {
                return false;
            } else {
                if ($type == self::TYPE_JSON) {
                    return json_decode($curlResponse);
                }

                $response = simplexml_load_string($curlResponse);
                return $response;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return Mage::getStoreConfig('payment/bluepayment/test_mode');
    }

    /**
     * @return mixed
     */
    public function getMerchantInfoUrl()
    {
        if ($this->isTestMode()) {
            return Mage::getStoreConfig("payment/bluepayment/test_address_gpay_merchant_info_url");
        }

        return Mage::getStoreConfig("payment/bluepayment/prod_address_gpay_merchant_info_url");
    }

    /**
     * @return string
     */
    public function getRegulationsURL()
    {
        if ($this->isTestMode()) {
            return Mage::getStoreConfig("payment/bluepayment/test_address_regulations_url");
        }

        return Mage::getStoreConfig("payment/bluepayment/prod_address_regulations_url");
    }

}
