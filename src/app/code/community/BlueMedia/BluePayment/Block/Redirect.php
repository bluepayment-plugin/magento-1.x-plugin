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
 * @category       Autopay
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
    public function getUrlWithParams()
    {
        $abstractModel = Mage::getModel('bluepayment/abstract');
        $params = $abstractModel->getFormRedirectFields($this->getOrder());

        Mage::log('[Redirect] Response - '.json_encode($params), null, 'bluemedia.log', true);

        $orderID = $params['OrderID'];
        $curlPayment = Mage::getStoreConfig("payment/bluepayment/curl_payment");
        if (!$curlPayment) {
            return array(false, $abstractModel->getUrlGateway() . '?' . http_build_query($params));
        }

        $fields = (is_array($params)) ? http_build_query($params) : $params;

        $curl = curl_init($abstractModel->getUrlGateway());
        if (array_key_exists('ClientHash', $params)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('BmHeader: pay-bm'));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('BmHeader: pay-bm-continue-transaction-url'));
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $curlResponse = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = curl_getinfo($curl);

        Mage::log('[Redirect] Response - '.$curlResponse, null, 'bluemedia.log', true);

        curl_close($curl);
        if (array_key_exists('ClientHash', $params)) {
            return array(false, $this->generateSuccessUrl($orderID));
        } else {
            try {
                $xml = simplexml_load_string($curlResponse);
                $url = (string)$xml->redirecturl;
                if (empty($url)) {
                    return array(false, '/checkout/onepage/failure');
                }
            } catch (Exception $e) {
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

    public function isTestMode()
    {
        $abstractModel = Mage::getModel('bluepayment/abstract');

        return $abstractModel->isTestMode();
    }

    public function generateSuccessUrl($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $currency = $order->getOrderCurrency()->getCode();

        $serviceId = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/service_id");
        $sharedKey = Mage::getStoreConfig("payment/bluepayment_".strtolower($currency)."/shared_key");
        $hashData = array($serviceId, $orderId, $sharedKey);
        $hashLocal = Mage::helper('bluepayment')->generateAndReturnHash($hashData);
        return Mage::getUrl('bluepayment/processing/back') . '?' . http_build_query(
            array(
                'ServiceID'=> $serviceId,
                'OrderID' => $orderId,
                'Hash' => $hashLocal
            )
        );
    }

    public function generateFailureUrl()
    {
        return Mage::getUrl('bluepayment/processing/back');
    }
}
