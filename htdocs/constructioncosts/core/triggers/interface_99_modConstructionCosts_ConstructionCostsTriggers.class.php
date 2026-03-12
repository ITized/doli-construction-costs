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
 * \file       htdocs/constructioncosts/core/triggers/interface_99_modConstructionCosts_ConstructionCostsTriggers.class.php
 * \ingroup    constructioncosts
 * \brief      Trigger class for ConstructionCosts module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 * Class InterfaceConstructionCostsTriggers
 *
 * Handles Dolibarr events related to construction cost management.
 */
class InterfaceConstructionCostsTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "ConstructionCosts triggers.";
		$this->version = '1.0.0';
		$this->picto = 'building';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Function called when a Dolibarr event is triggered.
	 *
	 * @param string     $action    Event action code
	 * @param object     $object    Object concerned
	 * @param User       $user      User who triggered the event
	 * @param Translate  $langs     Language object
	 * @param Conf       $conf      Configuration object
	 * @return int                  0=OK, -1=Error
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('constructioncosts')) {
			return 0;
		}

		switch ($action) {
			// Product/Service events that may need price recalculation
			case 'PRODUCT_CREATE':
			case 'PRODUCT_MODIFY':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				break;

			// BOM events
			case 'BOM_CREATE':
			case 'BOM_MODIFY':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				break;

			default:
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . (isset($object->id) ? $object->id : ''));
				break;
		}

		return 0;
	}
}
