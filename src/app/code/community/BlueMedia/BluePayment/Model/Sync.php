<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BlueMedia_BluePayment_Model_Sync {

    public function syncGateways() {
        Mage::helper('bluepayment/gateways')->syncGateways();
        return;
    }

}
