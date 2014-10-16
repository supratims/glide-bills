
create database glide_bills_test COLLATE utf8_unicode_ci;
#DROP TABLE IF EXISTS `test_master`;
CREATE TABLE IF NOT EXISTS `test_master` ( 
	`test_id` INT AUTO_INCREMENT NOT NULL,
	`result` TEXT,
	`creation` DATETIME,
	PRIMARY KEY (`test_id`)
) ENGINE=INNODB CHARACTER SET utf8 ;
#DROP TABLE IF EXISTS `test_api_details`;
CREATE TABLE IF NOT EXISTS `test_api_details` ( 
	`api_id` INT AUTO_INCREMENT NOT NULL,
	`fk_test_id` INT,
	`api` VARCHAR(255) NOT NULL,
	`api_param` VARCHAR(500),	
	`output_json` TEXT, 
	`creation` DATETIME,
	PRIMARY KEY (`api_id`),
	INDEX index_fk_test_id (fk_test_id), FOREIGN KEY (fk_test_id) REFERENCES test_master(test_id) ON DELETE CASCADE
) ENGINE=INNODB  CHARACTER SET utf8 ;