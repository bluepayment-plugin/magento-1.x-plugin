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
class BlueMedia_BluePayment_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{

    protected $_checkHashArray = array();

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

    protected $_orderParams = array(
        'ServiceID', 'OrderID', 'Amount', 'GatewayID', 'Currency',
        'CustomerEmail', 'Language', 'CustomerIP', 'RecurringAcceptanceState',
        'RecurringAction', 'ClientHash', 'ScreenType'
    );

    /**
     * Zwraca adres url kontrolera do przekierowania po potwierdzeniu zamówienia
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bluepayment/processing/create', array('_secure' => true));
    }

    /**
     * Zwraca adres bramki
     *
     * @return string
     */
    public function getUrlGateway()
    {
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
    public function getFormRedirectFields($order)
    {
        // Id zamówienia
        $orderId = $order->getRealOrderId();

        // Suma zamówienia
        $amount = number_format(round($order->getGrandTotal(), 2), 2, '.', '');

        // Waluta
        $currency = $order->getOrderCurrency()->getCode();

        // Dane serwisu partnera
        // Indywidualny numer serwisu
        $serviceId = $this->getConfigData('service_id', null, $currency);

        // Klucz współdzielony
        $sharedKey = $this->getConfigData('shared_key', null, $currency);

        //Kanał płatności
        $gatewayId = Mage::helper('bluepayment/gateways')->getQuoteGatewayId();
        if (!$gatewayId) {
            $gatewayId = 0;
        }

        // Adres email klienta
        $customerEmail = $order->getCustomerEmail();

        $params = array(
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
            'CustomerIP' => self::getClientIp(),
            'Language' => self::getLanguage(),
        );

        if ($gatewayId != 0 && Mage::helper('bluepayment/gateways')->isCheckoutGatewaysActive()) {
            $params['GatewayID'] = $gatewayId;

            if ($gatewayId == Mage::getStoreConfig("payment/bluepayment/autopay_gateway")
                && Mage::getSingleton('customer/session')->isLoggedIn()
            ) {
                $cardIndex = Mage::helper('bluepayment/gateways')->getQuoteCardIndex();
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $card = Mage::getModel('bluepayment/bluecards')->getCollection()
                    ->addFieldToFilter('customer_id', $customerData->getId())
                    ->addFieldToFilter('card_index', $cardIndex)->getFirstItem();
                if ($cardIndex == -1 || !$card) {
                    $params['RecurringAcceptanceState'] = 'ACCEPTED';
                    $params['RecurringAction'] = 'INIT_WITH_PAYMENT';
                } else {
                    $params['RecurringAction'] = 'MANUAL';
                    $params['ClientHash'] = Mage::getModel('core/encryption')
                        ->decrypt($card->getData('client_hash'));
                }
            }

            if (Mage::getStoreConfig("payment/bluepayment/iframe_payment") &&
                in_array(
                    $gatewayId, array(
                    Mage::getStoreConfig("payment/bluepayment/autopay_gateway"),
                    Mage::getStoreConfig("payment/bluepayment/card_gateway"))
                )
            ) {
                $params['ScreenType'] = 'IFRAME';
            }
        }

        $params = $this->_sortParams($params);
        
        $hashData = array_values($params);
        $hashData[] = $sharedKey;
        $params['Hash'] = Mage::helper('bluepayment')->generateAndReturnHash($hashData);

        return $params;
    }

    public function getLanguage()
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();

        /* English */
        if (strpos($locale, 'en_') !== false) {
            return "EN";
        }

        /* German */
        if (strpos($locale, 'de_') !== false) {
            return "DE";
        }

//         Czech */
//        if (strpos($locale, 'cs_') !== false) {
//            return "CS";
//        }

        return "PL";
    }

    protected function _sortParams($params)
    {
        $ordered = array();
        foreach ($this->_orderParams as $value) {
            if (array_key_exists($value, $params)) {
                $ordered[$value] = $params[$value];
                unset($params[$value]);
            }
        }

        return $ordered + $params;
    }

    /**
     * Ustawia odpowiedni status transakcji/płatności zgodnie z uzyskaną informacją
     * z akcji 'statusAction'
     *
     * @param SimpleXMLElement $response
     */
    public function processStatusPayment($response)
    {
        if ($response->getName() == 'transactionList') {
            $transaction = $response->transactions->transaction;
        } else {
            $transaction = $response->transaction;
        }

        $orderId = $transaction->orderID;
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if ($this->_validAll($response, $order)) {
            switch ($response->getName()) {
                case 'recurringActivation':
                    return $this->saveCardData($response);
                case 'recurringDeactivation':
                    return $this->deleteCardData($response);
                case 'transactionList';
                    $transactionXml = $response->transactions->transaction;
                    return $this->updateStatusTransactionAndOrder($transactionXml);
                default:
                    break;
            }
        }
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     *
     * @param $response
     * @param $order
     *
     * @return bool
     */
    public function _validAll($response, $order)
    {
        $currency = $order->getOrderCurrency()->getCode();

        $serviceId = $this->getConfigData('service_id', null, $currency);
        $sharedKey = $this->getConfigData('shared_key', null, $currency);

        $algorithm = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");
        $separator = Mage::getStoreConfig("payment/bluepayment/hash_separator");

        if ($serviceId != $response->serviceID) {
            return false;
        }

        $this->_checkHashArray = array();
        $hash = (string)$response->hash;
        $response->hash = null;

        $this->_checkInList($response);
        $this->_checkHashArray[] = $sharedKey;

        return hash($algorithm, implode($separator, $this->_checkHashArray)) == $hash;
    }

    protected function _checkInList($list)
    {
        foreach ((array)$list as $row) {
            if (is_object($row)) {
                $this->_checkInList($row);
            } else {
                $this->_checkHashArray[] = $row;
            }
        }
    }

    /**
     * Potwierdzenie w postaci xml o prawidłowej/nieprawidłowej transakcji
     *
     * @param string $orderId
     * @param string $confirmation
     *
     * @return string
     */
    protected function returnConfirmation($order, $confirmation)
    {
        $currency = $order->getOrderCurrency()->getCode();

        $serviceId = $this->getConfigData('service_id', null, $currency);
        $sharedKey = $this->getConfigData('shared_key', null, $currency);

        // Tablica danych z których wygenerować hash
        $hashData = array($serviceId, $order->getId(), $confirmation, $sharedKey);

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

        $domOrderID = $dom->createElement('orderID', $order->getId());
        $domTransactionConfirmed->appendChild($domOrderID);

        $domConfirmation = $dom->createElement('confirmation', $confirmation);
        $domTransactionConfirmed->appendChild($domConfirmation);

        $domHash = $dom->createElement('hash', $hashConfirmation);
        $confirmationList->appendChild($domHash);

        $dom->appendChild($confirmationList);

        return $dom->saveXML();
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     * @throws Exception
     */
    protected function updateStatusTransactionAndOrder($transaction)
    {
        // Status płatności
        $paymentStatus = $transaction->paymentStatus;

        // Id transakcji nadany przez bramkę
        $remoteId = $transaction->remoteID;

        // Id zamówienia
        $orderId = $transaction->orderID;

        // Objekt zamówienia
        /** @var Mage_Sales_Model_Order $order */
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
            $statusErrorPayment = $order->getConfig()->getStateDefaultStatus(
                Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
            );
        }

        $paymentStatus = (string)$paymentStatus;

        try {
            switch ($paymentStatus) {
                // Jeśli transakcja została rozpoczęta
                case self::PAYMENT_STATUS_PENDING:
                    if ($this->saveUpdate($orderId, $statusWaitingPayment)) {
                        // Powiadomienie mailowe dla klienta
                        $order->setState(
                            $orderStatusWaitingState, $statusWaitingPayment,
                            '[PENDING] Rozpoczęcię płatności przez Płatności online BM', true
                        )
                            ->sendOrderUpdateEmail(true)
                            ->save();
                    } else {
                        $order
                            ->addStatusHistoryComment('[PENDING] Zignorowany ITN')
                            ->save();
                    }
                    break;

                // Jeśli transakcja została zakończona poprawnie
                case self::PAYMENT_STATUS_SUCCESS:
                    if ($this->saveUpdate($orderId, $statusAcceptPayment)) {
                        $transaction = $orderPayment->setTransactionId((string)$remoteId);
                        $transaction->setPreparedMessage('[' . self::PAYMENT_STATUS_SUCCESS . ']')
                            ->registerCaptureNotification($amount)
                            ->setIsTransactionApproved(true)
                            ->setIsTransactionClosed(true)
                            ->save();

                        $order->setState(
                            $orderStatusAcceptState, $statusAcceptPayment,
                            'Potwierdzenie płatności przez bramkę Płatności online BM', true
                        )
                            ->sendOrderUpdateEmail(false)
                            ->save();

                        if ($this->getConfigData('makeinvoice')) {
                            $this->makeInvoiceFromOrder($order);
                        }
                    } else {
                        $order
                            ->addStatusHistoryComment('[SUCCESS] Zignorowany ITN')
                            ->save();
                    }
                    break;

                // Jeśli transakcja nie została zakończona poprawnie
                case self::PAYMENT_STATUS_FAILURE:
                    if ($this->saveUpdate($orderId, $statusErrorPayment)) {
                        // Jeśli aktualny status zamówienia jest równy ustawionemu jako "oczekiwanie na płatność"
                        if ($orderPaymentState != $paymentStatus) {
                            // Powiadomienie mailowe dla klienta
                            $order->setState(
                                $orderStatusErrorState, $statusErrorPayment,
                                'Anulowanie płatności przez bramkę Płatności online BM', true
                            )
                                ->sendOrderUpdateEmail(true)
                                ->save();
                        }
                    } else {
                        $order
                            ->addStatusHistoryComment('[FAILURE] Zignorowany ITN')
                            ->save();
                    }
                    break;
                default:
                    break;
            }

            $orderPayment->setAdditionalInformation('bluepayment_state', $paymentStatus);
            $orderPayment->save();
            return $this->returnConfirmation($order, self::TRANSACTION_CONFIRMED);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    protected function makeInvoiceFromOrder($order)
    {
        try {
            if ($order->canInvoice()) {
                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                if ($invoice->getTotalQty()) {
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();
                    $invoice->getOrder()->addStatusHistoryComment('Created invoice', true);
                    $invoice->sendEmail(true, '');
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function saveCardData($data)
    {
        $orderId = $data->transaction->orderID;
        Mage::log('[Processing] SaveCardData - OrderID: '.$orderId, null, 'bluemedia.log', true);

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $customerId = $order->getData('customer_id');
        Mage::log('[Processing] SaveCardData - CustomerID: '.$customerId, null, 'bluemedia.log', true);

        $status = self::TRANSACTION_NOTCONFIRMED;
        $clientHash = (string)$data->recurringData->clientHash;

        Mage::log('[Processing] SaveCardData - ClientHash: '.$customerId, null, 'bluemedia.log', true);

        if ($customerId) {
            $cardData = $data->cardData;

            /** @var BlueMedia_BluePayment_Model_Bluecards $blueCard */
            $blueCard = Mage::getModel('bluepayment/bluecards')->getCollection()
                ->addFieldToFilter('card_index', (int)$cardData->index)->getFirstItem();

            $blueCard->setData('card_index', (int)$cardData->index);
            $blueCard->setData('customer_id', $customerId);
            $blueCard->setData('validity_year', $cardData->validityYear);
            $blueCard->setData('validity_month', $cardData->validityMonth);
            $blueCard->setData('issuer', $cardData->issuer);
            $blueCard->setData('mask', $cardData->mask);
            $blueCard->setData('client_hash', Mage::getModel('core/encryption')->encrypt($clientHash));

            $blueCard->save();
            $status = self::TRANSACTION_CONFIRMED;
        }

        return $this->recurringResponse($clientHash, $status);
    }

    protected function deleteCardData($data)
    {
        $clientHash = (string)$data->recurringData->clientHash;

        Mage::getModel('bluepayment/bluecards')
            ->getCollection()
            ->addFieldToFilter(
                'client_hash', Mage::getModel('core/encryption')
                ->encrypt($clientHash)
            )
            ->delete();

        return $this->recurringResponse($clientHash, self::TRANSACTION_CONFIRMED);
    }

    protected function recurringResponse($clientHash, $status)
    {
        $serviceId = $this->getConfigData('service_id', null, 'PLN');
        $sharedKey = $this->getConfigData('shared_key', null, 'PLN');

        $hashData = array($serviceId, $clientHash, $status, $sharedKey);

        $hashConfirmation = Mage::helper('bluepayment')->generateAndReturnHash($hashData);

        $dom = new DOMDocument('1.0', 'UTF-8');

        $confirmationList = $dom->createElement('confirmationList');

        $domServiceID = $dom->createElement('serviceID', $serviceId);
        $confirmationList->appendChild($domServiceID);

        $recurringConfirmations = $dom->createElement('recurringConfirmations');
        $confirmationList->appendChild($recurringConfirmations);

        $recurringConfirmed = $dom->createElement('recurringConfirmed');
        $recurringConfirmations->appendChild($recurringConfirmed);

        $clientHash = $dom->createElement('clientHash', $clientHash);
        $recurringConfirmed->appendChild($clientHash);

        $domConfirmation = $dom->createElement('confirmation', $status);
        $recurringConfirmed->appendChild($domConfirmation);

        $domHash = $dom->createElement('hash', $hashConfirmation);
        $confirmationList->appendChild($domHash);

        $dom->appendChild($confirmationList);

        return $dom->saveXML();
    }

    public static function getClientIp()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        $ipaddress = explode(', ', $ipaddress);

        return end($ipaddress);
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null, $currency = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }

        $code = $this->getCode();
        if ($currency) {
            $code = $code.'_'.strtolower($currency);
        }

        $path = 'payment/'.$code.'/'.$field;

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Aktualizuje jedno pole w bazie danych - tylko w celu sprawdzenia, czy zamówienie powinno zostać zaktualizowane
     * (status nie jest taki sam lub nie jest na liście "niezmienialnych statusów"). Metoda wykorzystywana w celu
     * uniknięcia wyścigu ITNów (kiedy sklep otrzymuje dwa różne ITNy dla tego samego zamówienia w tym samym czasie i
     * dane dot. zamówienia nie są akutalizowane między nimi).
     *
     * @param $orderId
     * @param array $saveData
     *
     * @return boolean
     */
    protected function saveUpdate($orderId, $orderStatus)
    {
        $unchangeableStatuses = explode(',', $this->getConfigData('unchangable_statuses'));
        $unchangeableStatuses[] = $orderStatus;

        $saveData = array('status' => $orderStatus);

        $db = Mage::getSingleton('core/resource')->getConnection('core_write');

        $where = $db->quoteInto('increment_id = ?', $orderId);
        $where .= $db->quoteInto(' AND status NOT IN (?)', $unchangeableStatuses);

        $success = $db->update('sales_flat_order', $saveData, $where);

        return $success > 0 ? true : false;
    }
}
