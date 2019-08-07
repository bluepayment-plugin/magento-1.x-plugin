<?php

class BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = "entity_id";
        $this->_blockGroup = "bluepayment";
        $this->_controller = "adminhtml_bluegateways";
        $this->_updateButton("save", "label", Mage::helper("bluepayment")->__("Save Item"));
        $this->_updateButton("delete", "label", Mage::helper("bluepayment")->__("Delete Item"));

        $this->_addButton(
            "saveandcontinue", array(
            "label" => Mage::helper("bluepayment")->__("Save And Continue Edit"),
            "onclick" => "saveAndContinueEdit()",
            "class" => "save",
            ), -100
        );

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if (Mage::registry("bluegateways_data") && Mage::registry("bluegateways_data")->getId()) {
            return Mage::helper("bluepayment")->__(
                "Edit Item '%s'",
                $this->htmlEscape(Mage::registry("bluegateways_data")->getId())
            );
        } else {
            return Mage::helper("bluepayment")->__("Add Item");
        }
    }
}
