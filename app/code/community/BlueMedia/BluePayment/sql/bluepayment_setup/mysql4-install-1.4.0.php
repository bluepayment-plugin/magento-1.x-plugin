<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
CREATE TABLE 
	blue_gateways(
		entity_id INT NOT NULL AUTO_INCREMENT, 
		gateway_status INT NOT NULL,
		gateway_id INT NOT NULL,
		bank_name VARCHAR(100) CHARACTER SET utf8 NOT NULL,
		gateway_name VARCHAR(100) CHARACTER SET utf8 NOT NULL,
		gateway_description VARCHAR(1000) CHARACTER SET utf8,
		gateway_sort_order INT,
		gateway_type VARCHAR(50) CHARACTER SET utf8 NOT NULL,
		gateway_logo_url VARCHAR(500) CHARACTER SET utf8,
		use_own_logo INT NOT NULL,
		gateway_logo_path VARCHAR(500) CHARACTER SET utf8,
		status_date TIMESTAMP NOT NULL,

		PRIMARY KEY(entity_id)
	);
ALTER TABLE blue_gateways CHARACTER SET utf8 COLLATE utf8_general_ci;
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 