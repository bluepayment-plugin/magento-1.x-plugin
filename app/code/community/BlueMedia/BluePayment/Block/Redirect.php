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
        return $abstract_model->getUrlGateway() . '?' . 
                http_build_query($abstract_model->getFormRedirectFields($this->getOrder()));
    }
}
