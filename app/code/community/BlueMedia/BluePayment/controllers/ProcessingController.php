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
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Kontroler przetwarzania
 *
 * @category    BlueMedia
 * @package     BlueMedia_BluePayment
 */
class BlueMedia_BluePayment_ProcessingController extends Mage_Core_Controller_Front_Action {

    /**
     * Zwraca singleton dla Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Rozpoczęcie procesu płatności
     */
    public function createAction() {
        try {
            // Sesja
            $session = $this->_getCheckout();

            // Id kolejki modułu w sesji
            $quoteModuleId = $session->getBluePaymentQuoteId();

            // Zapis do sesji
            $session->setQuoteId($quoteModuleId);

            // Id ostatniego zamówienia z sesji
            $sessionLastRealOrderSessionId = $session->getLastRealOrderId();

            // Obiekt zamówienia
            $order = Mage::getModel('sales/order')->loadByIncrementId($sessionLastRealOrderSessionId);


            $statusWaitingPayment = Mage::getStoreConfig("payment/bluepayment/status_waiting_payment");

            // Jeśli ustawiono własny status
            if ($statusWaitingPayment != '') {
                $orderConfig = $order->getConfig();
                if (method_exists($orderConfig, 'getStatusStates')) {
                    $orderStatusWaitingStates = $orderConfig->getStatusStates($statusWaitingPayment);
                    $keyWaiting = array_search($statusWaitingPayment, $orderStatusWaitingStates);
                    $orderStatusWaitingState = $keyWaiting != '' ? $orderStatusWaitingStates[$keyWaiting] : Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                } else {
                    $orderStatusWaitingState = Mage_Sales_Model_Order::STATE_NEW;
                }

                $order->setState($orderStatusWaitingState, Mage::getStoreConfig("payment/bluepayment/status_waiting_payment"))
                        ->save();
            } else {
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
                        ->save();
            }

            $order->sendNewOrderEmail();

            // Załadowanie layout
            $this->loadLayout();
            $this->getLayout()->getBlock('bluepayment_child')->setOrder($order);
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * Sprawdzenie danych po powrocie z bramki płatniczej
     *
     * @throws Exception
     */
    public function backAction() {
        try {
            // Parametry z request
            $params = $this->getRequest()->getParams();

            if (array_key_exists('Hash', $params)) {
                // Id serwisu partnera
                $serviceId = Mage::getStoreConfig("payment/bluepayment/service_id");

                // Id zamówienia
                $orderId = $params['OrderID'];

                // Hash
                $hash = $params['Hash'];

                // Klucz współdzielony
                $sharedKey = Mage::getStoreConfig("payment/bluepayment/shared_key");

                // Tablica danych z których wygenerować hash
                $hashData = array($serviceId, $orderId, $sharedKey);

                // Klucz hash
                $hashLocal = Mage::helper('bluepayment')->generateAndReturnHash($hashData);

                // Sprawdzenie zgodności hash-y oraz reszty parametrów 
                if ($hash == $hashLocal) {
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                } else {
                    $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                }
            } else {
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * ITN - sprawdzenie statusu natychmiastowego powiadomienia o transakcji
     *
     * @throws Exception
     */
    public function statusAction() {
        try {
            // Parametry z request
            $params = $this->getRequest()->getParams();

            // Jeśli parametr 'transactions' istnieje w tablicy $params,
            // wykonaj operacje zmiany statusu płatności zamówienia
            if (array_key_exists('transactions', $params)) {
                // Zakodowany parametr transakcje
                $paramTransactions = $params['transactions'];

                // Odkodowanie parametru transakcji
                $base64transactions = base64_decode($paramTransactions);
                // Odczytanie parametrów z xml-a
                $simpleXml = simplexml_load_string($base64transactions);

                $abstract = Mage::getModel('bluepayment/abstract');
                $abstract->processStatusPayment($simpleXml);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}
