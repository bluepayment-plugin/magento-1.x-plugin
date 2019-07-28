<?php

$installer = $this;
$installer->startSetup();
$installer->addAttribute("order", "bm_gateway_id", array("type"=>"int"));
$installer->endSetup();
