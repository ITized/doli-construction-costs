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

CREATE TABLE llx_constructioncosts_pricingrule(
	-- BEGIN MODULEBUILDER FIELDS
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	ref VARCHAR(128) NOT NULL,
	label VARCHAR(255),
	description TEXT,
	fk_product INTEGER DEFAULT NULL,
	fk_category INTEGER DEFAULT NULL,
	rule_type VARCHAR(32) DEFAULT 'product' NOT NULL,
	base_price DOUBLE(24,8) DEFAULT 0,
	base_multiplier DOUBLE(24,8) DEFAULT 1.0,
	mo_hourly_rate DOUBLE(24,8) DEFAULT NULL,
	mo_hours DOUBLE(24,8) DEFAULT 0,
	margin_percent DOUBLE(24,8) DEFAULT 0,
	vat_rate DOUBLE(24,8) DEFAULT 20.0,
	currency_code VARCHAR(3) DEFAULT 'EUR',
	date_start DATE DEFAULT NULL,
	date_end DATE DEFAULT NULL,
	priority INTEGER DEFAULT 0,
	status SMALLINT DEFAULT 1,
	date_creation DATETIME NOT NULL,
	tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_create INTEGER,
	fk_user_modif INTEGER,
	import_key VARCHAR(14)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
