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
 * Blok formularza z metodą płatności bluepayment
 *
 * @category    BlueMedia
 * @package     BlueMedia_BluePayment
 */
class BlueMedia_BluePayment_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Konstruktor. Ustawienie szablonu formularza.
     */
    protected function _construct()
    {
        $this->setTemplate('bluepayment/form.phtml');
        $this->setMethodTitle(Mage::helper('bluepayment')->__('Online payments BM'));

        return parent::_construct();
    }

    /**
     * Zwraca adres do logo firmy
     *
     * @return string|bool
     */
    public function getLogoSrc()
    {
        $logo_src = $this->getSkinUrl('images/bluepayment/logo.png');

        return $logo_src != '' ? $logo_src : false;
    }

    public function getInfoText(){
        return preg_replace('#<script(.*?)>(.*?)</script>#is', '',
            Mage::getStoreConfig("payment/bluepayment/info_text"));
    }


}
