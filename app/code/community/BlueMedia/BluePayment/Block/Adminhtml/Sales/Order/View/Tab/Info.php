<?php

class BlueMedia_BluePayment_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info
{
    public function getPaymentHtml()
    {
        $payment = $this->getOrder()->getPayment()->getMethodInstance()->getCode();
        if ($payment == 'bluepayment') {
            $gatewayId = $this->getOrder()->getBmGatewayId();
            $additionaPaymentInfo = '</br><span class="bluemedia-bluepayment-order-info">' . Mage::helper('bluepayment')->__('Selected gateway ID: ' . $gatewayId) . '</span>';
            return $this->getChildHtml('order_payment') . $additionaPaymentInfo;
        }
        return parent::getPaymentHtml();
    }

}
