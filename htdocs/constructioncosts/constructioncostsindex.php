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
 * \file       htdocs/constructioncosts/constructioncostsindex.php
 * \ingroup    constructioncosts
 * \brief      Home page of Construction Costs module
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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("constructioncosts@constructioncosts"));

// Security check
if (!$user->hasRight('constructioncosts', 'pricingrule', 'read')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

// None for now


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("ConstructionCostsArea"), '', '', 0, 0, '', '', '', 'mod-constructioncosts page-index');

print load_fiche_titre($langs->trans("ConstructionCostsArea"), '', 'building');

print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

// Dashboard info box
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("ConstructionCostsDashboard").'</th></tr>';

// MO Hourly Rate
print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_MO_HOURLY_RATE").'</td>';
print '<td class="right"><strong>'.getDolGlobalString('CONSTRUCTIONCOSTS_MO_HOURLY_RATE', '45.00').' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'/h</strong></td></tr>';

// Default Margin
print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_DEFAULT_MARGIN_PERCENT").'</td>';
print '<td class="right"><strong>'.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_MARGIN_PERCENT', '20.00').'%</strong></td></tr>';

// Service Multiplier
print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_SERVICE_BASE_MULTIPLIER").'</td>';
print '<td class="right"><strong>x'.getDolGlobalString('CONSTRUCTIONCOSTS_SERVICE_BASE_MULTIPLIER', '1.00').'</strong></td></tr>';

// Default VAT
print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_DEFAULT_VAT_RATE").'</td>';
print '<td class="right"><strong>'.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_VAT_RATE', '20.0').'%</strong></td></tr>';

print '</table>';
print '</div>';

print '</div>';
print '<div class="fichetwothirdright">';

// Quick actions
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("QuickActions").'</th></tr>';

print '<tr class="oddeven"><td colspan="2">';
print '<a class="butAction" href="'.dol_buildpath('/constructioncosts/admin/setup.php', 1).'">';
print img_picto('', 'setup', 'class="pictofixedwidth"').' '.$langs->trans("ConstructionCostsSetup");
print '</a> ';
print '</td></tr>';

print '</table>';
print '</div>';

print '</div>';
print '</div>';

// End of page
llxFooter();
$db->close();
