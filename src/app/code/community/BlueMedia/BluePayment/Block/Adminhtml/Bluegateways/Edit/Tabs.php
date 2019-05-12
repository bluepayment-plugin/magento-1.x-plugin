<?php

class BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct()
    {
        parent::__construct();
        $this->setId("bluegateways_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle(Mage::helper("bluepayment")->__("Item Information"));
    }

    protected function _beforeToHtml()
    {
        $this->addTab("form_section", array(
            "label" => Mage::helper("bluepayment")->__("Item Information"),
            "title" => Mage::helper("bluepayment")->__("Item Information"),
            "content" => $this->getLayout()->createBlock("bluepayment/adminhtml_bluegateways_edit_tab_form")->toHtml(),
        ));

        return parent::_beforeToHtml();
    }

}
