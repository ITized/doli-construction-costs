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
 * \file       htdocs/constructioncosts/class/worktemplate.class.php
 * \ingroup    constructioncosts
 * \brief      Work Template engine for automated offer generation
 */

/**
 * Class WorkTemplateStep
 *
 * Represents a single step/line in a work template.
 * Each step defines a product or service with quantity formulas
 * that are computed from input parameters (e.g., surface area).
 */
class WorkTemplateStep
{
	/** @var int Step position/order */
	public $position = 0;

	/** @var string Step reference */
	public $ref = '';

	/** @var string Step label */
	public $label = '';

	/** @var string Step description */
	public $description = '';

	/** @var string Step type: 'product', 'service' */
	public $step_type = 'product';

	/** @var string Product/service reference to use */
	public $product_ref = '';

	/**
	 * @var string Quantity formula expression.
	 * Supports variables: {area}, {perimeter}, {length}, {height}, {units}, {rooms}
	 * Examples: "{area} * 0.3", "{area} / 10", "2", "{units}"
	 */
	public $qty_formula = '1';

	/** @var string Unit of measure code (M2, ML, U, H, L, KG, etc.) */
	public $unit_code = 'U';

	/** @var float Unit price HT (0 = use product's price) */
	public $unit_price_ht = 0;

	/** @var float MO hourly rate for service steps (0 = use global default) */
	public $mo_hourly_rate = 0;

	/** @var float MO hours per unit for service steps */
	public $mo_hours_per_unit = 0;

	/** @var float VAT rate percentage */
	public $vat_rate = 20.0;

	/** @var bool Whether this step is optional (user can skip) */
	public $is_optional = false;

	/** @var string User prompt question when step requires user input */
	public $user_prompt = '';

	/** @var string Default answer for the user prompt */
	public $default_answer = '';

	/**
	 * @var string Category of this step for grouping in proposals.
	 * E.g.: 'preparation', 'protection', 'removal', 'application', 'finishing', 'waste'
	 */
	public $step_category = '';

	/**
	 * Evaluate the quantity formula with given parameters.
	 *
	 * @param array $params Associative array of input parameters
	 *                      Keys: area, perimeter, length, width, height, units, rooms, layers
	 * @return float Computed quantity
	 */
	public function computeQuantity($params = array())
	{
		$formula = $this->qty_formula;

		if (empty($formula) || $formula === '0') {
			return 0.0;
		}

		// Replace parameter placeholders
		$replacements = array(
			'{area}' => isset($params['area']) ? (float) $params['area'] : 0,
			'{perimeter}' => isset($params['perimeter']) ? (float) $params['perimeter'] : 0,
			'{length}' => isset($params['length']) ? (float) $params['length'] : 0,
			'{width}' => isset($params['width']) ? (float) $params['width'] : 0,
			'{height}' => isset($params['height']) ? (float) $params['height'] : 0,
			'{units}' => isset($params['units']) ? (float) $params['units'] : 1,
			'{rooms}' => isset($params['rooms']) ? (float) $params['rooms'] : 1,
			'{layers}' => isset($params['layers']) ? (float) $params['layers'] : 1,
		);

		$expression = str_replace(
			array_keys($replacements),
			array_values($replacements),
			$formula
		);

		// Validate: only allow numbers, operators, spaces, dots, parentheses
		if (!preg_match('/^[\d\s\.\+\-\*\/\(\)]+$/', $expression)) {
			dol_syslog('WorkTemplateStep::computeQuantity invalid expression: ' . $expression, LOG_WARNING);
			return 0.0;
		}

		// Safe evaluation using basic arithmetic
		$result = $this->safeEval($expression);
		return max(0.0, round((float) $result, 4));
	}

	/**
	 * Safely evaluate a simple arithmetic expression.
	 * Only supports +, -, *, / and parentheses with numeric operands.
	 *
	 * @param string $expression The arithmetic expression
	 * @return float The result
	 */
	private function safeEval($expression)
	{
		$expression = trim($expression);

		// Handle empty or trivially numeric
		if ($expression === '' || $expression === '0') {
			return 0.0;
		}
		if (is_numeric($expression)) {
			return (float) $expression;
		}

		// Recursively handle parentheses (innermost first)
		while (preg_match('/\(([^()]+)\)/', $expression, $matches)) {
			$inner = $this->safeEval($matches[1]);
			$expression = str_replace($matches[0], (string) $inner, $expression);
		}

		// Handle multiplication and division first (left to right)
		while (preg_match('/(-?\d+\.?\d*)\s*([*\/])\s*(-?\d+\.?\d*)/', $expression, $matches)) {
			$left = (float) $matches[1];
			$op = $matches[2];
			$right = (float) $matches[3];

			if ($op === '*') {
				$result = $left * $right;
			} else {
				$result = $right != 0 ? $left / $right : 0;
			}
			$expression = str_replace($matches[0], (string) $result, $expression);
		}

		// Handle addition and subtraction (left to right)
		while (preg_match('/(-?\d+\.?\d*)\s*([+\-])\s*(\d+\.?\d*)/', $expression, $matches)) {
			$left = (float) $matches[1];
			$op = $matches[2];
			$right = (float) $matches[3];

			$result = $op === '+' ? $left + $right : $left - $right;
			$expression = str_replace($matches[0], (string) $result, $expression);
		}

		return is_numeric(trim($expression)) ? (float) $expression : 0.0;
	}
}


/**
 * Class WorkTemplate
 *
 * A work template is a "recipe" for a common construction task.
 * When the user specifies high-level parameters (e.g., "35m² of wall to repaint"),
 * the template automatically generates all required proposal lines:
 * products (paint, primer, protection film, etc.) and services (labor hours).
 *
 * Each template contains ordered steps that are evaluated with input parameters
 * to produce fully priced line items for a Dolibarr proposal (propal).
 */
class WorkTemplate
{
	/** @var string Module name */
	public $module = 'constructioncosts';

	/** @var string Element type */
	public $element = 'worktemplate';

	/** @var string Table name */
	public $table_element = 'constructioncosts_worktemplate';

	/** @var int ID */
	public $id;

	/** @var int Entity */
	public $entity;

	/** @var string Unique reference */
	public $ref = '';

	/** @var string Template label */
	public $label = '';

	/** @var string Template description */
	public $description = '';

	/** @var string Construction category code (PEINTURE, ELECTRICITE, etc.) */
	public $category_code = '';

	/**
	 * @var string Pricing mode: 'detailed' (line-by-line) or 'forfait' (lump sum)
	 */
	public $pricing_mode = 'detailed';

	/** @var float Default margin percentage */
	public $margin_percent = 0;

	/** @var float Default VAT rate */
	public $vat_rate = 20.0;

	/** @var int Status: 0=draft, 1=active */
	public $status = 1;

	/** @var WorkTemplateStep[] Array of template steps */
	public $steps = array();

	/** @var string Date of creation */
	public $date_creation;

	/** @var int User who created */
	public $fk_user_create;

	// Status constants
	const STATUS_DRAFT = 0;
	const STATUS_ACTIVE = 1;

	// Pricing modes
	const PRICING_DETAILED = 'detailed';
	const PRICING_FORFAIT = 'forfait';

	/**
	 * Add a step to the template.
	 *
	 * @param WorkTemplateStep $step Step to add
	 * @return void
	 */
	public function addStep($step)
	{
		if (empty($step->position)) {
			$step->position = count($this->steps) + 1;
		}
		$this->steps[] = $step;
	}

	/**
	 * Generate proposal lines from the template with given parameters.
	 *
	 * Returns an array of line items ready to be added to a Dolibarr proposal.
	 * Each line contains: label, description, qty, unit_price_ht, vat_rate, etc.
	 *
	 * @param array $params Input parameters (area, perimeter, height, layers, etc.)
	 * @param array $userAnswers Answers to optional prompts (step_ref => answer)
	 * @return array Array of proposal line arrays
	 */
	public function generateLines($params = array(), $userAnswers = array())
	{
		$lines = array();
		$globalMargin = (float) $this->margin_percent;
		$defaultMoRate = (float) getDolGlobalString('CONSTRUCTIONCOSTS_MO_HOURLY_RATE', '45.00');

		// Sort steps by position
		$sortedSteps = $this->steps;
		usort($sortedSteps, function ($a, $b) {
			return $a->position - $b->position;
		});

		foreach ($sortedSteps as $step) {
			// Skip optional steps the user declined
			if ($step->is_optional && !empty($step->user_prompt)) {
				$answer = isset($userAnswers[$step->ref]) ? $userAnswers[$step->ref] : $step->default_answer;
				if (empty($answer) || strtolower($answer) === 'no' || strtolower($answer) === 'non') {
					continue;
				}
			}

			$qty = $step->computeQuantity($params);
			if ($qty <= 0 && !$step->is_optional) {
				continue;
			}

			// Calculate unit price
			$unitPrice = (float) $step->unit_price_ht;
			if ($step->step_type === 'service' && $step->mo_hours_per_unit > 0) {
				$moRate = $step->mo_hourly_rate > 0 ? (float) $step->mo_hourly_rate : $defaultMoRate;
				$unitPrice = $moRate * (float) $step->mo_hours_per_unit;
			}

			// Apply margin
			if ($globalMargin > 0) {
				$unitPrice = $unitPrice * (1 + $globalMargin / 100);
			}

			$line = array(
				'position' => $step->position,
				'ref' => $step->ref,
				'label' => $step->label,
				'description' => $step->description,
				'step_type' => $step->step_type,
				'product_ref' => $step->product_ref,
				'qty' => (float) price2num($qty, 'MS'),
				'unit_code' => $step->unit_code,
				'unit_price_ht' => (float) price2num($unitPrice, 'MU'),
				'vat_rate' => (float) $step->vat_rate,
				'total_ht' => (float) price2num($qty * $unitPrice, 'MT'),
				'step_category' => $step->step_category,
				'is_optional' => $step->is_optional,
			);

			$lines[] = $line;
		}

		return $lines;
	}

	/**
	 * Generate a lump-sum (forfait) price for the entire template.
	 *
	 * @param array $params Input parameters
	 * @param array $userAnswers User answers to prompts
	 * @return array Single-line array with forfait pricing
	 */
	public function generateForfait($params = array(), $userAnswers = array())
	{
		$lines = $this->generateLines($params, $userAnswers);
		$totalHT = 0;

		foreach ($lines as $line) {
			$totalHT += $line['total_ht'];
		}

		return array(
			array(
				'position' => 1,
				'ref' => $this->ref . '-FORFAIT',
				'label' => $this->label . ' (Forfait)',
				'description' => $this->description . "\n" . $this->generateForfaitDescription($lines),
				'step_type' => 'mixed',
				'product_ref' => '',
				'qty' => 1,
				'unit_code' => 'F',
				'unit_price_ht' => (float) price2num($totalHT, 'MT'),
				'vat_rate' => (float) $this->vat_rate,
				'total_ht' => (float) price2num($totalHT, 'MT'),
				'step_category' => 'forfait',
				'is_optional' => false,
				'detail_lines' => $lines,
			),
		);
	}

	/**
	 * Generate description text for a forfait line showing breakdown.
	 *
	 * @param array $lines Detail lines
	 * @return string Description text
	 */
	private function generateForfaitDescription($lines)
	{
		$desc = '';
		foreach ($lines as $line) {
			$desc .= '- ' . $line['label'] . ': ' . $line['qty'] . ' ' . $line['unit_code'];
			$desc .= "\n";
		}
		return $desc;
	}

	/**
	 * Get all prompts that need user answers for this template.
	 *
	 * @return array Array of arrays with keys: ref, prompt, default_answer
	 */
	public function getRequiredPrompts()
	{
		$prompts = array();
		foreach ($this->steps as $step) {
			if ($step->is_optional && !empty($step->user_prompt)) {
				$prompts[] = array(
					'ref' => $step->ref,
					'prompt' => $step->user_prompt,
					'default_answer' => $step->default_answer,
					'label' => $step->label,
				);
			}
		}
		return $prompts;
	}

	/**
	 * Get the total estimated price HT for given parameters.
	 *
	 * @param array $params Input parameters
	 * @param array $userAnswers User answers
	 * @return float Total price HT
	 */
	public function estimateTotalHT($params = array(), $userAnswers = array())
	{
		$lines = $this->generateLines($params, $userAnswers);
		$total = 0;
		foreach ($lines as $line) {
			$total += $line['total_ht'];
		}
		return (float) price2num($total, 'MT');
	}

	/**
	 * Validate the template.
	 *
	 * @return array Array of error messages (empty if valid)
	 */
	public function validate()
	{
		$errors = array();

		if (empty($this->ref)) {
			$errors[] = 'Reference is required';
		}

		if (empty($this->label)) {
			$errors[] = 'Label is required';
		}

		if (!in_array($this->pricing_mode, array(self::PRICING_DETAILED, self::PRICING_FORFAIT))) {
			$errors[] = 'Invalid pricing mode: ' . $this->pricing_mode;
		}

		if (empty($this->steps)) {
			$errors[] = 'Template must have at least one step';
		}

		foreach ($this->steps as $idx => $step) {
			if (empty($step->label)) {
				$errors[] = 'Step ' . ($idx + 1) . ': label is required';
			}
		}

		return $errors;
	}

	/**
	 * Initialize the template with specimen data for "Repaint papered wall".
	 *
	 * @return int 1
	 */
	public function initAsSpecimenRepaintPaperedWall()
	{
		$this->ref = 'WT-REPAINT-PAPER';
		$this->label = 'Repaint wall (currently papered)';
		$this->description = 'Complete workflow: protection, wallpaper removal, surface preparation, primer + paint application, waste disposal';
		$this->category_code = 'PEINTURE';
		$this->pricing_mode = self::PRICING_DETAILED;
		$this->margin_percent = 20.0;
		$this->vat_rate = 10.0; // Renovation reduced rate
		$this->status = self::STATUS_ACTIVE;

		// Step 1: Worksite protection
		$s1 = new WorkTemplateStep();
		$s1->position = 1;
		$s1->ref = 'PROTECT-FILM';
		$s1->label = 'Protection film for floor';
		$s1->description = 'Plastic protection film to cover floor and furniture';
		$s1->step_type = 'product';
		$s1->product_ref = 'PROT-FILM-50';
		$s1->qty_formula = '{area} * 1.1';
		$s1->unit_code = 'M2';
		$s1->unit_price_ht = 0.80;
		$s1->vat_rate = 10.0;
		$s1->step_category = 'protection';
		$this->addStep($s1);

		// Step 2: Protection labor
		$s2 = new WorkTemplateStep();
		$s2->position = 2;
		$s2->ref = 'PROTECT-MO';
		$s2->label = 'Worksite protection labor';
		$s2->description = 'Setting up protection on floor, furniture, and fixtures';
		$s2->step_type = 'service';
		$s2->qty_formula = '{area}';
		$s2->unit_code = 'M2';
		$s2->mo_hours_per_unit = 0.05;
		$s2->mo_hourly_rate = 42.00;
		$s2->vat_rate = 10.0;
		$s2->step_category = 'protection';
		$this->addStep($s2);

		// Step 3: Wallpaper removal product (steamer rental or stripper)
		$s3 = new WorkTemplateStep();
		$s3->position = 3;
		$s3->ref = 'STRIP-PRODUCT';
		$s3->label = 'Wallpaper stripper/steamer';
		$s3->description = 'Wallpaper stripping solution or steamer rental';
		$s3->step_type = 'product';
		$s3->product_ref = 'DECOLLE-PAPIER-5L';
		$s3->qty_formula = '{area} / 30';
		$s3->unit_code = 'U';
		$s3->unit_price_ht = 12.00;
		$s3->vat_rate = 10.0;
		$s3->step_category = 'removal';
		$this->addStep($s3);

		// Step 4: Wallpaper removal labor
		$s4 = new WorkTemplateStep();
		$s4->position = 4;
		$s4->ref = 'STRIP-MO';
		$s4->label = 'Wallpaper removal labor';
		$s4->description = 'Stripping existing wallpaper from walls';
		$s4->step_type = 'service';
		$s4->qty_formula = '{area}';
		$s4->unit_code = 'M2';
		$s4->mo_hours_per_unit = 0.35;
		$s4->mo_hourly_rate = 42.00;
		$s4->vat_rate = 10.0;
		$s4->step_category = 'removal';
		$this->addStep($s4);

		// Step 5: Surface preparation (filling/sanding)
		$s5 = new WorkTemplateStep();
		$s5->position = 5;
		$s5->ref = 'PREP-ENDUIT';
		$s5->label = 'Surface filler/compound';
		$s5->description = 'Filling compound for surface imperfections after paper removal';
		$s5->step_type = 'product';
		$s5->product_ref = 'ENDUIT-LISS-5KG';
		$s5->qty_formula = '{area} * 0.3';
		$s5->unit_code = 'KG';
		$s5->unit_price_ht = 3.50;
		$s5->vat_rate = 10.0;
		$s5->is_optional = true;
		$s5->user_prompt = 'Does the wall need surface filling after paper removal?';
		$s5->default_answer = 'yes';
		$s5->step_category = 'preparation';
		$this->addStep($s5);

		// Step 6: Surface preparation labor
		$s6 = new WorkTemplateStep();
		$s6->position = 6;
		$s6->ref = 'PREP-MO';
		$s6->label = 'Surface preparation labor';
		$s6->description = 'Filling, sanding and preparing wall surface';
		$s6->step_type = 'service';
		$s6->qty_formula = '{area}';
		$s6->unit_code = 'M2';
		$s6->mo_hours_per_unit = 0.15;
		$s6->mo_hourly_rate = 42.00;
		$s6->vat_rate = 10.0;
		$s6->is_optional = true;
		$s6->user_prompt = 'Does the wall need surface filling after paper removal?';
		$s6->default_answer = 'yes';
		$s6->step_category = 'preparation';
		$this->addStep($s6);

		// Step 7: Primer
		$s7 = new WorkTemplateStep();
		$s7->position = 7;
		$s7->ref = 'PRIMER';
		$s7->label = 'Primer/undercoat';
		$s7->description = 'Acrylic primer for bare/prepared wall';
		$s7->step_type = 'product';
		$s7->product_ref = 'SOUS-COUCHE-10L';
		$s7->qty_formula = '{area} / 10';
		$s7->unit_code = 'U';
		$s7->unit_price_ht = 28.00;
		$s7->vat_rate = 10.0;
		$s7->step_category = 'application';
		$this->addStep($s7);

		// Step 8: Primer application labor
		$s8 = new WorkTemplateStep();
		$s8->position = 8;
		$s8->ref = 'PRIMER-MO';
		$s8->label = 'Primer application labor';
		$s8->description = 'Applying primer coat to prepared wall';
		$s8->step_type = 'service';
		$s8->qty_formula = '{area}';
		$s8->unit_code = 'M2';
		$s8->mo_hours_per_unit = 0.12;
		$s8->mo_hourly_rate = 42.00;
		$s8->vat_rate = 10.0;
		$s8->step_category = 'application';
		$this->addStep($s8);

		// Step 9: Paint
		$s9 = new WorkTemplateStep();
		$s9->position = 9;
		$s9->ref = 'PAINT';
		$s9->label = 'Finish paint';
		$s9->description = 'Acrylic paint for finish coat(s)';
		$s9->step_type = 'product';
		$s9->product_ref = 'PEINT-ACR-10';
		$s9->qty_formula = '{area} * {layers} / 10';
		$s9->unit_code = 'U';
		$s9->unit_price_ht = 35.00;
		$s9->vat_rate = 10.0;
		$s9->step_category = 'application';
		$this->addStep($s9);

		// Step 10: Paint application labor
		$s10 = new WorkTemplateStep();
		$s10->position = 10;
		$s10->ref = 'PAINT-MO';
		$s10->label = 'Paint application labor';
		$s10->description = 'Applying finish paint coat(s)';
		$s10->step_type = 'service';
		$s10->qty_formula = '{area} * {layers}';
		$s10->unit_code = 'M2';
		$s10->mo_hours_per_unit = 0.15;
		$s10->mo_hourly_rate = 42.00;
		$s10->vat_rate = 10.0;
		$s10->step_category = 'application';
		$this->addStep($s10);

		// Step 11: Waste disposal
		$s11 = new WorkTemplateStep();
		$s11->position = 11;
		$s11->ref = 'WASTE';
		$s11->label = 'Waste disposal';
		$s11->description = 'Collection and disposal of wallpaper waste and packaging';
		$s11->step_type = 'service';
		$s11->qty_formula = '{area} * 0.02';
		$s11->unit_code = 'M3';
		$s11->mo_hours_per_unit = 0;
		$s11->unit_price_ht = 45.00;
		$s11->vat_rate = 10.0;
		$s11->step_category = 'waste';
		$this->addStep($s11);

		// Step 12: Cleanup labor
		$s12 = new WorkTemplateStep();
		$s12->position = 12;
		$s12->ref = 'CLEANUP-MO';
		$s12->label = 'Site cleanup labor';
		$s12->description = 'Removing protection, cleaning up worksite';
		$s12->step_type = 'service';
		$s12->qty_formula = '{area}';
		$s12->unit_code = 'M2';
		$s12->mo_hours_per_unit = 0.03;
		$s12->mo_hourly_rate = 42.00;
		$s12->vat_rate = 10.0;
		$s12->step_category = 'finishing';
		$this->addStep($s12);

		$this->date_creation = date('Y-m-d H:i:s');

		return 1;
	}
}
