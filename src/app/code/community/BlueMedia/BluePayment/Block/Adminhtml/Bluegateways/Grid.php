<?php

class BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId("bluegatewaysGrid");
        $this->setDefaultSort("entity_id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel("bluepayment/bluegateways")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn("entity_id", array(
            "header" => Mage::helper("bluepayment")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "entity_id",
        ));

        $this->addColumn("gateway_currency", array(
            "header" => Mage::helper("bluepayment")->__("Currency"),
            "index" => "gateway_currency",
        ));

        $this->addColumn('gateway_status', array(
            'header' => Mage::helper('bluepayment')->__('Gateway Status'),
            'index' => 'gateway_status',
            'type' => 'options',
            'options' => BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getOptionArray0(),
        ));

        $this->addColumn("gateway_id", array(
            "header" => Mage::helper("bluepayment")->__("Gateway ID"),
            "index" => "gateway_id",
        ));
        $this->addColumn("bank_name", array(
            "header" => Mage::helper("bluepayment")->__("Bank Name"),
            "index" => "bank_name",
        ));
        $this->addColumn("gateway_name", array(
            "header" => Mage::helper("bluepayment")->__("Gateway Name"),
            "index" => "gateway_name",
        ));
        $this->addColumn("gateway_description", array(
            "header" => Mage::helper("bluepayment")->__("Gateway Description"),
            "index" => "gateway_description",
        ));
        $this->addColumn("is_separated_method", array(
            "header" => Mage::helper("bluepayment")->__("Is separated method?"),
            "index" => "is_separated_method",
            'type' => 'options',
            'options' => BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getOptionArray6(),
        ));
        $this->addColumn("gateway_sort_order", array(
            "header" => Mage::helper("bluepayment")->__("Sort Order"),
            "index" => "gateway_sort_order",
        ));
        $this->addColumn("gateway_type", array(
            "header" => Mage::helper("bluepayment")->__("Gateway Type"),
            "index" => "gateway_type",
        ));
        $this->addColumn('use_own_logo', array(
            'header' => Mage::helper('bluepayment')->__('Use Own Logo'),
            'index' => 'use_own_logo',
            'type' => 'options',
            'options' => BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getOptionArray6(),
        ));

        $this->addColumn('status_date', array(
            'header' => Mage::helper('bluepayment')->__('Status Date'),
            'index' => 'status_date',
            'type' => 'datetime',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return $this->getUrl("*/*/edit", array("id" => $row->getId()));
    }

    static public function getOptionArray0() {
        $data_array = array();
        $data_array[0] = Mage::helper("bluepayment")->__('DISABLED');
        $data_array[1] = Mage::helper("bluepayment")->__('ENABLED');
        return($data_array);
    }

    static public function getValueArray0() {
        $data_array = array();
        foreach (BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getOptionArray0() as $k => $v) {
            $data_array[] = array('value' => $k, 'label' => $v);
        }
        return($data_array);
    }

    static public function getOptionArray6() {
        $data_array = array();
        $data_array[0] = Mage::helper("bluepayment")->__('NO');
        $data_array[1] = Mage::helper("bluepayment")->__('YES');
        return($data_array);
    }

    static public function getValueArray6() {
        $data_array = array();
        foreach (BlueMedia_BluePayment_Block_Adminhtml_Bluegateways_Grid::getOptionArray6() as $k => $v) {
            $data_array[] = array('value' => $k, 'label' => $v);
        }
        return($data_array);
    }

}
