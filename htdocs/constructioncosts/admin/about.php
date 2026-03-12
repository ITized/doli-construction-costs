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
 * \file       htdocs/constructioncosts/admin/about.php
 * \ingroup    constructioncosts
 * \brief      About page for ConstructionCosts module.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once '../lib/constructioncosts.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "constructioncosts@constructioncosts"));

// Access control
if (!$user->admin) {
	accessforbidden();
}


/*
 * View
 */

$form = new Form($db);

$title = "ConstructionCostsSetup";

llxHeader('', $langs->trans($title), '', '', 0, 0, '', '', '', 'mod-constructioncosts page-admin-about');

// Configuration header
$head = constructioncostsAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($title), -1, "constructioncosts@constructioncosts");

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Module version
print '<tr><td class="titlefield">'.$langs->trans("Version").'</td><td>1.0.0</td></tr>';

// Module author
print '<tr><td>'.$langs->trans("Author").'</td><td>ITized</td></tr>';

// License
print '<tr><td>'.$langs->trans("License").'</td><td>GPLv3+</td></tr>';

// Description
print '<tr><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("ConstructionCostsAboutDescription").'</td></tr>';

// Links
print '<tr><td>'.$langs->trans("MoreInformation").'</td><td>';
print '<a href="https://github.com/ITized/doli-construction-costs" target="_blank" rel="noopener noreferrer">GitHub Repository</a>';
print '</td></tr>';

print '</table>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
