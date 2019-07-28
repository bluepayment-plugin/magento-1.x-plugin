<?php

class BlueMedia_BluePayment_Model_Observer
{

    public function saveBmGatewayId($event) 
    {
        $order = $event->getOrder();
        $payment = $order->getPayment()->getMethodInstance()->getCode();
        if ($payment == 'bluepayment') {
            $order->setBmGatewayId(Mage::helper('bluepayment/gateways')->getQuoteGatewayId());
            $order->save();
        }

        return;
    }

}
