<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $this->getTable('blue_gateways'), 'gateway_currency', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 5,
    'nullable'  => false,
    'default'   => 'PLN',
    'after'     => 'entity_id',
    'comment'   => 'Gateway currency'
    )
);
$installer->endSetup();
