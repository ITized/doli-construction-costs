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
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

-- Default construction categories (French construction sector)
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('GROS_OEUVRE', 'Gros Oeuvre', 'Structural works: foundations, walls, floors, roof structure', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('SECOND_OEUVRE', 'Second Oeuvre', 'Finishing works: plumbing, electrical, painting, tiling', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('ELECTRICITE', 'Electricité', 'Electrical installations and equipment', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('PLOMBERIE', 'Plomberie', 'Plumbing, water supply and drainage', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('CHAUFFAGE', 'Chauffage / Climatisation', 'Heating, ventilation and air conditioning (HVAC)', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('MENUISERIE', 'Menuiserie', 'Woodwork: doors, windows, frames', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('PEINTURE', 'Peinture / Revêtements', 'Painting and wall/floor coverings', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('CARRELAGE', 'Carrelage / Faïence', 'Tiling and ceramics', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('ISOLATION', 'Isolation', 'Thermal and acoustic insulation', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('TOITURE', 'Toiture / Couverture', 'Roofing and waterproofing', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('TERRASSEMENT', 'Terrassement', 'Earthworks and excavation', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('MACONNERIE', 'Maçonnerie', 'Masonry and brickwork', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('CHARPENTE', 'Charpente', 'Timber framework and structural carpentry', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('SERRURERIE', 'Serrurerie / Métallerie', 'Metalwork and locksmithing', 1);
INSERT INTO llx_c_constructioncosts_category (code, label, description, active) VALUES ('VRD', 'VRD', 'Voirie et Réseaux Divers - Roads and utilities', 1);

-- Default measurement units for construction
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('M2', 'Mètre carré (m²)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('M3', 'Mètre cube (m³)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('ML', 'Mètre linéaire (ml)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('U', 'Unité (u)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('ENS', 'Ensemble (ens)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('KG', 'Kilogramme (kg)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('T', 'Tonne (t)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('L', 'Litre (l)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('H', 'Heure (h)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('J', 'Jour (j)', 1);
INSERT INTO llx_c_constructioncosts_unit (code, label, active) VALUES ('F', 'Forfait (f)', 1);
