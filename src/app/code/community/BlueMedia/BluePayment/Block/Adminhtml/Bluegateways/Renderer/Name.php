<?php

class BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Renderer_Name extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object|BlueMedia_BluePayment_Model_Bluegateways $row
     *
     * @return mixed|string
     */
    public function render(Varien_Object $row)
    {
        return $row->getName();
    }
}
