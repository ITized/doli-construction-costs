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
 * \brief      Home page of Construction Costs module - routes to different sections by mode
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
require_once __DIR__.'/lib/constructioncosts.lib.php';

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
$mode = GETPOST('mode', 'aZ09');


/*
 * Actions
 */

// Handle work template generation action
if ($action === 'generate' && $mode === 'worktemplates') {
	// Action handled in the view section below
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

// Determine active tab from mode
$activeTab = 'dashboard';
$validModes = array('pricingrules', 'suppliers', 'import', 'worktemplates', 'supplierprices', 'offerconvert');
if (!empty($mode) && in_array($mode, $validModes)) {
	$activeTab = $mode;
}

// Page title per mode
$pageTitles = array(
	'dashboard' => 'ConstructionCostsArea',
	'pricingrules' => 'PricingRules',
	'suppliers' => 'SupplierConfigs',
	'import' => 'ImportData',
	'worktemplates' => 'WorkTemplates',
	'supplierprices' => 'SupplierPriceImport',
	'offerconvert' => 'ConvertOfferToInvoice',
);
$pageTitle = $langs->trans(isset($pageTitles[$activeTab]) ? $pageTitles[$activeTab] : 'ConstructionCostsArea');

llxHeader("", $pageTitle, '', '', 0, 0, '', '', '', 'mod-constructioncosts page-index');

print load_fiche_titre($pageTitle, '', 'building');

// Tab navigation
$head = constructioncostsPrepareHead();
print dol_get_fiche_head($head, $activeTab, $langs->trans("ModuleConstructionCostsName"), -1, 'building');


// ============================================================
// DASHBOARD (default)
// ============================================================
if ($activeTab === 'dashboard') {
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
	print '<a class="butAction" href="'.dol_buildpath('/constructioncosts/constructioncostsindex.php', 1).'?mode=worktemplates">';
	print img_picto('', 'list', 'class="pictofixedwidth"').' '.$langs->trans("WorkTemplates");
	print '</a> ';
	print '<a class="butAction" href="'.dol_buildpath('/constructioncosts/constructioncostsindex.php', 1).'?mode=import">';
	print img_picto('', 'import', 'class="pictofixedwidth"').' '.$langs->trans("ImportData");
	print '</a> ';
	print '<a class="butAction" href="'.dol_buildpath('/constructioncosts/admin/setup.php', 1).'">';
	print img_picto('', 'setup', 'class="pictofixedwidth"').' '.$langs->trans("ConstructionCostsSetup");
	print '</a> ';
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '</div>';
	print '</div>';
}

// ============================================================
// PRICING RULES
// ============================================================
elseif ($activeTab === 'pricingrules') {
	require_once __DIR__.'/class/pricingrule.class.php';

	print '<div class="opacitymedium">'.$langs->trans("PricingRulesOverview").'</div><br>';

	// Show a specimen pricing rule as example
	$specimen = new PricingRule($db);
	$specimen->initAsSpecimen();
	$breakdown = $specimen->getPriceBreakdown();

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("WorkTemplateRef").'</th>';
	print '<th>'.$langs->trans("WorkTemplateLabel").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateStepType").'</th>';
	print '<th class="right">'.$langs->trans("MaterialCost").'</th>';
	print '<th class="right">'.$langs->trans("LaborCost").'</th>';
	print '<th class="right">'.$langs->trans("MarginPercent").'</th>';
	print '<th class="right">'.$langs->trans("PriceHT").'</th>';
	print '<th class="right">'.$langs->trans("PriceTTC").'</th>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.dol_escape_htmltag($specimen->ref).'</td>';
	print '<td>'.dol_escape_htmltag($specimen->label).'</td>';
	print '<td class="center">'.$langs->trans("PricingRuleType".ucfirst($specimen->rule_type)).'</td>';
	print '<td class="right nowrap">'.price($breakdown['material_cost']).'</td>';
	print '<td class="right nowrap">'.price($breakdown['labor_cost']).'</td>';
	print '<td class="right nowrap">'.price($breakdown['margin_percent']).'%</td>';
	print '<td class="right nowrap"><strong>'.price($breakdown['price_ht']).'</strong></td>';
	print '<td class="right nowrap"><strong>'.price($breakdown['price_ttc']).'</strong></td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	// Breakdown detail
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Subtotal").' - '.dol_escape_htmltag($specimen->ref).'</th></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("MaterialCost").'</td>';
	print '<td class="right">'.price($breakdown['material_cost']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("LaborCost").' ('.$specimen->mo_hours.'h x '.price($specimen->getEffectiveMoRate()).'/h)</td>';
	print '<td class="right">'.price($breakdown['labor_cost']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("Subtotal").' (x'.$specimen->base_multiplier.')</td>';
	print '<td class="right">'.price($breakdown['subtotal']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("MarginAmount").' ('.price($breakdown['margin_percent']).'%)</td>';
	print '<td class="right">+'.price($breakdown['margin_amount']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';

	print '<tr class="oddeven"><td><strong>'.$langs->trans("PriceHT").'</strong></td>';
	print '<td class="right"><strong>'.price($breakdown['price_ht']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong></td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("VATAmount").' ('.price($breakdown['vat_rate']).'%)</td>';
	print '<td class="right">+'.price($breakdown['vat_amount']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';

	print '<tr class="oddeven"><td><strong>'.$langs->trans("PriceTTC").'</strong></td>';
	print '<td class="right"><strong>'.price($breakdown['price_ttc']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong></td></tr>';

	print '</table>';
	print '</div>';
}

// ============================================================
// WORK TEMPLATES
// ============================================================
elseif ($activeTab === 'worktemplates') {
	require_once __DIR__.'/class/worktemplate.class.php';

	// Create specimen template for demonstration
	$template = new WorkTemplate();
	$template->initAsSpecimenRepaintPaperedWall();

	// Handle generate action
	$generatedLines = array();
	$generateMode = '';
	if ($action === 'generate') {
		$area = (float) GETPOST('area', 'alpha');
		$layers = (float) GETPOST('layers', 'alpha');
		$rooms = (float) GETPOST('rooms', 'alpha');
		$height = (float) GETPOST('height', 'alpha');
		$pricingMode = GETPOST('pricing_mode', 'aZ09');

		$params = array(
			'area' => $area > 0 ? $area : 35,
			'layers' => $layers > 0 ? $layers : 2,
			'rooms' => $rooms > 0 ? $rooms : 1,
			'height' => $height > 0 ? $height : 2.5,
		);

		// Collect user answers for optional prompts
		$userAnswers = array();
		$prompts = $template->getRequiredPrompts();
		foreach ($prompts as $prompt) {
			$answer = GETPOST('prompt_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $prompt['ref']), 'alpha');
			if (!empty($answer)) {
				$userAnswers[$prompt['ref']] = $answer;
			} else {
				$userAnswers[$prompt['ref']] = $prompt['default_answer'];
			}
		}

		if ($pricingMode === 'forfait') {
			$generatedLines = $template->generateForfait($params, $userAnswers);
			$generateMode = 'forfait';
		} else {
			$generatedLines = $template->generateLines($params, $userAnswers);
			$generateMode = 'detailed';
		}
	}

	// Template info
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("WorkTemplateList").'</th></tr>';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("WorkTemplateRef").'</th>';
	print '<th>'.$langs->trans("WorkTemplateLabel").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateCategory").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateStatus").'</th>';
	print '</tr>';

	$statusLabel = $template->status == WorkTemplate::STATUS_ACTIVE ? $langs->trans("WorkTemplateStatusActive") : $langs->trans("WorkTemplateStatusDraft");
	$statusClass = $template->status == WorkTemplate::STATUS_ACTIVE ? 'badge badge-status4' : 'badge badge-status0';

	print '<tr class="oddeven">';
	print '<td>'.dol_escape_htmltag($template->ref).'</td>';
	print '<td>'.dol_escape_htmltag($template->label).'</td>';
	print '<td class="center">'.dol_escape_htmltag($template->category_code).'</td>';
	print '<td class="center"><span class="'.$statusClass.'">'.$statusLabel.'</span></td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	// Template steps detail
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="7">'.$langs->trans("WorkTemplateSteps").' - '.dol_escape_htmltag($template->label).'</th></tr>';
	print '<tr class="liste_titre">';
	print '<th>#</th>';
	print '<th>'.$langs->trans("WorkTemplateLabel").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateStepType").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateStepCategory").'</th>';
	print '<th>'.$langs->trans("WorkTemplateStepQtyFormula").'</th>';
	print '<th class="right">'.$langs->trans("WorkTemplateStepUnitPrice").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateStepOptional").'</th>';
	print '</tr>';

	foreach ($template->steps as $step) {
		$stepTypeLabel = $step->step_type === 'service' ? $langs->trans("WorkTemplateStepTypeService") : $langs->trans("WorkTemplateStepTypeProduct");
		$catKey = 'WorkTemplateCategory'.ucfirst($step->step_category);

		print '<tr class="oddeven">';
		print '<td>'.$step->position.'</td>';
		print '<td>'.dol_escape_htmltag($step->label).'</td>';
		print '<td class="center">'.$stepTypeLabel.'</td>';
		print '<td class="center">'.$langs->trans($catKey).'</td>';
		print '<td><code>'.dol_escape_htmltag($step->qty_formula).'</code></td>';
		if ($step->step_type === 'service' && $step->mo_hours_per_unit > 0) {
			print '<td class="right nowrap">'.price($step->mo_hours_per_unit).'h x '.price($step->mo_hourly_rate > 0 ? $step->mo_hourly_rate : (float) getDolGlobalString('CONSTRUCTIONCOSTS_MO_HOURLY_RATE', '45.00')).'/h</td>';
		} else {
			print '<td class="right nowrap">'.price($step->unit_price_ht).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td>';
		}
		print '<td class="center">'.($step->is_optional ? img_picto($langs->trans("Yes"), 'tick') : '').'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	// Generate offer form
	print '<br>';
	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"].'?mode=worktemplates').'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="generate">';
	print '<input type="hidden" name="mode" value="worktemplates">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("GenerateOfferFrom").' - '.dol_escape_htmltag($template->ref).'</th></tr>';

	// Input parameters
	print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("WorkTemplateInputArea").'</td>';
	print '<td><input type="text" name="area" value="'.dol_escape_htmltag(GETPOST('area', 'alpha') ? GETPOST('area', 'alpha') : '35').'" class="minwidth100" /> m²</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("WorkTemplateInputLayers").'</td>';
	print '<td><input type="text" name="layers" value="'.dol_escape_htmltag(GETPOST('layers', 'alpha') ? GETPOST('layers', 'alpha') : '2').'" class="minwidth100" /></td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("WorkTemplateInputRooms").'</td>';
	print '<td><input type="text" name="rooms" value="'.dol_escape_htmltag(GETPOST('rooms', 'alpha') ? GETPOST('rooms', 'alpha') : '1').'" class="minwidth100" /></td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("WorkTemplateInputHeight").'</td>';
	print '<td><input type="text" name="height" value="'.dol_escape_htmltag(GETPOST('height', 'alpha') ? GETPOST('height', 'alpha') : '2.5').'" class="minwidth100" /> m</td></tr>';

	// Optional step prompts
	$prompts = $template->getRequiredPrompts();
	if (!empty($prompts)) {
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("WorkTemplatePromptQuestions").'</th></tr>';
		foreach ($prompts as $prompt) {
			$fieldName = 'prompt_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $prompt['ref']);
			$currentVal = GETPOST($fieldName, 'alpha') ? GETPOST($fieldName, 'alpha') : $prompt['default_answer'];
			print '<tr class="oddeven"><td>'.dol_escape_htmltag($prompt['prompt']).'</td>';
			print '<td><select name="'.$fieldName.'" class="minwidth100">';
			print '<option value="yes"'.($currentVal === 'yes' ? ' selected' : '').'>'.$langs->trans("Yes").'</option>';
			print '<option value="no"'.($currentVal === 'no' ? ' selected' : '').'>'.$langs->trans("No").'</option>';
			print '</select></td></tr>';
		}
	}

	// Pricing mode choice
	print '<tr class="oddeven"><td>'.$langs->trans("WorkTemplatePricingMode").'</td>';
	print '<td><select name="pricing_mode" class="minwidth200">';
	print '<option value="detailed">'.$langs->trans("WorkTemplatePricingDetailed").'</option>';
	print '<option value="forfait">'.$langs->trans("WorkTemplatePricingForfait").'</option>';
	print '</select></td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("GenerateOffer").'" />';
	print '</div>';
	print '</form>';

	// Display generated lines if action was triggered
	if (!empty($generatedLines)) {
		print '<br>';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="7">'.$langs->trans("GeneratedLines").' ('.($generateMode === 'forfait' ? $langs->trans("WorkTemplatePricingForfait") : $langs->trans("WorkTemplatePricingDetailed")).')</th></tr>';
		print '<tr class="liste_titre">';
		print '<th>#</th>';
		print '<th>'.$langs->trans("WorkTemplateLabel").'</th>';
		print '<th class="center">'.$langs->trans("WorkTemplateStepType").'</th>';
		print '<th class="right">'.$langs->trans("Qty").'</th>';
		print '<th class="right">'.$langs->trans("WorkTemplateStepUnitPrice").'</th>';
		print '<th class="right">'.$langs->trans("VATRate").'</th>';
		print '<th class="right">'.$langs->trans("PriceHT").'</th>';
		print '</tr>';

		$totalHT = 0;
		foreach ($generatedLines as $line) {
			$stepTypeLabel = '';
			if ($line['step_type'] === 'service') {
				$stepTypeLabel = $langs->trans("WorkTemplateStepTypeService");
			} elseif ($line['step_type'] === 'product') {
				$stepTypeLabel = $langs->trans("WorkTemplateStepTypeProduct");
			} else {
				$stepTypeLabel = $langs->trans("PricingRuleTypeMixed");
			}

			print '<tr class="oddeven">';
			print '<td>'.$line['position'].'</td>';
			print '<td>'.dol_escape_htmltag($line['label']).'</td>';
			print '<td class="center">'.$stepTypeLabel.'</td>';
			print '<td class="right nowrap">'.price($line['qty']).' '.$line['unit_code'].'</td>';
			print '<td class="right nowrap">'.price($line['unit_price_ht']).'</td>';
			print '<td class="right nowrap">'.price($line['vat_rate']).'%</td>';
			print '<td class="right nowrap"><strong>'.price($line['total_ht']).'</strong></td>';
			print '</tr>';
			$totalHT += $line['total_ht'];
		}

		print '<tr class="liste_total">';
		print '<td colspan="6" class="right"><strong>'.$langs->trans("TotalEstimate").'</strong></td>';
		print '<td class="right nowrap"><strong>'.price($totalHT).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong></td>';
		print '</tr>';

		print '</table>';
		print '</div>';
	}
}

// ============================================================
// SUPPLIER CONFIGURATIONS
// ============================================================
elseif ($activeTab === 'suppliers') {
	print '<div class="opacitymedium">'.$langs->trans("SupplierConfigs").'</div><br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("SupplierName").'</th>';
	print '<th class="center">'.$langs->trans("SupplierType").'</th>';
	print '<th class="center">'.$langs->trans("MatchField").'</th>';
	print '<th class="center">'.$langs->trans("CONSTRUCTIONCOSTS_ENABLE_SUPPLIER_SYNC").'</th>';
	print '<th class="center">'.$langs->trans("LastSyncDate").'</th>';
	print '</tr>';

	// Supplier sync status
	$syncEnabled = getDolGlobalInt('CONSTRUCTIONCOSTS_ENABLE_SUPPLIER_SYNC');
	$syncStatus = $syncEnabled ? '<span class="badge badge-status4">'.$langs->trans("Enabled").'</span>' : '<span class="badge badge-status8">'.$langs->trans("Disabled").'</span>';

	print '<tr class="oddeven">';
	print '<td class="opacitymedium" colspan="5">'.$langs->trans("NoPricingRulesYet").'</td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	// Sync configuration status
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("SupplierIntegrationSection").'</th></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_ENABLE_SUPPLIER_SYNC").'</td>';
	print '<td class="right">'.$syncStatus.'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_CSV_SEPARATOR").'</td>';
	print '<td class="right"><code>'.getDolGlobalString('CONSTRUCTIONCOSTS_CSV_SEPARATOR', ';').'</code></td></tr>';

	print '</table>';
	print '</div>';

	// Link to setup
	print '<div class="center">';
	print '<a class="butAction" href="'.dol_buildpath('/constructioncosts/admin/setup.php', 1).'">';
	print img_picto('', 'setup', 'class="pictofixedwidth"').' '.$langs->trans("ConstructionCostsSetup");
	print '</a>';
	print '</div>';
}

// ============================================================
// IMPORT DATA (CSV)
// ============================================================
elseif ($activeTab === 'import') {
	require_once __DIR__.'/class/constructioncostscatalog.class.php';

	$catalog = new ConstructionCostsCatalog($db);

	// Handle CSV template download
	if ($action === 'downloadtemplate') {
		$csvContent = $catalog->generateCSVTemplate();
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="constructioncosts_import_template.csv"');
		echo $csvContent;
		exit;
	}

	print '<div class="opacitymedium">'.$langs->trans("ImportCSVDescription").'</div><br>';

	// Download template link
	print '<div class="marginbottomonly">';
	print '<a class="butAction" href="'.dol_escape_htmltag($_SERVER["PHP_SELF"].'?mode=import&action=downloadtemplate&token='.newToken()).'">';
	print img_picto('', 'download', 'class="pictofixedwidth"').' '.$langs->trans("DownloadCSVTemplate");
	print '</a>';
	print '</div>';

	// Import form
	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"].'?mode=import').'" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="importcsv">';
	print '<input type="hidden" name="mode" value="import">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("ImportCSVTitle").'</th></tr>';

	print '<tr class="oddeven"><td class="titlefield fieldrequired">'.$langs->trans("File").'</td>';
	print '<td><input type="file" name="csvfile" accept=".csv,.txt" class="minwidth300" /></td></tr>';

	$separator = getDolGlobalString('CONSTRUCTIONCOSTS_CSV_SEPARATOR', ';');
	print '<tr class="oddeven"><td>'.$langs->trans("CONSTRUCTIONCOSTS_CSV_SEPARATOR").'</td>';
	print '<td><select name="separator" class="minwidth100">';
	print '<option value=";"'.(($separator === ';') ? ' selected' : '').'>'.$langs->trans("Semicolon").' (;)</option>';
	print '<option value=","'.(($separator === ',') ? ' selected' : '').'>'.$langs->trans("Comma").' (,)</option>';
	print '<option value="\t"'.(($separator === '\t') ? ' selected' : '').'>'.$langs->trans("Tab").'</option>';
	print '<option value="|"'.(($separator === '|') ? ' selected' : '').'>'.$langs->trans("Pipe").' (|)</option>';
	print '</select></td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("ImportData").'" />';
	print '</div>';
	print '</form>';

	// CSV template columns preview
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="3">'.$langs->trans("DownloadCSVTemplate").' - Column Format</th></tr>';
	print '<tr class="liste_titre"><th>#</th><th>Column</th><th>Description</th></tr>';

	$columns = array(
		array('ref', 'Product reference (required)'),
		array('label', 'Product label (required)'),
		array('description', 'Product description'),
		array('category', 'Construction category code'),
		array('type', 'product, service or mixed'),
		array('price_ht', 'Price HT'),
		array('vat_rate', 'VAT rate (%)'),
		array('unit', 'Unit of measure'),
		array('ean', 'EAN barcode'),
		array('supplier', 'Supplier name'),
		array('supplier_ref', 'Supplier reference'),
		array('supplier_price', 'Supplier price HT'),
	);
	$colNum = 1;
	foreach ($columns as $col) {
		print '<tr class="oddeven"><td>'.$colNum.'</td><td><strong>'.$col[0].'</strong></td><td>'.$col[1].'</td></tr>';
		$colNum++;
	}

	print '</table>';
	print '</div>';
}

// ============================================================
// SUPPLIER PRICE IMPORT
// ============================================================
elseif ($activeTab === 'supplierprices') {
	print '<div class="opacitymedium">'.$langs->trans("SupplierPriceImport").'</div><br>';

	// CSV import tab
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.img_picto('', 'file-csv', 'class="pictofixedwidth"').' '.$langs->trans("ImportFromCSV").'</th></tr>';

	print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("SupplierPriceImportCSVDesc").'</td></tr>';

	print '</table>';
	print '</div>';

	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"].'?mode=supplierprices').'" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="importsuppliercsv">';
	print '<input type="hidden" name="mode" value="supplierprices">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="oddeven"><td class="titlefield fieldrequired">'.$langs->trans("SupplierName").'</td>';
	print '<td><input type="text" name="supplier_name" value="'.dol_escape_htmltag(GETPOST('supplier_name', 'alpha')).'" class="minwidth200" /></td></tr>';

	print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("File").'</td>';
	print '<td><input type="file" name="pricefile" accept=".csv,.txt,.json" class="minwidth300" /></td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("MatchField").'</td>';
	print '<td><select name="match_field" class="minwidth100">';
	print '<option value="ref">'.$langs->trans("MatchFieldRef").'</option>';
	print '<option value="ean">'.$langs->trans("MatchFieldEAN").'</option>';
	print '</select></td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("ImportFromCSV").'" />';
	print '</div>';
	print '</form>';

	// URL import section
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.img_picto('', 'url', 'class="pictofixedwidth"').' '.$langs->trans("ImportFromURL").'</th></tr>';
	print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("SupplierPriceImportURLDesc").'</td></tr>';
	print '</table>';
	print '</div>';

	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"].'?mode=supplierprices').'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="importsupplierurl">';
	print '<input type="hidden" name="mode" value="supplierprices">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="oddeven"><td class="titlefield fieldrequired">'.$langs->trans("SupplierName").'</td>';
	print '<td><input type="text" name="supplier_name_url" value="'.dol_escape_htmltag(GETPOST('supplier_name_url', 'alpha')).'" class="minwidth200" /></td></tr>';

	print '<tr class="oddeven"><td class="fieldrequired">URL</td>';
	print '<td><input type="text" name="import_url" value="'.dol_escape_htmltag(GETPOST('import_url', 'alpha')).'" class="minwidth400" placeholder="https://..." /></td></tr>';

	print '<tr class="oddeven"><td>Format</td>';
	print '<td><select name="url_format" class="minwidth100">';
	print '<option value="csv">CSV</option>';
	print '<option value="json">JSON</option>';
	print '</select></td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("ImportFromURL").'" />';
	print '</div>';
	print '</form>';

	// Manual entry section
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.img_picto('', 'edit', 'class="pictofixedwidth"').' '.$langs->trans("ImportManualEntry").'</th></tr>';
	print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("SupplierPriceImportManualDesc").'</td></tr>';
	print '</table>';
	print '</div>';
}

// ============================================================
// OFFER-TO-INVOICE CONVERSION
// ============================================================
elseif ($activeTab === 'offerconvert') {
	require_once __DIR__.'/class/offerconverter.class.php';
	require_once __DIR__.'/class/worktemplate.class.php';

	print '<div class="opacitymedium">'.$langs->trans("OfferConversionDesc").'</div><br>';

	// Demonstration: generate lines from a specimen template and show conversion preview
	$template = new WorkTemplate();
	$template->initAsSpecimenRepaintPaperedWall();

	$params = array('area' => 35, 'layers' => 2, 'rooms' => 1, 'height' => 2.5);
	$propalLines = $template->generateLines($params);

	$converter = new OfferConverter($db);
	$meta = $converter->prepareInvoiceMeta($template->ref, $params, 'PR-2026-0001');
	$invoiceLines = $converter->convertLinesToInvoice($propalLines, $meta);
	$costSummary = $converter->generateCostSummary($invoiceLines);
	$invoiceNote = $converter->generateInvoiceNote($meta);

	// Cost summary
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("CostSummary").'</th></tr>';

	print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("TotalMaterials").'</td>';
	print '<td class="right"><strong>'.price($costSummary['total_materials_ht']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong> ('.$costSummary['material_percent'].'%)</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("TotalLabor").'</td>';
	print '<td class="right"><strong>'.price($costSummary['total_labor_ht']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong> ('.$costSummary['labor_percent'].'%)</td></tr>';

	print '<tr class="oddeven"><td><strong>'.$langs->trans("PriceHT").'</strong></td>';
	print '<td class="right"><strong>'.price($costSummary['total_ht']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong></td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("VATAmount").'</td>';
	print '<td class="right">'.price($costSummary['total_vat']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';

	print '<tr class="oddeven"><td><strong>'.$langs->trans("PriceTTC").'</strong></td>';
	print '<td class="right"><strong>'.price($costSummary['total_ttc']).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</strong></td></tr>';

	print '</table>';
	print '</div>';

	// Category breakdown
	if (!empty($costSummary['categories'])) {
		print '<br>';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("ConstructionCategory").'</th></tr>';

		foreach ($costSummary['categories'] as $cat => $amount) {
			$catKey = 'WorkTemplateCategory'.ucfirst($cat);
			print '<tr class="oddeven"><td>'.$langs->trans($catKey).'</td>';
			print '<td class="right">'.price($amount).' '.getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR').'</td></tr>';
		}

		print '</table>';
		print '</div>';
	}

	// Invoice lines preview
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="6">'.$langs->trans("GeneratedLines").' ('.$langs->trans("OriginalProposal").': '.dol_escape_htmltag($meta['propal_ref']).')</th></tr>';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("WorkTemplateLabel").'</th>';
	print '<th class="center">'.$langs->trans("WorkTemplateStepType").'</th>';
	print '<th class="right">'.$langs->trans("Qty").'</th>';
	print '<th class="right">'.$langs->trans("WorkTemplateStepUnitPrice").'</th>';
	print '<th class="right">'.$langs->trans("PriceHT").'</th>';
	print '<th class="right">'.$langs->trans("PriceTTC").'</th>';
	print '</tr>';

	foreach ($invoiceLines as $line) {
		$stepTypeLabel = '';
		if (isset($line['cc_meta']['step_type'])) {
			if ($line['cc_meta']['step_type'] === 'service') {
				$stepTypeLabel = $langs->trans("WorkTemplateStepTypeService");
			} else {
				$stepTypeLabel = $langs->trans("WorkTemplateStepTypeProduct");
			}
		}

		print '<tr class="oddeven">';
		print '<td>'.dol_escape_htmltag($line['description']).'</td>';
		print '<td class="center">'.$stepTypeLabel.'</td>';
		print '<td class="right nowrap">'.price($line['qty']).' '.$line['unit_code'].'</td>';
		print '<td class="right nowrap">'.price($line['unit_price_ht']).'</td>';
		print '<td class="right nowrap">'.price($line['total_ht']).'</td>';
		print '<td class="right nowrap">'.price($line['total_ttc']).'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	// Metadata traceability info
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("InvoiceMetadata").'</th></tr>';

	print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("OriginalTemplate").'</td>';
	print '<td>'.dol_escape_htmltag($meta['work_template_ref']).'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("OriginalProposal").'</td>';
	print '<td>'.dol_escape_htmltag($meta['propal_ref']).'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("OriginalParameters").'</td>';
	$paramStr = '';
	foreach ($meta['params'] as $key => $value) {
		$paramStr .= $key.'='.$value.' ';
	}
	print '<td>'.dol_escape_htmltag(trim($paramStr)).'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("ConversionDate").'</td>';
	print '<td>'.dol_escape_htmltag($meta['generated_date']).'</td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="info">'.$langs->trans("PreservedMetadata").'</div>';
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
