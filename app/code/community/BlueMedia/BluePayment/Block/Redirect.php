<?php
/**
 * BlueMedia_BluePayment extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       Blue Media
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Blok przekierowania
 *
 * @category    BlueMedia
 * @package     BlueMedia_BluePayment
 */
class BlueMedia_BluePayment_Block_Redirect extends Mage_Core_Block_Template
{
    public function getUrlWithParams(){
        $abstract_model = Mage::getModel('bluepayment/abstract');
        $params = $abstract_model->getFormRedirectFields($this->getOrder());
        $orderID = $params['OrderID'];
        $curl_payment = Mage::getStoreConfig("payment/bluepayment/curl_payment");
        if (!$curl_payment){
            return array(false, $abstract_model->getUrlGateway() . '?' . http_build_query($params));
        }

        $fields = (is_array($params)) ? http_build_query($params) : $params;
        $curl = curl_init($abstract_model->getUrlGateway());
        if (array_key_exists('ClientHash', $params)){
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('BmHeader: pay-bm'));
        } else{
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('BmHeader: pay-bm-continue-transaction-url'));
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $curlResponse = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = curl_getinfo($curl);
        curl_close($curl);
        if (array_key_exists('ClientHash', $params)){
            return array(false, $this->generateSuccessUrl($orderID));
        } else {
            try {
                $xml = simplexml_load_string($curlResponse);
                $url = (string)$xml->redirecturl;
                if (empty($url)) {
                    return array(false, '/checkout/onepage/failure');
                }
            }catch (Exception $e){
                return array(false, '/checkout/onepage/failure');
            }
        }

        return array(
            array_key_exists('ScreenType', $params),
            $url,
            $this->generateSuccessUrl($orderID),
            $this->generateFailureUrl()
        );
    }

    function generateSuccessUrl($orderID){
        $serviceId = Mage::getStoreConfig("payment/bluepayment/service_id");
        $sharedKey = Mage::getStoreConfig("payment/bluepayment/shared_key");
        $hashData = array($serviceId, $orderID, $sharedKey);
        $hashLocal = Mage::helper('bluepayment')->generateAndReturnHash($hashData);
        return Mage::getUrl('bluepayment/processing/back') . '?' . http_build_query(array(
                'ServiceID'=> $serviceId,
                'OrderID' => $orderID,
                'Hash' => $hashLocal
            ));
    }

    function generateFailureUrl(){
        return Mage::getUrl('bluepayment/processing/back');
    }
}
