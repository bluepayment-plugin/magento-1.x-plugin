<?php
echo '1';
$installer = $this;
$installer->startSetup();

echo '2';
$installer->addAttribute("order", "bm_gateway_id", array("type"=>"int"));
echo '3';
$installer->endSetup();
	 