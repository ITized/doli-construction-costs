<?php
/* Copyright (C) 2024-2026	ITized <https://github.com/ITized>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       htdocs/constructioncosts/test/phpunit/OfferConverterTest.php
 * \ingroup    constructioncosts
 * \brief      PHPUnit tests for OfferConverter class
 */

/**
 * Class OfferConverterTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class OfferConverterTest extends PHPUnit\Framework\TestCase
{
	/** @var OfferConverter */
	private $converter;

	protected function setUp(): void
	{
		$this->converter = new OfferConverter(null);
	}

	// ------------------------------------------------------------------
	// Line conversion tests
	// ------------------------------------------------------------------

	public function testConvertLinesToInvoiceBasic()
	{
		$propalLines = array(
			array(
				'ref' => 'PAINT',
				'label' => 'Finish paint',
				'description' => 'Acrylic paint',
				'step_type' => 'product',
				'product_ref' => 'PEINT-ACR-10',
				'qty' => 3.5,
				'unit_code' => 'U',
				'unit_price_ht' => 35.00,
				'vat_rate' => 10.0,
				'total_ht' => 122.50,
				'step_category' => 'application',
			),
		);

		$meta = array(
			'work_template_ref' => 'WT-REPAINT-PAPER',
			'propal_ref' => 'PR2026-001',
			'params' => array('area' => 35, 'layers' => 2),
			'generated_date' => '2026-03-12',
		);

		$invoiceLines = $this->converter->convertLinesToInvoice($propalLines, $meta);

		$this->assertCount(1, $invoiceLines);
		$line = $invoiceLines[0];

		$this->assertEquals(3.5, $line['qty']);
		$this->assertEquals(35.00, $line['unit_price_ht']);
		$this->assertEquals(10.0, $line['vat_rate']);
		$this->assertEquals(122.50, $line['total_ht']);
		$this->assertEqualsWithDelta(12.25, $line['total_vat'], 0.01);
		$this->assertEqualsWithDelta(134.75, $line['total_ttc'], 0.01);

		// Check metadata preserved
		$this->assertEquals('WT-REPAINT-PAPER', $line['cc_meta']['template_ref']);
		$this->assertEquals('PAINT', $line['cc_meta']['step_ref']);
		$this->assertEquals('product', $line['cc_meta']['step_type']);
		$this->assertEquals('PR2026-001', $line['cc_meta']['propal_ref']);
		$this->assertEquals(35, $line['cc_meta']['original_params']['area']);
	}

	public function testConvertMultipleLines()
	{
		$propalLines = array(
			array(
				'ref' => 'PRODUCT',
				'label' => 'Product',
				'description' => '',
				'step_type' => 'product',
				'product_ref' => 'P1',
				'qty' => 2,
				'unit_code' => 'U',
				'unit_price_ht' => 100.00,
				'vat_rate' => 20.0,
				'total_ht' => 200.00,
				'step_category' => 'application',
			),
			array(
				'ref' => 'SERVICE',
				'label' => 'Service',
				'description' => '',
				'step_type' => 'service',
				'product_ref' => '',
				'qty' => 10,
				'unit_code' => 'H',
				'unit_price_ht' => 45.00,
				'vat_rate' => 20.0,
				'total_ht' => 450.00,
				'step_category' => 'application',
			),
		);

		$invoiceLines = $this->converter->convertLinesToInvoice($propalLines);
		$this->assertCount(2, $invoiceLines);
	}

	// ------------------------------------------------------------------
	// Invoice description tests
	// ------------------------------------------------------------------

	public function testBuildInvoiceDescription()
	{
		$line = array(
			'label' => 'Finish paint',
			'description' => 'Acrylic paint for walls',
			'product_ref' => 'PEINT-ACR-10',
		);

		$desc = $this->converter->buildInvoiceDescription($line);

		$this->assertStringContainsString('Finish paint', $desc);
		$this->assertStringContainsString('Acrylic paint for walls', $desc);
		$this->assertStringContainsString('PEINT-ACR-10', $desc);
	}

	public function testBuildInvoiceDescriptionMinimal()
	{
		$line = array(
			'label' => 'Simple item',
			'description' => '',
			'product_ref' => '',
		);

		$desc = $this->converter->buildInvoiceDescription($line);
		$this->assertEquals('Simple item', $desc);
	}

	// ------------------------------------------------------------------
	// Cost summary tests
	// ------------------------------------------------------------------

	public function testGenerateCostSummary()
	{
		$invoiceLines = array(
			array(
				'total_ht' => 200.00,
				'total_vat' => 40.00,
				'total_ttc' => 240.00,
				'cc_meta' => array(
					'step_type' => 'product',
					'step_category' => 'application',
				),
			),
			array(
				'total_ht' => 450.00,
				'total_vat' => 90.00,
				'total_ttc' => 540.00,
				'cc_meta' => array(
					'step_type' => 'service',
					'step_category' => 'application',
				),
			),
			array(
				'total_ht' => 50.00,
				'total_vat' => 10.00,
				'total_ttc' => 60.00,
				'cc_meta' => array(
					'step_type' => 'product',
					'step_category' => 'protection',
				),
			),
		);

		$summary = $this->converter->generateCostSummary($invoiceLines);

		$this->assertEqualsWithDelta(250.00, $summary['total_materials_ht'], 0.01);
		$this->assertEqualsWithDelta(450.00, $summary['total_labor_ht'], 0.01);
		$this->assertEqualsWithDelta(700.00, $summary['total_ht'], 0.01);
		$this->assertEqualsWithDelta(140.00, $summary['total_vat'], 0.01);
		$this->assertEqualsWithDelta(840.00, $summary['total_ttc'], 0.01);

		// Percentages
		$this->assertEqualsWithDelta(35.7, $summary['material_percent'], 0.1);
		$this->assertEqualsWithDelta(64.3, $summary['labor_percent'], 0.1);

		// Categories
		$this->assertArrayHasKey('application', $summary['categories']);
		$this->assertArrayHasKey('protection', $summary['categories']);
		$this->assertEqualsWithDelta(650.00, $summary['categories']['application'], 0.01);
		$this->assertEqualsWithDelta(50.00, $summary['categories']['protection'], 0.01);
	}

	public function testGenerateCostSummaryEmpty()
	{
		$summary = $this->converter->generateCostSummary(array());

		$this->assertEquals(0, $summary['total_ht']);
		$this->assertEquals(0, $summary['material_percent']);
		$this->assertEquals(0, $summary['labor_percent']);
	}

	// ------------------------------------------------------------------
	// Validation tests
	// ------------------------------------------------------------------

	public function testValidateForConversionValid()
	{
		$lines = array(
			array('qty' => 5, 'unit_price_ht' => 10.00, 'vat_rate' => 20.0),
		);

		$errors = $this->converter->validateForConversion($lines);
		$this->assertEmpty($errors);
	}

	public function testValidateForConversionEmpty()
	{
		$errors = $this->converter->validateForConversion(array());
		$this->assertContains('No lines to convert', $errors);
	}

	public function testValidateForConversionZeroQty()
	{
		$lines = array(
			array('qty' => 0, 'unit_price_ht' => 10.00, 'vat_rate' => 20.0),
		);

		$errors = $this->converter->validateForConversion($lines);
		$this->assertNotEmpty($errors);
	}

	public function testValidateForConversionMissingPrice()
	{
		$lines = array(
			array('qty' => 5, 'vat_rate' => 20.0),
		);

		$errors = $this->converter->validateForConversion($lines);
		$this->assertNotEmpty($errors);
	}

	// ------------------------------------------------------------------
	// Invoice metadata tests
	// ------------------------------------------------------------------

	public function testPrepareInvoiceMeta()
	{
		$meta = $this->converter->prepareInvoiceMeta(
			'WT-REPAINT-PAPER',
			array('area' => 35, 'layers' => 2),
			'PR2026-001'
		);

		$this->assertEquals('WT-REPAINT-PAPER', $meta['work_template_ref']);
		$this->assertEquals('PR2026-001', $meta['propal_ref']);
		$this->assertEquals(35, $meta['params']['area']);
		$this->assertNotEmpty($meta['generated_date']);
		$this->assertEquals('constructioncosts', $meta['module']);
	}

	public function testGenerateInvoiceNote()
	{
		$meta = array(
			'work_template_ref' => 'WT-REPAINT-PAPER',
			'propal_ref' => 'PR2026-001',
			'params' => array('area' => 35, 'layers' => 2),
			'generated_date' => '2026-03-12 10:00:00',
		);

		$note = $this->converter->generateInvoiceNote($meta);

		$this->assertStringContainsString('WT-REPAINT-PAPER', $note);
		$this->assertStringContainsString('PR2026-001', $note);
		$this->assertStringContainsString('area=35', $note);
		$this->assertStringContainsString('layers=2', $note);
		$this->assertStringContainsString('2026-03-12', $note);
	}

	// ------------------------------------------------------------------
	// Integration test: full workflow
	// ------------------------------------------------------------------

	public function testFullWorkflowTemplateToInvoice()
	{
		// 1. Create template and generate lines
		$template = new WorkTemplate();
		$template->initAsSpecimenRepaintPaperedWall();

		$params = array('area' => 35, 'layers' => 2);
		$userAnswers = array('PREP-ENDUIT' => 'yes', 'PREP-MO' => 'yes');
		$propalLines = $template->generateLines($params, $userAnswers);

		$this->assertNotEmpty($propalLines);

		// 2. Validate for conversion
		$errors = $this->converter->validateForConversion($propalLines);
		$this->assertEmpty($errors, 'Lines should be valid: ' . implode(', ', $errors));

		// 3. Convert to invoice
		$meta = $this->converter->prepareInvoiceMeta($template->ref, $params, 'PR2026-TEST');
		$invoiceLines = $this->converter->convertLinesToInvoice($propalLines, $meta);

		$this->assertCount(count($propalLines), $invoiceLines);

		// 4. Generate cost summary
		$summary = $this->converter->generateCostSummary($invoiceLines);

		$this->assertGreaterThan(0, $summary['total_materials_ht']);
		$this->assertGreaterThan(0, $summary['total_labor_ht']);
		$this->assertGreaterThan(0, $summary['total_ht']);

		// Materials + Labor should equal total
		$this->assertEqualsWithDelta(
			$summary['total_ht'],
			$summary['total_materials_ht'] + $summary['total_labor_ht'],
			0.01
		);

		// 5. Generate note
		$note = $this->converter->generateInvoiceNote($meta);
		$this->assertStringContainsString('WT-REPAINT-PAPER', $note);

		// 6. All metadata preserved
		foreach ($invoiceLines as $line) {
			$this->assertEquals('WT-REPAINT-PAPER', $line['cc_meta']['template_ref']);
			$this->assertNotEmpty($line['cc_meta']['step_ref']);
		}
	}
}
