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
 * Abstrakcyjny model
 *
 * @category    BlueMedia
 * @package     BlueMedia_BluePayment
 */
class BlueMedia_BluePayment_Model_Abstract extends Mage_Payment_Model_Method_Abstract {

    private $_checkHashArray = array();

    /**
     * Stałe statusów płatności
     */
    const PAYMENT_STATUS_PENDING = 'PENDING';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILURE = 'FAILURE';

    /**
     * Stałe potwierdzenia autentyczności transakcji
     */
    const TRANSACTION_CONFIRMED = "CONFIRMED";
    const TRANSACTION_NOTCONFIRMED = "NOTCONFIRMED";

    /**
     * Unikatowy wewnętrzy identyfikator metody płatności
     *
     * @var string [a-z0-9_]
     */
    protected $_code = 'bluepayment';

    /**
     * Blok z formularza płatności
     * 
     * @var string
     */
    protected $_formBlockType = 'bluepayment/form';

    /**
     * Czy ta opcja płatności może być pokazywana na stronie 
     * płatności w zakupach typu 'checkout' ?
     * 
     * @var boolean
     */
    protected $_canUseCheckout = true;

    /**
     * Czy stosować tą metodę płatności dla opcji multi-dostaw ?
     * 
     * @var boolean
     */
    protected $_canUseForMultishipping = false;

    /**
     * Czy ta metoda płatności jest bramką (online auth/charge) ?
     * 
     * @var boolean
     */
    protected $_isGateway = false;

    /**
     * Możliwość użycia formy płatności z panelu administracyjnego
     * 
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * Czy wymagana jest inicjalizacja ?
     * 
     * @var boolean
     */
    protected $_isInitializeNeeded = true;

    /**
     * Zwraca adres url kontrolera do przekierowania po potwierdzeniu zamówienia
     * 
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('bluepayment/processing/create', array('_secure' => true));
    }

    /**
     * Zwraca adres bramki
     * 
     * @return string
     */
    public function getUrlGateway() {
        // Aktywny tryb usługi
        $mode = $this->getConfigData('test_mode');

        if ($mode) {
            return Mage::getStoreConfig("payment/bluepayment/test_address_url");
        }

        return Mage::getStoreConfig("payment/bluepayment/prod_address_url");
    }

    /**
     * Tablica z parametrami do wysłania metodą GET do bramki
     * @param object $order
     *
     * @return array
     */
    public function getFormRedirectFields($order) {
        // Id zamówienia
        $orderId = $order->getRealOrderId();

        // Suma zamówienia
        $amount = number_format(round($order->getGrandTotal(), 2), 2, '.', '');

        // Dane serwisu partnera
        // Indywidualny numer serwisu
        $serviceId = $this->getConfigData('service_id');

        // Klucz współdzielony
        $sharedKey = $this->getConfigData('shared_key');
        
        //Kanał płatności        
        
        $gatewayId = Mage::helper('bluepayment/gateways')->getQuoteGatewayId();
        if (!$gatewayId){
            $gatewayId = 0;
        }

        // Adres email klienta
        $customerEmail = $order->getCustomerEmail();

        // Tablica danych z których wygenerować hash
        $hashData = array($serviceId, $orderId, $amount, $gatewayId, $customerEmail, $sharedKey);

        // Klucz hash
        $hashLocal = Mage::helper('bluepayment')->generateAndReturnHash($hashData);

        // Tablica z parametrami do formularza
        $params = array(
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'GatewayID' => $gatewayId,
            'CustomerEmail' => $customerEmail,
            'Hash' => $hashLocal
        );

        return $params;
    }

    /**
     * Ustawia odpowiedni status transakcji/płatności zgodnie z uzyskaną informacją
     * z akcji 'statusAction'
     *
     * @param array $transactions
     * @param string $hash
     */
    public function processStatusPayment($response) {
        if ($this->_validAllTransaction($response)) {
            $transaction_xml = $response->transactions->transaction;
            // Aktualizacja statusu zamówienia i transakcji
            $this->updateStatusTransactionAndOrder($transaction_xml);
        }
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     * @param XML $response
     * @return boolen 
     */
    public function _validAllTransaction($response) {

        $service_id = $this->getConfigData('service_id');
        // Klucz współdzielony
        $shared_key = $this->getConfigData('shared_key');

        $algorithm = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");

        $separator = Mage::getStoreConfig("payment/bluepayment/hash_separator");

        if ($service_id != $response->serviceID)
            return false;
        $this->_checkHashArray = array();
        $hash = (string) $response->hash;
        $this->_checkHashArray[] = (string) $response->serviceID;

        foreach ($response->transactions->transaction as $trans) {
            $this->_checkInList($trans);
        }
        $this->_checkHashArray[] = $shared_key;
        return hash($algorithm, implode($separator, $this->_checkHashArray)) == $hash;
    }

    private function _checkInList($list) {
        foreach ((array) $list as $row) {
            if (is_object($row)) {
                $this->_checkInList($row);
            } else {
                $this->_checkHashArray[] = $row;
            }
        }
    }

    /**
     * Sprawdza czy zamówienie zostało zakończone, zamknięte, lub anulowane
     *
     * @param object $order
     *
     * @return boolean
     */
    public function isOrderCompleted($order) {
        $status = $order->getStatus();
        $stateOrderTab = array(
            Mage_Sales_Model_Order::STATE_CLOSED,
            Mage_Sales_Model_Order::STATE_CANCELED,
            Mage_Sales_Model_Order::STATE_COMPLETE
        );

        return in_array($status, $stateOrderTab);
    }

    /**
     * Potwierdzenie w postaci xml o prawidłowej/nieprawidłowej transakcji
     *
     * @param string $orderId
     * @param string $confirmation
     *
     * @return XML
     */
    protected function returnConfirmation($orderId, $confirmation) {
        // Id serwisu partnera
        $serviceId = $this->getConfigData('service_id');

        // Klucz współdzielony
        $sharedKey = $this->getConfigData('shared_key');

        // Tablica danych z których wygenerować hash
        $hashData = array($serviceId, $orderId, $confirmation, $sharedKey);

        // Klucz hash
        $hashConfirmation = Mage::helper('bluepayment')->generateAndReturnHash($hashData);

        $dom = new DOMDocument('1.0', 'UTF-8');

        $confirmationList = $dom->createElement('confirmationList');

        $domServiceID = $dom->createElement('serviceID', $serviceId);
        $confirmationList->appendChild($domServiceID);

        $transactionsConfirmations = $dom->createElement('transactionsConfirmations');
        $confirmationList->appendChild($transactionsConfirmations);

        $domTransactionConfirmed = $dom->createElement('transactionConfirmed');
        $transactionsConfirmations->appendChild($domTransactionConfirmed);

        $domOrderID = $dom->createElement('orderID', $orderId);
        $domTransactionConfirmed->appendChild($domOrderID);

        $domConfirmation = $dom->createElement('confirmation', $confirmation);
        $domTransactionConfirmed->appendChild($domConfirmation);

        $domHash = $dom->createElement('hash', $hashConfirmation);
        $confirmationList->appendChild($domHash);

        $dom->appendChild($confirmationList);

        echo $dom->saveXML();
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     * @throws Exception
     */
    protected function updateStatusTransactionAndOrder($transaction) {
        // Status płatności
        $paymentStatus = $transaction->paymentStatus;

        // Id transakcji nadany przez bramkę
        $remoteId = $transaction->remoteID;

        // Id zamówienia
        $orderId = $transaction->orderID;

        // Objekt zamówienia
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        // Obiekt płatności zamówienia
        $orderPayment = $order->getPayment();

        // Stan płatności w zamówieniu
        $orderPaymentState = $orderPayment->getAdditionalInformation('bluepayment_state');

        // Suma zamówienia
        $amount = number_format(round($order->getGrandTotal(), 2), 2, '.', '');

        $orderConfig = $order->getConfig();

        // Statusy i stany zamówienia
        // TODO: zastanowić się nad możliwością ustawiania "własnego" stanu zamówienia
        if ($this->getConfigData('status_waiting_payment') != '') {
            $statusWaitingPayment = $this->getConfigData('status_waiting_payment');
            if (method_exists($orderConfig, 'getStatusStates')) {
                $orderStatusWaitingStates = $order->getConfig()->getStatusStates($statusWaitingPayment);
                $keyWaiting = array_search($statusWaitingPayment, $orderStatusWaitingStates);
                $orderStatusWaitingState = $keyWaiting != '' ? $orderStatusWaitingStates[$keyWaiting] : Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            } else {
                $orderStatusWaitingState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            }
        } else {
            $statusWaitingPayment = $order->getConfig()->getStateDefaultStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        }

        if ($this->getConfigData('status_accept_payment') != '') {
            $statusAcceptPayment = $this->getConfigData('status_accept_payment');
            if (method_exists($orderConfig, 'getStatusStates')) {
                $orderStatusAcceptStates = $order->getConfig()->getStatusStates($statusAcceptPayment);
                $keyAccept = array_search($statusAcceptPayment, $orderStatusAcceptStates);
                $orderStatusAcceptState = $keyAccept != '' ? $orderStatusAcceptStates[$keyAccept] : Mage_Sales_Model_Order::STATE_PROCESSING;
            } else {
                $orderStatusAcceptState = Mage_Sales_Model_Order::STATE_PROCESSING;
            }
        } else {
            $statusAcceptPayment = $order->getConfig()->getStateDefaultStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
        }

        if ($this->getConfigData('status_error_payment') != '') {
            $statusErrorPayment = $this->getConfigData('status_error_payment');
            if (method_exists($orderConfig, 'getStatusStates')) {
                $orderStatusErrorStates = $order->getConfig()->getStatusStates($statusErrorPayment);
                $keyError = array_search($statusErrorPayment, $orderStatusErrorStates);
                $orderStatusErrorState = $keyError != '' ? $orderStatusErrorStates[$keyError] : Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            } else {
                $statusErrorPayment = Mage_Sales_Model_Order::STATE_NEW;
            }
        } else {
            $statusErrorPayment = $order->getConfig()->getStateDefaultStatus(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
        }

        $paymentStatus = (string) $paymentStatus;

        try {
            // Jeśli zamówienie jest otwarte i status płatności zamówienia jest różny od statusu płatności z bramki
            if (!($this->isOrderCompleted($order)) && $orderPaymentState != $paymentStatus) {
                switch ($paymentStatus) {
                    // Jeśli transakcja została rozpoczęta
                    case self::PAYMENT_STATUS_PENDING:
                        // Jeśli aktualny status zamówienia jest różny od ustawionego jako "oczekiwanie na płatność"
                        if ($paymentStatus != $orderPaymentState) {
                            $transaction = $orderPayment->setTransactionId((string) $remoteId);
                            $transaction->setPreparedMessage('[' . self::PAYMENT_STATUS_PENDING . ']')
                                    ->save();
                            // Powiadomienie mailowe dla klienta
                            $order->setState($orderStatusWaitingState, $statusWaitingPayment, '', true)
                                    ->sendOrderUpdateEmail(true)
                                    ->save();
                        }
                        break;
                    // Jeśli transakcja została zakończona poprawnie
                    case self::PAYMENT_STATUS_SUCCESS:

                        $transaction = $orderPayment->setTransactionId((string) $remoteId);
                        $transaction->setPreparedMessage('[' . self::PAYMENT_STATUS_SUCCESS . ']')
                                ->registerAuthorizationNotification($amount)
                                ->setIsTransactionApproved(true)
                                ->setIsTransactionClosed(true)
                                ->save();
                        // Powiadomienie mailowe dla klienta
                        $order->setState($orderStatusAcceptState, $statusAcceptPayment, '', true)
                                ->sendOrderUpdateEmail(true)
                                ->save();
                        break;
                    // Jeśli transakcja nie została zakończona poprawnie
                    case self::PAYMENT_STATUS_FAILURE:

                        // Jeśli aktualny status zamówienia jest równy ustawionemu jako "oczekiwanie na płatność"
                        if ($orderPaymentState != $paymentStatus) {
                            $transaction = $orderPayment->setTransactionId((string) $remoteId);
                            $transaction->setPreparedMessage('[' . self::PAYMENT_STATUS_FAILURE . ']')
                                    ->registerCaptureNotification()
                                    ->save();
                            // Powiadomienie mailowe dla klienta
                            $order->setState($orderStatusErrorState, $statusErrorPayment, '', true)
                                    ->sendOrderUpdateEmail(true)
                                    ->save();
                        }
                        break;
                    default:
                        break;
                }
            }
            $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
            $orderPayment->save();
            $this->returnConfirmation($orderId, self::TRANSACTION_CONFIRMED);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}
