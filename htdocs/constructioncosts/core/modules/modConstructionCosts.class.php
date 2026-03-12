<?php
/* Copyright (C) 2024-2026	ITized <https://github.com/ITized>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \defgroup   constructioncosts     Module ConstructionCosts
 * \brief      Construction Costs module descriptor.
 *
 * \file       htdocs/constructioncosts/core/modules/modConstructionCosts.class.php
 * \ingroup    constructioncosts
 * \brief      Description and activation file for module ConstructionCosts
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 * Description and activation class for module ConstructionCosts
 *
 * Provides comprehensive BOM, product and service management for the construction sector
 * with configurable pricing rules, margin management and supplier integration.
 */
class modConstructionCosts extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Module ID (must be unique). Use a free id from https://wiki.dolibarr.org/index.php/List_of_modules_id
		$this->numero = 500100;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'constructioncosts';

		// Family: 'products' since this module manages construction products/services
		$this->family = "products";

		// Module position in the family on 2 digits
		$this->module_position = '90';

		// Module label (no space allowed)
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description
		$this->description = "ConstructionCostsDescription";
		$this->descriptionlong = "ConstructionCostsDescriptionLong";

		// Author
		$this->editor_name = 'ITized';
		$this->editor_url = 'https://github.com/ITized';
		$this->editor_squarred_logo = '';

		// Module version
		$this->version = '1.0.0';

		// Key used in llx_const table to save module status
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Module icon
		$this->picto = 'building';

		// Define some features supported by module
		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(
				'data' => array(
					'productcard',
					'servicecard',
					'bomcard',
					'propalcard',
					'invoicecard',
				),
			),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0
		);

		// Data directories to create when module is enabled
		$this->dirs = array("/constructioncosts/temp");

		// Config pages
		$this->config_page_url = array("setup.php@constructioncosts");

		// Dependencies
		$this->hidden = getDolGlobalInt('MODULE_CONSTRUCTIONCOSTS_DISABLED');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		// The language file dedicated to this module
		$this->langfiles = array("constructioncosts@constructioncosts");

		// Prerequisites
		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(19, 0);
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		// Constants
		$this->const = array(
			1 => array('CONSTRUCTIONCOSTS_MO_HOURLY_RATE', 'chaine', '45.00', 'Default Main d\'Oeuvre (labor) hourly rate in EUR', 1),
			2 => array('CONSTRUCTIONCOSTS_DEFAULT_MARGIN_PERCENT', 'chaine', '20.00', 'Default margin percentage for products', 1),
			3 => array('CONSTRUCTIONCOSTS_SERVICE_BASE_MULTIPLIER', 'chaine', '1.00', 'Base multiplier for service pricing', 1),
			4 => array('CONSTRUCTIONCOSTS_DEFAULT_VAT_RATE', 'chaine', '20.0', 'Default VAT rate for construction products/services', 1),
			5 => array('CONSTRUCTIONCOSTS_ENABLE_SUPPLIER_SYNC', 'chaine', '0', 'Enable automatic supplier price synchronization', 1),
			6 => array('CONSTRUCTIONCOSTS_CSV_SEPARATOR', 'chaine', ';', 'CSV field separator for import/export', 1),
			7 => array('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'chaine', 'EUR', 'Default currency for prices', 1),
		);

		if (!isModEnabled("constructioncosts")) {
			$conf->constructioncosts = new stdClass();
			$conf->constructioncosts->enabled = 0;
		}

		// Tabs: add construction costs tab on product/service cards
		/* BEGIN MODULEBUILDER TABS */
		$this->tabs = array();
		/* END MODULEBUILDER TABS */

		// Dictionaries for construction categories
		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array(
			'langs' => 'constructioncosts@constructioncosts',
			'tabname' => array(
				$this->db->prefix()."c_constructioncosts_category",
				$this->db->prefix()."c_constructioncosts_unit",
			),
			'tablib' => array(
				"ConstructionCategory",
				"ConstructionUnit",
			),
			'tabsql' => array(
				'SELECT f.rowid as rowid, f.code, f.label, f.description, f.active FROM '.$this->db->prefix().'c_constructioncosts_category as f',
				'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'c_constructioncosts_unit as f',
			),
			'tabsqlsort' => array(
				"label ASC",
				"label ASC",
			),
			'tabfield' => array(
				"code,label,description",
				"code,label",
			),
			'tabfieldvalue' => array(
				"code,label,description",
				"code,label",
			),
			'tabfieldinsert' => array(
				"code,label,description",
				"code,label",
			),
			'tabrowid' => array(
				"rowid",
				"rowid",
			),
			'tabcond' => array(
				isModEnabled('constructioncosts'),
				isModEnabled('constructioncosts'),
			),
			'tabhelp' => array(
				array('code' => $langs->trans('ConstructionCategoryCodeHelp'), 'label' => $langs->trans('ConstructionCategoryLabelHelp')),
				array('code' => $langs->trans('ConstructionUnitCodeHelp'), 'label' => $langs->trans('ConstructionUnitLabelHelp')),
			),
		);
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array();
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = array(
			0 => array(
				'label' => 'SyncSupplierPrices',
				'jobtype' => 'method',
				'class' => '/constructioncosts/class/constructioncostscatalog.class.php',
				'objectname' => 'ConstructionCostsCatalog',
				'method' => 'doScheduledJob',
				'parameters' => '',
				'comment' => 'Synchronize supplier prices for construction products',
				'frequency' => 24,
				'unitfrequency' => 3600,
				'status' => 0,
				'test' => 'isModEnabled("constructioncosts")',
				'priority' => 50,
			),
		);
		/* END MODULEBUILDER CRON */

		// Permissions
		$this->rights = array();
		$r = 0;
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$o = 1;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1);
		$this->rights[$r][1] = 'Read construction cost data';
		$this->rights[$r][4] = 'pricingrule';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 2);
		$this->rights[$r][1] = 'Create/Update construction cost data';
		$this->rights[$r][4] = 'pricingrule';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 3);
		$this->rights[$r][1] = 'Delete construction cost data';
		$this->rights[$r][4] = 'pricingrule';
		$this->rights[$r][5] = 'delete';
		$r++;

		$o = 2;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1);
		$this->rights[$r][1] = 'Read supplier configurations';
		$this->rights[$r][4] = 'supplierconfig';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 2);
		$this->rights[$r][1] = 'Create/Update supplier configurations';
		$this->rights[$r][4] = 'supplierconfig';
		$this->rights[$r][5] = 'write';
		$r++;

		$o = 3;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1);
		$this->rights[$r][1] = 'Import construction cost data';
		$this->rights[$r][4] = 'import';
		$this->rights[$r][5] = 'execute';
		$r++;

		$o = 4;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1);
		$this->rights[$r][1] = 'Read work templates';
		$this->rights[$r][4] = 'worktemplate';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 2);
		$this->rights[$r][1] = 'Create/Update work templates';
		$this->rights[$r][4] = 'worktemplate';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 3);
		$this->rights[$r][1] = 'Delete work templates';
		$this->rights[$r][4] = 'worktemplate';
		$this->rights[$r][5] = 'delete';
		$r++;

		$o = 5;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1);
		$this->rights[$r][1] = 'Convert offers to invoices';
		$this->rights[$r][4] = 'offerconvert';
		$this->rights[$r][5] = 'execute';
		$r++;
		/* END MODULEBUILDER PERMISSIONS */


		// Main menu entries
		$this->menu = array();
		$r = 0;

		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu' => '',
			'type' => 'top',
			'titre' => 'ModuleConstructionCostsName',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => '',
			'url' => '/constructioncosts/constructioncostsindex.php',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '1',
			'target' => '',
			'user' => 2,
		);
		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU PRICINGRULE */
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=constructioncosts',
			'type' => 'left',
			'titre' => 'PricingRules',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => 'pricingrule',
			'url' => '/constructioncosts/constructioncostsindex.php?mode=pricingrules',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '$user->hasRight("constructioncosts", "pricingrule", "read")',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=constructioncosts',
			'type' => 'left',
			'titre' => 'SupplierConfigs',
			'prefix' => img_picto('', 'company', 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => 'supplierconfig',
			'url' => '/constructioncosts/constructioncostsindex.php?mode=suppliers',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '$user->hasRight("constructioncosts", "supplierconfig", "read")',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=constructioncosts',
			'type' => 'left',
			'titre' => 'ImportData',
			'prefix' => img_picto('', 'import', 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => 'import',
			'url' => '/constructioncosts/constructioncostsindex.php?mode=import',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '$user->hasRight("constructioncosts", "import", "execute")',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=constructioncosts',
			'type' => 'left',
			'titre' => 'WorkTemplates',
			'prefix' => img_picto('', 'list', 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => 'worktemplate',
			'url' => '/constructioncosts/constructioncostsindex.php?mode=worktemplates',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '$user->hasRight("constructioncosts", "worktemplate", "read")',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=constructioncosts',
			'type' => 'left',
			'titre' => 'SupplierPriceImport',
			'prefix' => img_picto('', 'supplier_proposal', 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => 'supplierpriceimport',
			'url' => '/constructioncosts/constructioncostsindex.php?mode=supplierprices',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '$user->hasRight("constructioncosts", "import", "execute")',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=constructioncosts',
			'type' => 'left',
			'titre' => 'ConvertOfferToInvoice',
			'prefix' => img_picto('', 'bill', 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'constructioncosts',
			'leftmenu' => 'offerconvert',
			'url' => '/constructioncosts/constructioncostsindex.php?mode=offerconvert',
			'langs' => 'constructioncosts@constructioncosts',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('constructioncosts')",
			'perms' => '$user->hasRight("constructioncosts", "offerconvert", "execute")',
			'target' => '',
			'user' => 2,
		);
		/* END MODULEBUILDER LEFTMENU */
	}

	/**
	 * Function called when module is enabled.
	 * The init function adds tabs, constants, boxes, permissions and menus.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/constructioncosts/sql/');

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param string $options Options when disabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
