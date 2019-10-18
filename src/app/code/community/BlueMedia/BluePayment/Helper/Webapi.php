<?php

class BlueMedia_BluePayment_Helper_Webapi extends Mage_Core_Helper_Abstract
{
    public function getMerchantInfo()
    {
        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();

        $hashMethod = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");
        $serviceId = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/service_id");
        $hashKey = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/shared_key");
        $domain = 'magento2.bm.devmouse.pl';
        $merchantInfoUrl = $this->getMerchantInfoUrl();

        return $this->loadMerchantInfoFromAPI(
            $hashMethod,
            $serviceId,
            $domain,
            $hashKey,
            $merchantInfoUrl
        );
    }

    protected function loadMerchantInfoFromAPI($hashMethod, $serviceId, $merchantDomain, $hashKey, $merchantInfoUrl)
    {
        $hash = hash($hashMethod, $serviceId . '|' . $merchantDomain . '|' . $hashKey);
        $data = array(
            'ServiceID' => $serviceId,
            'MerchantDomain' => $merchantDomain,
            'Hash' => $hash
        );
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

            Mage::log('[Webapi] Response - '.$curlResponse, null, 'bluemedia.log', true);

            curl_close($curl);

            if ($curlResponse == 'ERROR' || $curlResponse == '') {
                return false;
            } else {
                return json_decode($curlResponse);
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

}
