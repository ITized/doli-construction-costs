-- Copyright (C) 2024-2026 ITized <https://github.com/ITized>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

CREATE TABLE llx_constructioncosts_supplierconfig(
	-- BEGIN MODULEBUILDER FIELDS
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	ref VARCHAR(128) NOT NULL,
	label VARCHAR(255),
	supplier_name VARCHAR(255) NOT NULL,
	supplier_type VARCHAR(32) DEFAULT 'csv' NOT NULL,
	api_url VARCHAR(512) DEFAULT NULL,
	api_key VARCHAR(255) DEFAULT NULL,
	import_format VARCHAR(32) DEFAULT 'csv',
	csv_separator VARCHAR(4) DEFAULT ';',
	csv_enclosure VARCHAR(4) DEFAULT '"',
	match_field VARCHAR(32) DEFAULT 'ean',
	default_margin_percent DOUBLE(24,8) DEFAULT 0,
	auto_update SMALLINT DEFAULT 0,
	update_frequency INTEGER DEFAULT 86400,
	last_sync_date DATETIME DEFAULT NULL,
	fk_supplier INTEGER DEFAULT NULL,
	status SMALLINT DEFAULT 1,
	date_creation DATETIME NOT NULL,
	tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_create INTEGER,
	fk_user_modif INTEGER,
	import_key VARCHAR(14)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
