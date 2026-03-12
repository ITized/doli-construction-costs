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
 * \file       htdocs/constructioncosts/admin/setup.php
 * \ingroup    constructioncosts
 * \brief      ConstructionCosts setup page.
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
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/constructioncosts.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "constructioncosts@constructioncosts"));

// Initialize hooks
$hookmanager->initHooks(array('constructioncostssetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Form setup factory
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);


// ---- Pricing Configuration Section ----
$formSetup->newItem('PricingSection')->setAsTitle();

// MO Hourly Rate (Main d'Oeuvre)
$item = $formSetup->newItem('CONSTRUCTIONCOSTS_MO_HOURLY_RATE');
$item->defaultFieldValue = '45.00';
$item->fieldAttr['placeholder'] = '45.00';
$item->helpText = $langs->transnoentities('MOHourlyRateHelp');
$item->cssClass = 'minwidth200';

// Default Margin Percentage
$item = $formSetup->newItem('CONSTRUCTIONCOSTS_DEFAULT_MARGIN_PERCENT');
$item->defaultFieldValue = '20.00';
$item->fieldAttr['placeholder'] = '20.00';
$item->helpText = $langs->transnoentities('DefaultMarginPercentHelp');
$item->cssClass = 'minwidth200';

// Service Base Multiplier
$item = $formSetup->newItem('CONSTRUCTIONCOSTS_SERVICE_BASE_MULTIPLIER');
$item->defaultFieldValue = '1.00';
$item->fieldAttr['placeholder'] = '1.00';
$item->helpText = $langs->transnoentities('ServiceBaseMultiplierHelp');
$item->cssClass = 'minwidth200';

// Default VAT Rate
$item = $formSetup->newItem('CONSTRUCTIONCOSTS_DEFAULT_VAT_RATE');
$item->defaultFieldValue = '20.0';
$item->fieldAttr['placeholder'] = '20.0';
$item->helpText = $langs->transnoentities('DefaultVATRateHelp');
$item->cssClass = 'minwidth200';

// Default Currency
$TField = array(
	'EUR' => 'EUR - Euro',
	'USD' => 'USD - US Dollar',
	'GBP' => 'GBP - British Pound',
	'CHF' => 'CHF - Swiss Franc',
);
$formSetup->newItem('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY')->setAsSelect($TField);


// ---- Import/Export Section ----
$formSetup->newItem('ImportExportSection')->setAsTitle();

// CSV Separator
$TCSVSep = array(
	';' => $langs->trans('Semicolon') . ' (;)',
	',' => $langs->trans('Comma') . ' (,)',
	'\t' => $langs->trans('Tab'),
	'|' => $langs->trans('Pipe') . ' (|)',
);
$formSetup->newItem('CONSTRUCTIONCOSTS_CSV_SEPARATOR')->setAsSelect($TCSVSep);


// ---- Supplier Integration Section ----
$formSetup->newItem('SupplierIntegrationSection')->setAsTitle();

// Enable Supplier Sync
$formSetup->newItem('CONSTRUCTIONCOSTS_ENABLE_SUPPLIER_SYNC')->setAsYesNo();


$setupnotempty += count($formSetup->items);


/*
 * Actions
 */

if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$action = 'edit';


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "ConstructionCostsSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-constructioncosts page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = constructioncostsAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "constructioncosts@constructioncosts");

// Setup page description
echo '<span class="opacitymedium">'.$langs->trans("ConstructionCostsSetupPage").'</span><br><br>';

if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true);
	print '<br>';
}

print dol_get_fiche_end();

llxFooter();
$db->close();
