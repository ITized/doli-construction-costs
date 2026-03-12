<?php
/* Copyright (C) 2024-2026	ITized <https://github.com/ITized>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       htdocs/constructioncosts/test/phpunit/WorkTemplateTest.php
 * \ingroup    constructioncosts
 * \brief      PHPUnit tests for WorkTemplate and WorkTemplateStep classes
 */

/**
 * Class WorkTemplateTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class WorkTemplateTest extends PHPUnit\Framework\TestCase
{
	/** @var WorkTemplate */
	private $template;

	protected function setUp(): void
	{
		$this->template = new WorkTemplate();
	}

	// ------------------------------------------------------------------
	// WorkTemplateStep quantity formula tests
	// ------------------------------------------------------------------

	public function testSimpleNumericFormula()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '5';
		$this->assertEquals(5.0, $step->computeQuantity());
	}

	public function testAreaFormula()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '{area}';
		$this->assertEquals(35.0, $step->computeQuantity(array('area' => 35)));
	}

	public function testAreaWithMultiplierFormula()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '{area} * 1.1';
		$this->assertEqualsWithDelta(38.5, $step->computeQuantity(array('area' => 35)), 0.01);
	}

	public function testAreaDivisionFormula()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '{area} / 10';
		$this->assertEquals(3.5, $step->computeQuantity(array('area' => 35)));
	}

	public function testMultiVariableFormula()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '{area} * {layers} / 10';
		$this->assertEquals(7.0, $step->computeQuantity(array('area' => 35, 'layers' => 2)));
	}

	public function testFormulaWithParentheses()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '({area} + {perimeter}) * 0.1';
		$qty = $step->computeQuantity(array('area' => 35, 'perimeter' => 25));
		$this->assertEqualsWithDelta(6.0, $qty, 0.01);
	}

	public function testEmptyFormulaReturnsZero()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '';
		$this->assertEquals(0.0, $step->computeQuantity(array('area' => 35)));
	}

	public function testZeroFormulaReturnsZero()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '0';
		$this->assertEquals(0.0, $step->computeQuantity());
	}

	public function testInvalidFormulaReturnsZero()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = 'DROP TABLE;';
		$this->assertEquals(0.0, $step->computeQuantity());
	}

	public function testMissingParamDefaultsToZero()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '{area} * 2';
		$this->assertEquals(0.0, $step->computeQuantity(array())); // no area param
	}

	public function testNegativeResultClampedToZero()
	{
		$step = new WorkTemplateStep();
		$step->qty_formula = '{area} - 100';
		$this->assertEquals(0.0, $step->computeQuantity(array('area' => 35)));
	}

	// ------------------------------------------------------------------
	// Template step management
	// ------------------------------------------------------------------

	public function testAddStep()
	{
		$step = new WorkTemplateStep();
		$step->label = 'Test step';
		$this->template->addStep($step);

		$this->assertCount(1, $this->template->steps);
		$this->assertEquals(1, $this->template->steps[0]->position);
	}

	public function testAddMultipleSteps()
	{
		$s1 = new WorkTemplateStep();
		$s1->label = 'Step 1';
		$s2 = new WorkTemplateStep();
		$s2->label = 'Step 2';

		$this->template->addStep($s1);
		$this->template->addStep($s2);

		$this->assertCount(2, $this->template->steps);
		$this->assertEquals(1, $this->template->steps[0]->position);
		$this->assertEquals(2, $this->template->steps[1]->position);
	}

	// ------------------------------------------------------------------
	// Line generation tests
	// ------------------------------------------------------------------

	public function testGenerateLinesBasic()
	{
		$step = new WorkTemplateStep();
		$step->ref = 'PAINT';
		$step->label = 'Paint';
		$step->step_type = 'product';
		$step->qty_formula = '{area} / 10';
		$step->unit_code = 'U';
		$step->unit_price_ht = 35.00;
		$step->vat_rate = 10.0;
		$this->template->addStep($step);

		$lines = $this->template->generateLines(array('area' => 35));

		$this->assertCount(1, $lines);
		$this->assertEquals('PAINT', $lines[0]['ref']);
		$this->assertEquals(3.5, $lines[0]['qty']);
		$this->assertEquals(35.00, $lines[0]['unit_price_ht']);
		$this->assertEqualsWithDelta(122.50, $lines[0]['total_ht'], 0.01);
	}

	public function testGenerateLinesServiceWithMO()
	{
		$step = new WorkTemplateStep();
		$step->ref = 'PAINT-MO';
		$step->label = 'Paint labor';
		$step->step_type = 'service';
		$step->qty_formula = '{area}';
		$step->unit_code = 'M2';
		$step->mo_hours_per_unit = 0.15;
		$step->mo_hourly_rate = 42.00;
		$step->vat_rate = 10.0;
		$this->template->addStep($step);

		$lines = $this->template->generateLines(array('area' => 35));

		$this->assertCount(1, $lines);
		$this->assertEquals(35.0, $lines[0]['qty']);
		$this->assertEqualsWithDelta(6.30, $lines[0]['unit_price_ht'], 0.01); // 42 * 0.15
	}

	public function testGenerateLinesWithMargin()
	{
		$this->template->margin_percent = 20.0;

		$step = new WorkTemplateStep();
		$step->ref = 'PRODUCT';
		$step->label = 'Product';
		$step->step_type = 'product';
		$step->qty_formula = '1';
		$step->unit_price_ht = 100.00;
		$this->template->addStep($step);

		$lines = $this->template->generateLines(array());

		$this->assertCount(1, $lines);
		$this->assertEqualsWithDelta(120.00, $lines[0]['unit_price_ht'], 0.01); // 100 * 1.2
	}

	public function testOptionalStepSkippedWhenDeclined()
	{
		$step = new WorkTemplateStep();
		$step->ref = 'OPT-STEP';
		$step->label = 'Optional step';
		$step->step_type = 'product';
		$step->qty_formula = '1';
		$step->unit_price_ht = 50.00;
		$step->is_optional = true;
		$step->user_prompt = 'Do you need this?';
		$step->default_answer = '';
		$this->template->addStep($step);

		// Decline via answer
		$lines = $this->template->generateLines(array(), array('OPT-STEP' => 'no'));
		$this->assertCount(0, $lines);
	}

	public function testOptionalStepIncludedWhenAccepted()
	{
		$step = new WorkTemplateStep();
		$step->ref = 'OPT-STEP';
		$step->label = 'Optional step';
		$step->step_type = 'product';
		$step->qty_formula = '1';
		$step->unit_price_ht = 50.00;
		$step->is_optional = true;
		$step->user_prompt = 'Do you need this?';
		$step->default_answer = 'yes';
		$this->template->addStep($step);

		$lines = $this->template->generateLines(array(), array('OPT-STEP' => 'yes'));
		$this->assertCount(1, $lines);
		$this->assertEquals(50.00, $lines[0]['unit_price_ht']);
	}

	public function testZeroQuantityStepSkipped()
	{
		$step = new WorkTemplateStep();
		$step->ref = 'ZERO';
		$step->label = 'Zero qty';
		$step->step_type = 'product';
		$step->qty_formula = '{area} * 0';
		$step->unit_price_ht = 100.00;
		$this->template->addStep($step);

		$lines = $this->template->generateLines(array('area' => 35));
		$this->assertCount(0, $lines);
	}

	// ------------------------------------------------------------------
	// Forfait (lump sum) tests
	// ------------------------------------------------------------------

	public function testGenerateForfait()
	{
		$this->template->ref = 'WT-TEST';
		$this->template->label = 'Test Template';
		$this->template->description = 'Test';

		$s1 = new WorkTemplateStep();
		$s1->ref = 'PROD';
		$s1->label = 'Product';
		$s1->step_type = 'product';
		$s1->qty_formula = '2';
		$s1->unit_price_ht = 100.00;
		$this->template->addStep($s1);

		$s2 = new WorkTemplateStep();
		$s2->ref = 'SERV';
		$s2->label = 'Service';
		$s2->step_type = 'service';
		$s2->qty_formula = '3';
		$s2->unit_price_ht = 50.00;
		$this->template->addStep($s2);

		$forfait = $this->template->generateForfait(array());

		$this->assertCount(1, $forfait);
		$this->assertStringContainsString('Forfait', $forfait[0]['label']);
		$this->assertEqualsWithDelta(350.00, $forfait[0]['total_ht'], 0.01); // 2*100 + 3*50
		$this->assertArrayHasKey('detail_lines', $forfait[0]);
	}

	// ------------------------------------------------------------------
	// Prompts
	// ------------------------------------------------------------------

	public function testGetRequiredPrompts()
	{
		$s1 = new WorkTemplateStep();
		$s1->ref = 'STEP1';
		$s1->label = 'Required step';
		$this->template->addStep($s1);

		$s2 = new WorkTemplateStep();
		$s2->ref = 'STEP2';
		$s2->label = 'Optional step';
		$s2->is_optional = true;
		$s2->user_prompt = 'Do you need filling?';
		$s2->default_answer = 'yes';
		$this->template->addStep($s2);

		$prompts = $this->template->getRequiredPrompts();
		$this->assertCount(1, $prompts);
		$this->assertEquals('STEP2', $prompts[0]['ref']);
		$this->assertEquals('Do you need filling?', $prompts[0]['prompt']);
	}

	// ------------------------------------------------------------------
	// Estimate total
	// ------------------------------------------------------------------

	public function testEstimateTotalHT()
	{
		$s1 = new WorkTemplateStep();
		$s1->ref = 'P';
		$s1->label = 'Product';
		$s1->step_type = 'product';
		$s1->qty_formula = '{area} / 10';
		$s1->unit_price_ht = 30.00;
		$this->template->addStep($s1);

		$s2 = new WorkTemplateStep();
		$s2->ref = 'S';
		$s2->label = 'Service';
		$s2->step_type = 'service';
		$s2->qty_formula = '{area}';
		$s2->mo_hours_per_unit = 0.1;
		$s2->mo_hourly_rate = 40.00;
		$this->template->addStep($s2);

		// Product: 35/10 * 30 = 105.00
		// Service: 35 * (40*0.1) = 35 * 4 = 140.00
		// Total: 245.00
		$total = $this->template->estimateTotalHT(array('area' => 35));
		$this->assertEqualsWithDelta(245.00, $total, 0.01);
	}

	// ------------------------------------------------------------------
	// Validation tests
	// ------------------------------------------------------------------

	public function testValidateValidTemplate()
	{
		$this->template->ref = 'WT-001';
		$this->template->label = 'Test';

		$step = new WorkTemplateStep();
		$step->label = 'Step 1';
		$this->template->addStep($step);

		$errors = $this->template->validate();
		$this->assertEmpty($errors);
	}

	public function testValidateMissingRef()
	{
		$this->template->ref = '';
		$this->template->label = 'Test';

		$step = new WorkTemplateStep();
		$step->label = 'Step';
		$this->template->addStep($step);

		$errors = $this->template->validate();
		$this->assertContains('Reference is required', $errors);
	}

	public function testValidateMissingLabel()
	{
		$this->template->ref = 'WT-001';
		$this->template->label = '';

		$step = new WorkTemplateStep();
		$step->label = 'Step';
		$this->template->addStep($step);

		$errors = $this->template->validate();
		$this->assertContains('Label is required', $errors);
	}

	public function testValidateNoSteps()
	{
		$this->template->ref = 'WT-001';
		$this->template->label = 'Test';

		$errors = $this->template->validate();
		$this->assertContains('Template must have at least one step', $errors);
	}

	public function testValidateInvalidPricingMode()
	{
		$this->template->ref = 'WT-001';
		$this->template->label = 'Test';
		$this->template->pricing_mode = 'invalid';

		$step = new WorkTemplateStep();
		$step->label = 'Step';
		$this->template->addStep($step);

		$errors = $this->template->validate();
		$this->assertNotEmpty($errors);
	}

	// ------------------------------------------------------------------
	// Specimen / integration test
	// ------------------------------------------------------------------

	public function testRepaintPaperedWallSpecimen()
	{
		$this->template->initAsSpecimenRepaintPaperedWall();

		// Validate
		$errors = $this->template->validate();
		$this->assertEmpty($errors, 'Specimen should be valid: ' . implode(', ', $errors));

		$this->assertEquals('WT-REPAINT-PAPER', $this->template->ref);
		$this->assertCount(12, $this->template->steps);

		// Generate lines for 35m², 2 layers, all optional steps accepted
		$params = array('area' => 35, 'layers' => 2);
		$userAnswers = array(
			'PREP-ENDUIT' => 'yes',
			'PREP-MO' => 'yes',
		);
		$lines = $this->template->generateLines($params, $userAnswers);

		// Should have all 12 steps
		$this->assertCount(12, $lines);

		// Verify categories are represented
		$categories = array_unique(array_column($lines, 'step_category'));
		$this->assertContains('protection', $categories);
		$this->assertContains('removal', $categories);
		$this->assertContains('preparation', $categories);
		$this->assertContains('application', $categories);
		$this->assertContains('waste', $categories);
		$this->assertContains('finishing', $categories);

		// Verify total is reasonable
		$total = $this->template->estimateTotalHT($params, $userAnswers);
		$this->assertGreaterThan(500, $total, 'Total for 35m² repaint should be > €500');
		$this->assertLessThan(5000, $total, 'Total for 35m² repaint should be < €5000');
	}

	public function testRepaintPaperedWallNoPreparation()
	{
		$this->template->initAsSpecimenRepaintPaperedWall();

		$params = array('area' => 35, 'layers' => 2);
		$userAnswers = array(
			'PREP-ENDUIT' => 'no',
			'PREP-MO' => 'no',
		);

		$lines = $this->template->generateLines($params, $userAnswers);

		// Should have 10 steps (12 - 2 optional ones)
		$this->assertCount(10, $lines);

		// No preparation lines
		$prepLines = array_filter($lines, function ($l) {
			return $l['step_category'] === 'preparation';
		});
		$this->assertCount(0, $prepLines);
	}

	public function testRepaintPaperedWallForfait()
	{
		$this->template->initAsSpecimenRepaintPaperedWall();

		$params = array('area' => 35, 'layers' => 2);
		$userAnswers = array(
			'PREP-ENDUIT' => 'yes',
			'PREP-MO' => 'yes',
		);

		$forfait = $this->template->generateForfait($params, $userAnswers);
		$this->assertCount(1, $forfait);
		$this->assertStringContainsString('Forfait', $forfait[0]['label']);
		$this->assertGreaterThan(0, $forfait[0]['total_ht']);
		$this->assertArrayHasKey('detail_lines', $forfait[0]);
		$this->assertCount(12, $forfait[0]['detail_lines']);
	}
}
