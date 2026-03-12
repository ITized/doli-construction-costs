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

-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_constructioncosts_pricingrule ADD INDEX idx_constructioncosts_pricingrule_ref (ref);
ALTER TABLE llx_constructioncosts_pricingrule ADD INDEX idx_constructioncosts_pricingrule_entity (entity);
ALTER TABLE llx_constructioncosts_pricingrule ADD INDEX idx_constructioncosts_pricingrule_fk_product (fk_product);
ALTER TABLE llx_constructioncosts_pricingrule ADD INDEX idx_constructioncosts_pricingrule_rule_type (rule_type);
ALTER TABLE llx_constructioncosts_pricingrule ADD INDEX idx_constructioncosts_pricingrule_status (status);
-- END MODULEBUILDER INDEXES

ALTER TABLE llx_constructioncosts_pricingrule ADD UNIQUE INDEX uk_constructioncosts_pricingrule_ref (ref, entity);
