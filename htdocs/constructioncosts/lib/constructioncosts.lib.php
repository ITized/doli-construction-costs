<?php
/* Copyright (C) 2024-2026	ITized <https://github.com/ITized>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/constructioncosts/lib/constructioncosts.lib.php
 * \ingroup    constructioncosts
 * \brief      Library files with common functions for ConstructionCosts
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function constructioncostsAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("constructioncosts@constructioncosts");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/constructioncosts/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/constructioncosts/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'constructioncosts@constructioncosts');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'constructioncosts@constructioncosts', 'remove');

	return $head;
}

/**
 * Prepare front-facing pages header (tab navigation for the module index)
 *
 * @return array<array{string,string,string}>
 */
function constructioncostsPrepareHead()
{
	global $langs, $conf, $user;

	$langs->load("constructioncosts@constructioncosts");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1);
	$head[$h][1] = $langs->trans("ConstructionCostsDashboard");
	$head[$h][2] = 'dashboard';
	$h++;

	if ($user->hasRight('constructioncosts', 'pricingrule', 'read')) {
		$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1).'?mode=pricingrules';
		$head[$h][1] = $langs->trans("PricingRules");
		$head[$h][2] = 'pricingrules';
		$h++;
	}

	if ($user->hasRight('constructioncosts', 'worktemplate', 'read')) {
		$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1).'?mode=worktemplates';
		$head[$h][1] = $langs->trans("WorkTemplates");
		$head[$h][2] = 'worktemplates';
		$h++;
	}

	if ($user->hasRight('constructioncosts', 'supplierconfig', 'read')) {
		$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1).'?mode=suppliers';
		$head[$h][1] = $langs->trans("SupplierConfigs");
		$head[$h][2] = 'suppliers';
		$h++;
	}

	if ($user->hasRight('constructioncosts', 'import', 'execute')) {
		$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1).'?mode=import';
		$head[$h][1] = $langs->trans("ImportData");
		$head[$h][2] = 'import';
		$h++;

		$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1).'?mode=supplierprices';
		$head[$h][1] = $langs->trans("SupplierPriceImport");
		$head[$h][2] = 'supplierprices';
		$h++;
	}

	if ($user->hasRight('constructioncosts', 'offerconvert', 'execute')) {
		$head[$h][0] = dol_buildpath("/constructioncosts/constructioncostsindex.php", 1).'?mode=offerconvert';
		$head[$h][1] = $langs->trans("ConvertOfferToInvoice");
		$head[$h][2] = 'offerconvert';
		$h++;
	}

	return $head;
}
