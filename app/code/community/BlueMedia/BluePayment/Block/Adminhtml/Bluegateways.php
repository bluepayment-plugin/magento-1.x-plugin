<?php

class BlueMedia_BluePayment_Block_Adminhtml_Bluegateways extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {

        $this->_controller = "adminhtml_bluegateways";
        $this->_blockGroup = "bluepayment";
        $this->_headerText = Mage::helper("bluepayment")->__("Bluegateways Manager");
        $this->_addButtonLabel = Mage::helper("bluepayment")->__("Add New Item");
        parent::__construct();
        
        $this->_removeButton('add');
        
        $this->_addButton('sync', array(
            'label' => 'Sync Gateways',
            'onclick' => 'setLocation(\'' . $this->getSyncGatewaysUrl() . '\')',
            'class' => 'sync',
        ));
    }

    public function getSyncGatewaysUrl() {
        return $this->getUrl('admin_bluepayment/adminhtml_bluegateways/sync');
    }

}
