<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('blue_gateways'), 'is_separated_method', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'nullable'  => false,
    'default'   => '0',
    'after'     => 'gateway_description',
    'comment'   => 'Is separated method?'
));

$installer->endSetup();