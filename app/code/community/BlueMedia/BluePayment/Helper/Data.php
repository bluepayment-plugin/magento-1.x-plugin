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

        $values_array = array_values($data);

        $values_array_filter = array_filter(($values_array));

        $comma_separated = implode(",", $values_array_filter);

        $replaced = str_replace(",", $separator, $comma_separated);

        $hash = hash($algorithm, $replaced);

        return $hash;
    }
}