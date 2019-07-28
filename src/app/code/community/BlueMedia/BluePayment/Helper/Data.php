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
 * Funkcje pomocnicze
 *
 * @category    BlueMedia
 * @package     BlueMedia_BluePayment
 */
class BlueMedia_BluePayment_Helper_Data extends Mage_Payment_Helper_Data
{
    /**
     * Generuje i zwraca klucz hash na podstawie wartości pól z tablicy
     *
     * @param array $data
     * @return string
     */
    public function generateAndReturnHash($data)
    {
        $algorithm = Mage::getStoreConfig("payment/bluepayment/hash_algorithm");
        $separator = Mage::getStoreConfig("payment/bluepayment/hash_separator");
        $arrayValues = array_values($data);
        $arrayValuesFilter = array_filter(($arrayValues));
        $commaSeparated = implode(",", $arrayValuesFilter);
        $replaced = str_replace(",", $separator, $commaSeparated);
        $hash = hash($algorithm, $replaced);
        return $hash;
    }
}
