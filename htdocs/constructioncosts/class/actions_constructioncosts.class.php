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
 * \file       htdocs/constructioncosts/class/actions_constructioncosts.class.php
 * \ingroup    constructioncosts
 * \brief      Hook actions class for ConstructionCosts module
 */

/**
 * Class ActionsConstructionCosts
 *
 * Provides hook handlers for integrating construction cost data
 * into Dolibarr product/service cards, BOMs, and proposals.
 */
class ActionsConstructionCosts
{
	/**
	 * @var object Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * @var array Hook results
	 */
	public $results = array();

	/**
	 * @var string HTML output for hooks
	 */
	public $resprints = '';

	/**
	 * Constructor
	 *
	 * @param object $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the addMoreActionsButtons function.
	 * Called in product/service card to add construction cost buttons.
	 *
	 * @param array  $parameters Hook parameters
	 * @param object $object     Current object
	 * @param string $action     Current action
	 * @return int               0=OK, 1=Replace standard code, -1=KO
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;

		$contexts = explode(':', $parameters['context']);

		if (in_array('productcard', $contexts) || in_array('servicecard', $contexts)) {
			if (isModEnabled('constructioncosts') && $user->hasRight('constructioncosts', 'pricingrule', 'read')) {
				$langs->load('constructioncosts@constructioncosts');
				// Button to view construction cost pricing for this product
				$this->resprints .= '<a class="butAction" href="' . dol_buildpath('/constructioncosts/constructioncostsindex.php?fk_product=' . $object->id, 1) . '">';
				$this->resprints .= $langs->trans('ViewConstructionCosts');
				$this->resprints .= '</a>';
			}
		}

		return 0;
	}

	/**
	 * Overloading the formObjectOptions function.
	 * Called in product/service card to add extra fields display.
	 *
	 * @param array  $parameters Hook parameters
	 * @param object $object     Current object
	 * @param string $action     Current action
	 * @return int               0=OK, 1=Replace standard code, -1=KO
	 */
	public function formObjectOptions($parameters, &$object, &$action)
	{
		return 0;
	}
}
