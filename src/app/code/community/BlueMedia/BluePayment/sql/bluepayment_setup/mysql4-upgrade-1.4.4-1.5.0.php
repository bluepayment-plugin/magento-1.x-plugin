<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
CREATE TABLE
	blue_cards(
		entity_id INT NOT NULL AUTO_INCREMENT,
		customer_id INT NOT NULL,
		card_index INT NOT NULL,
		validity_year VARCHAR(4) CHARACTER SET utf8 NOT NULL,
		validity_month VARCHAR(2) CHARACTER SET utf8 NOT NULL,
		issuer VARCHAR(100) CHARACTER SET utf8,
		mask VARCHAR(100) CHARACTER SET utf8 NOT NULL,
		client_hash VARCHAR(1000) CHARACTER SET utf8 NOT NULL,
		PRIMARY KEY(entity_id)
	);
ALTER TABLE blue_cards CHARACTER SET utf8 COLLATE utf8_general_ci;
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 