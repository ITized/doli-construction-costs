-- ========================================================================
-- Copyright (C) 2024-2026  ITized <https://github.com/ITized>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
-- ========================================================================

ALTER TABLE llx_constructioncosts_worktemplate ADD UNIQUE INDEX uk_worktemplate_ref (ref, entity);
ALTER TABLE llx_constructioncosts_worktemplate ADD INDEX idx_worktemplate_category (category_code);
ALTER TABLE llx_constructioncosts_worktemplate ADD INDEX idx_worktemplate_status (status);
