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
 * \file       htdocs/constructioncosts/class/pricingrule.class.php
 * \ingroup    constructioncosts
 * \brief      CRUD class for PricingRule object
 */

/**
 * Class PricingRule
 *
 * Manages pricing rules for construction products and services.
 *
 * Pricing model:
 * - Products: final_price = (base_price * base_multiplier) * (1 + margin_percent/100)
 * - Services: final_price = (mo_hourly_rate * mo_hours * base_multiplier) * (1 + margin_percent/100)
 * - Mixed:    final_price = ((base_price + mo_hourly_rate * mo_hours) * base_multiplier) * (1 + margin_percent/100)
 */
class PricingRule extends CommonObject
{
	/**
	 * @var string Module name
	 */
	public $module = 'constructioncosts';

	/**
	 * @var string Element type
	 */
	public $element = 'pricingrule';

	/**
	 * @var string Table name
	 */
	public $table_element = 'constructioncosts_pricingrule';

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string Reference
	 */
	public $ref;

	/**
	 * @var string Label
	 */
	public $label;

	/**
	 * @var string Description
	 */
	public $description;

	/**
	 * @var int Product foreign key
	 */
	public $fk_product;

	/**
	 * @var int Category foreign key
	 */
	public $fk_category;

	/**
	 * @var string Rule type: 'product', 'service', 'mixed'
	 */
	public $rule_type = 'product';

	/**
	 * @var float Base price (HT) in default currency
	 */
	public $base_price = 0;

	/**
	 * @var float Base multiplier applied to the price
	 */
	public $base_multiplier = 1.0;

	/**
	 * @var float Main d'Oeuvre (labor) hourly rate
	 */
	public $mo_hourly_rate;

	/**
	 * @var float Number of MO hours
	 */
	public $mo_hours = 0;

	/**
	 * @var float Margin percentage
	 */
	public $margin_percent = 0;

	/**
	 * @var float VAT rate percentage
	 */
	public $vat_rate = 20.0;

	/**
	 * @var string Currency code
	 */
	public $currency_code = 'EUR';

	/**
	 * @var string Start date for rule validity
	 */
	public $date_start;

	/**
	 * @var string End date for rule validity
	 */
	public $date_end;

	/**
	 * @var int Priority (higher = more priority)
	 */
	public $priority = 0;

	/**
	 * @var int Status (0=draft, 1=active)
	 */
	public $status = 1;

	/**
	 * @var string Date of creation
	 */
	public $date_creation;

	/**
	 * @var int User who created
	 */
	public $fk_user_create;

	/**
	 * @var int User who last modified
	 */
	public $fk_user_modif;

	/** @var string Import key */
	public $import_key;

	// Valid rule types
	const RULE_TYPE_PRODUCT = 'product';
	const RULE_TYPE_SERVICE = 'service';
	const RULE_TYPE_MIXED = 'mixed';

	// Status constants
	const STATUS_DRAFT = 0;
	const STATUS_ACTIVE = 1;

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
	 * Calculate the final price based on the pricing rule.
	 *
	 * For products: final = (base_price * multiplier) * (1 + margin/100)
	 * For services: final = (mo_rate * mo_hours * multiplier) * (1 + margin/100)
	 * For mixed:    final = ((base_price + mo_rate * mo_hours) * multiplier) * (1 + margin/100)
	 *
	 * @param float|null $overrideMoRate   Override the MO hourly rate (null = use rule's rate or global default)
	 * @param float|null $overrideMargin   Override the margin percentage (null = use rule's margin)
	 * @return float The computed final price HT
	 */
	public function calculatePrice($overrideMoRate = null, $overrideMargin = null)
	{
		$moRate = $overrideMoRate !== null ? (float) $overrideMoRate : $this->getEffectiveMoRate();
		$margin = $overrideMargin !== null ? (float) $overrideMargin : (float) $this->margin_percent;

		$baseComponent = 0;
		$laborComponent = 0;

		switch ($this->rule_type) {
			case self::RULE_TYPE_PRODUCT:
				$baseComponent = (float) $this->base_price;
				break;

			case self::RULE_TYPE_SERVICE:
				$laborComponent = $moRate * (float) $this->mo_hours;
				break;

			case self::RULE_TYPE_MIXED:
			default:
				$baseComponent = (float) $this->base_price;
				$laborComponent = $moRate * (float) $this->mo_hours;
				break;
		}

		$subtotal = ($baseComponent + $laborComponent) * (float) $this->base_multiplier;
		$finalPrice = $subtotal * (1 + $margin / 100);

		return (float) price2num($finalPrice, 'MT');
	}

	/**
	 * Calculate the price including VAT.
	 *
	 * @param float|null $overrideMoRate   Override MO hourly rate
	 * @param float|null $overrideMargin   Override margin percentage
	 * @return float The computed final price TTC
	 */
	public function calculatePriceTTC($overrideMoRate = null, $overrideMargin = null)
	{
		$priceHT = $this->calculatePrice($overrideMoRate, $overrideMargin);
		$priceTTC = $priceHT * (1 + (float) $this->vat_rate / 100);

		return (float) price2num($priceTTC, 'MT');
	}

	/**
	 * Get the labor cost component only.
	 *
	 * @param float|null $overrideMoRate Override MO hourly rate
	 * @return float Labor cost
	 */
	public function getLaborCost($overrideMoRate = null)
	{
		$moRate = $overrideMoRate !== null ? (float) $overrideMoRate : $this->getEffectiveMoRate();
		return (float) price2num($moRate * (float) $this->mo_hours, 'MT');
	}

	/**
	 * Get the material cost component only.
	 *
	 * @return float Material cost (base_price * multiplier)
	 */
	public function getMaterialCost()
	{
		return (float) price2num((float) $this->base_price * (float) $this->base_multiplier, 'MT');
	}

	/**
	 * Get the margin amount.
	 *
	 * @param float|null $overrideMoRate Override MO hourly rate
	 * @return float Margin amount
	 */
	public function getMarginAmount($overrideMoRate = null)
	{
		$moRate = $overrideMoRate !== null ? (float) $overrideMoRate : $this->getEffectiveMoRate();
		$baseComponent = (float) $this->base_price;
		$laborComponent = $moRate * (float) $this->mo_hours;
		$subtotal = ($baseComponent + $laborComponent) * (float) $this->base_multiplier;

		return (float) price2num($subtotal * (float) $this->margin_percent / 100, 'MT');
	}

	/**
	 * Get the effective MO hourly rate.
	 * Uses the rule's rate if set, otherwise falls back to global default.
	 *
	 * @return float Effective MO hourly rate
	 */
	public function getEffectiveMoRate()
	{
		if ($this->mo_hourly_rate !== null && $this->mo_hourly_rate > 0) {
			return (float) $this->mo_hourly_rate;
		}

		$globalRate = getDolGlobalString('CONSTRUCTIONCOSTS_MO_HOURLY_RATE', '45.00');
		return (float) $globalRate;
	}

	/**
	 * Check if the rule is currently valid (based on date range).
	 *
	 * @param string|null $date Date to check (Y-m-d format), null for today
	 * @return bool True if the rule is valid
	 */
	public function isValidAtDate($date = null)
	{
		if ($date === null) {
			$date = date('Y-m-d');
		}

		if ($this->status != self::STATUS_ACTIVE) {
			return false;
		}

		if (!empty($this->date_start) && $date < $this->date_start) {
			return false;
		}

		if (!empty($this->date_end) && $date > $this->date_end) {
			return false;
		}

		return true;
	}

	/**
	 * Validate the pricing rule data.
	 *
	 * @return array Array of error messages (empty if valid)
	 */
	public function validate()
	{
		$errors = array();

		if (empty($this->ref)) {
			$errors[] = 'Reference is required';
		}

		if (!in_array($this->rule_type, array(self::RULE_TYPE_PRODUCT, self::RULE_TYPE_SERVICE, self::RULE_TYPE_MIXED))) {
			$errors[] = 'Invalid rule type: ' . $this->rule_type;
		}

		if ($this->base_price < 0) {
			$errors[] = 'Base price cannot be negative';
		}

		if ($this->base_multiplier <= 0) {
			$errors[] = 'Base multiplier must be positive';
		}

		if ($this->mo_hours < 0) {
			$errors[] = 'MO hours cannot be negative';
		}

		if ($this->margin_percent < -100) {
			$errors[] = 'Margin percent cannot be less than -100%';
		}

		if ($this->vat_rate < 0) {
			$errors[] = 'VAT rate cannot be negative';
		}

		if (!empty($this->date_start) && !empty($this->date_end) && $this->date_start > $this->date_end) {
			$errors[] = 'Start date cannot be after end date';
		}

		return $errors;
	}

	/**
	 * Initialize object with specimen data for testing.
	 *
	 * @return int 1
	 */
	public function initAsSpecimen()
	{
		$this->ref = 'PR-SPECIMEN-001';
		$this->label = 'Specimen Pricing Rule';
		$this->description = 'A specimen pricing rule for testing';
		$this->rule_type = self::RULE_TYPE_MIXED;
		$this->base_price = 100.00;
		$this->base_multiplier = 1.0;
		$this->mo_hourly_rate = 45.00;
		$this->mo_hours = 2.0;
		$this->margin_percent = 20.0;
		$this->vat_rate = 20.0;
		$this->currency_code = 'EUR';
		$this->status = self::STATUS_ACTIVE;
		$this->date_creation = date('Y-m-d H:i:s');

		return 1;
	}

	/**
	 * Get a price breakdown as an associative array.
	 *
	 * @param float|null $overrideMoRate Override MO hourly rate
	 * @param float|null $overrideMargin Override margin percentage
	 * @return array Price breakdown with keys: material_cost, labor_cost, subtotal, margin_amount, price_ht, vat_amount, price_ttc
	 */
	public function getPriceBreakdown($overrideMoRate = null, $overrideMargin = null)
	{
		$moRate = $overrideMoRate !== null ? (float) $overrideMoRate : $this->getEffectiveMoRate();
		$margin = $overrideMargin !== null ? (float) $overrideMargin : (float) $this->margin_percent;

		$materialCost = (float) $this->base_price;
		$laborCost = $moRate * (float) $this->mo_hours;
		$subtotal = ($materialCost + $laborCost) * (float) $this->base_multiplier;
		$marginAmount = $subtotal * $margin / 100;
		$priceHT = $subtotal + $marginAmount;
		$vatAmount = $priceHT * (float) $this->vat_rate / 100;
		$priceTTC = $priceHT + $vatAmount;

		return array(
			'material_cost' => (float) price2num($materialCost, 'MT'),
			'labor_cost' => (float) price2num($laborCost, 'MT'),
			'subtotal' => (float) price2num($subtotal, 'MT'),
			'margin_percent' => $margin,
			'margin_amount' => (float) price2num($marginAmount, 'MT'),
			'price_ht' => (float) price2num($priceHT, 'MT'),
			'vat_rate' => (float) $this->vat_rate,
			'vat_amount' => (float) price2num($vatAmount, 'MT'),
			'price_ttc' => (float) price2num($priceTTC, 'MT'),
		);
	}
}
