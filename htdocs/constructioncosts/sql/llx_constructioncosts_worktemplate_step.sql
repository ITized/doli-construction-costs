-- ========================================================================
-- Copyright (C) 2024-2026  ITized <https://github.com/ITized>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
-- ========================================================================

CREATE TABLE llx_constructioncosts_worktemplate_step(
	rowid             integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_worktemplate   integer NOT NULL,
	position          integer DEFAULT 0 NOT NULL,
	ref               varchar(128) DEFAULT '' NOT NULL,
	label             varchar(255) DEFAULT '' NOT NULL,
	description       text,
	step_type         varchar(32) DEFAULT 'product' NOT NULL,
	product_ref       varchar(128) DEFAULT '',
	qty_formula       varchar(255) DEFAULT '1' NOT NULL,
	unit_code         varchar(16) DEFAULT 'U' NOT NULL,
	unit_price_ht     double(24,8) DEFAULT 0 NOT NULL,
	mo_hourly_rate    double(24,8) DEFAULT 0 NOT NULL,
	mo_hours_per_unit double(24,8) DEFAULT 0 NOT NULL,
	vat_rate          double(24,8) DEFAULT 20.0 NOT NULL,
	is_optional       smallint DEFAULT 0 NOT NULL,
	user_prompt       varchar(255) DEFAULT '',
	default_answer    varchar(128) DEFAULT '',
	step_category     varchar(64) DEFAULT ''
) ENGINE=innodb;
