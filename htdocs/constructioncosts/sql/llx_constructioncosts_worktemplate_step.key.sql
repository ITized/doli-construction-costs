-- ========================================================================
-- Copyright (C) 2024-2026  ITized <https://github.com/ITized>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
-- ========================================================================

ALTER TABLE llx_constructioncosts_worktemplate_step ADD INDEX idx_wtstep_fk_worktemplate (fk_worktemplate);
ALTER TABLE llx_constructioncosts_worktemplate_step ADD INDEX idx_wtstep_position (fk_worktemplate, position);
ALTER TABLE llx_constructioncosts_worktemplate_step ADD CONSTRAINT fk_wtstep_worktemplate FOREIGN KEY (fk_worktemplate) REFERENCES llx_constructioncosts_worktemplate(rowid);
