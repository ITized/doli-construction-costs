-- ========================================================================
-- Copyright (C) 2024-2026  ITized <https://github.com/ITized>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
-- ========================================================================

CREATE TABLE llx_constructioncosts_worktemplate(
	rowid           integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref             varchar(128) DEFAULT '' NOT NULL,
	label           varchar(255) DEFAULT '' NOT NULL,
	description     text,
	entity          integer DEFAULT 1 NOT NULL,
	category_code   varchar(64) DEFAULT '',
	pricing_mode    varchar(32) DEFAULT 'detailed' NOT NULL,
	margin_percent  double(24,8) DEFAULT 0 NOT NULL,
	vat_rate        double(24,8) DEFAULT 20.0 NOT NULL,
	status          smallint DEFAULT 1 NOT NULL,
	date_creation   datetime,
	tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_create  integer,
	fk_user_modif   integer,
	import_key      varchar(14)
) ENGINE=innodb;
