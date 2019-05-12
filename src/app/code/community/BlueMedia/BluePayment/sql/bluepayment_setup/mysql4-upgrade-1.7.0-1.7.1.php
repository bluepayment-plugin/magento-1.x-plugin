<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('blue_gateways'), 'own_name', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 255,
    'nullable'  => true,
    'default'   => null,
    'after'     => 'gateway_name',
    'comment'   => 'Own name',
));

$installer->endSetup();