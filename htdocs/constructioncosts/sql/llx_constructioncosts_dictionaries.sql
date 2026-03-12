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

-- Dictionary: Construction cost categories
CREATE TABLE llx_c_constructioncosts_category(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	code VARCHAR(32) NOT NULL,
	label VARCHAR(255) NOT NULL,
	description TEXT,
	active TINYINT DEFAULT 1 NOT NULL
) ENGINE=innodb;

-- Dictionary: Construction measurement units
CREATE TABLE llx_c_constructioncosts_unit(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	code VARCHAR(32) NOT NULL,
	label VARCHAR(255) NOT NULL,
	active TINYINT DEFAULT 1 NOT NULL
) ENGINE=innodb;
