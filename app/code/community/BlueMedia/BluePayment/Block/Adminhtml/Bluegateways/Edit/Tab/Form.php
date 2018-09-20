<?php

class BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("bluepayment_form", array("legend" => Mage::helper("bluepayment")->__("Item information")));


        $fieldset->addField('gateway_status', 'select', array(
            'label' => Mage::helper('bluepayment')->__('Gateway Status'),
            'values' => BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getValueArray0(),
            'name' => 'gateway_status',
        ));
        $fieldset->addField('gateway_id', 'text', array(
            'disabled' => true,
            'label' => Mage::helper('bluepayment')->__('Gateway ID'),
            'name' => 'gateway_id',
        ));
        $fieldset->addField('gateway_currency', 'text', array(
            'disabled' => true,
            'label' => Mage::helper('bluepayment')->__('Currency'),
            'name' => 'gateway_currency',
        ));
        $fieldset->addField('bank_name', 'text', array(
            'disabled' => true,
            'label' => Mage::helper('bluepayment')->__('Bank Name'),
            'name' => 'bank_name',
        ));
        $fieldset->addField('gateway_name', 'text', array(
            'label' => Mage::helper('bluepayment')->__('Gateway Name'),
            'name' => 'gateway_name',
        ));
        $fieldset->addField('gateway_type', 'text', array(
            'disabled' => true,
            'label' => Mage::helper('bluepayment')->__('Gateway Type'),
            'name' => 'gateway_type',
        ));

        $fieldset->addField("gateway_description", "text", array(
            "label" => Mage::helper("bluepayment")->__("Gateway Description"),
            "name" => "gateway_description",
        ));

        $fieldset->addField("gateway_sort_order", "text", array(
            "label" => Mage::helper("bluepayment")->__("Sort Order"),
            "name" => "gateway_sort_order",
        ));

        $fieldset->addField('gateway_logo_url', 'text', array(
            'disabled' => true,
            'label' => Mage::helper('bluepayment')->__('Gateway Logo URL'),
            'name' => 'gateway_logo_url',
        ));

        $fieldset->addField('use_own_logo', 'select', array(
            'label' => Mage::helper('bluepayment')->__('Use Own Logo'),
            'values' => BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getValueArray6(),
            'name' => 'use_own_logo',
        ));
        $fieldset->addField('gateway_logo_path', 'image', array(
            'label' => Mage::helper('bluepayment')->__('Gateway Logo Path'),
            'name' => 'gateway_logo_path',
            'note' => '(*.jpg, *.png, *.gif)',
        ));
        $fieldset->addField('status_date', 'text', array(
            'disabled' => true,
            'label' => Mage::helper('bluepayment')->__('Status Date'),
            'name' => 'status_date',
        ));


        if (Mage::getSingleton("adminhtml/session")->getBluegatewaysData()) {
            $form->setValues(Mage::getSingleton("adminhtml/session")->getBluegatewaysData());
            Mage::getSingleton("adminhtml/session")->setBluegatewaysData(null);
        } elseif (Mage::registry("bluegateways_data")) {
            $form->setValues(Mage::registry("bluegateways_data")->getData());
        }
        return parent::_prepareForm();
    }

}
