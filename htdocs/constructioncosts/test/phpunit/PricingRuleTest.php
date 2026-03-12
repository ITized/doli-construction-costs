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
 * \file       htdocs/constructioncosts/test/phpunit/PricingRuleTest.php
 * \ingroup    constructioncosts
 * \brief      PHPUnit tests for PricingRule class
 */

/**
 * Class PricingRuleTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class PricingRuleTest extends PHPUnit\Framework\TestCase
{
	/**
	 * @var PricingRule
	 */
	private $rule;

	/**
	 * Set up test fixture
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->rule = new PricingRule(null);
	}

	// ------------------------------------------------------------------
	// Product pricing tests
	// ------------------------------------------------------------------

	/**
	 * Test basic product price calculation
	 *
	 * @return void
	 */
	public function testProductPriceCalculation()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		$price = $this->rule->calculatePrice();
		$this->assertEquals(100.00, $price, 'Product price with no margin should equal base price');
	}

	/**
	 * Test product price with margin
	 *
	 * @return void
	 */
	public function testProductPriceWithMargin()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 20.0;

		$price = $this->rule->calculatePrice();
		$this->assertEquals(120.00, $price, 'Product price with 20% margin should be 120.00');
	}

	/**
	 * Test product price with multiplier
	 *
	 * @return void
	 */
	public function testProductPriceWithMultiplier()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 50.00;
		$this->rule->base_multiplier = 2.0;
		$this->rule->margin_percent = 0;

		$price = $this->rule->calculatePrice();
		$this->assertEquals(100.00, $price, 'Product price with 2x multiplier should double');
	}

	/**
	 * Test product price with margin and multiplier
	 *
	 * @return void
	 */
	public function testProductPriceWithMarginAndMultiplier()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 50.00;
		$this->rule->base_multiplier = 2.0;
		$this->rule->margin_percent = 10.0;

		// (50 * 2.0) * (1 + 10/100) = 100 * 1.1 = 110
		$price = $this->rule->calculatePrice();
		$this->assertEqualsWithDelta(110.00, $price, 0.01);
	}

	// ------------------------------------------------------------------
	// Service pricing tests
	// ------------------------------------------------------------------

	/**
	 * Test basic service price calculation (MO rate * hours)
	 *
	 * @return void
	 */
	public function testServicePriceCalculation()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_SERVICE;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		// 45 * 2 * 1.0 * (1 + 0) = 90
		$price = $this->rule->calculatePrice();
		$this->assertEquals(90.00, $price, 'Service price should be MO rate * hours');
	}

	/**
	 * Test service price with margin
	 *
	 * @return void
	 */
	public function testServicePriceWithMargin()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_SERVICE;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 20.0;

		// (45 * 2) * 1.0 * (1 + 20/100) = 90 * 1.2 = 108
		$price = $this->rule->calculatePrice();
		$this->assertEquals(108.00, $price);
	}

	/**
	 * Test service price with override MO rate
	 *
	 * @return void
	 */
	public function testServicePriceWithOverrideMoRate()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_SERVICE;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		// Override MO rate to 60: 60 * 2 = 120
		$price = $this->rule->calculatePrice(60.00);
		$this->assertEquals(120.00, $price, 'Service price should use overridden MO rate');
	}

	/**
	 * Test service price with base multiplier
	 *
	 * @return void
	 */
	public function testServicePriceWithMultiplier()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_SERVICE;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.5;
		$this->rule->margin_percent = 0;

		// (45 * 2) * 1.5 = 135
		$price = $this->rule->calculatePrice();
		$this->assertEquals(135.00, $price);
	}

	// ------------------------------------------------------------------
	// Mixed pricing tests
	// ------------------------------------------------------------------

	/**
	 * Test mixed pricing (product + service)
	 *
	 * @return void
	 */
	public function testMixedPriceCalculation()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_MIXED;
		$this->rule->base_price = 100.00;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		// (100 + 45*2) * 1.0 * (1 + 0) = 190
		$price = $this->rule->calculatePrice();
		$this->assertEquals(190.00, $price, 'Mixed price should be base_price + labor');
	}

	/**
	 * Test mixed pricing with margin
	 *
	 * @return void
	 */
	public function testMixedPriceWithMargin()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_MIXED;
		$this->rule->base_price = 100.00;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 20.0;

		// (100 + 90) * 1.0 * (1 + 0.2) = 190 * 1.2 = 228
		$price = $this->rule->calculatePrice();
		$this->assertEquals(228.00, $price);
	}

	// ------------------------------------------------------------------
	// TTC pricing tests
	// ------------------------------------------------------------------

	/**
	 * Test TTC price calculation
	 *
	 * @return void
	 */
	public function testCalculatePriceTTC()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;
		$this->rule->vat_rate = 20.0;

		$priceTTC = $this->rule->calculatePriceTTC();
		$this->assertEquals(120.00, $priceTTC, 'TTC should include 20% VAT');
	}

	/**
	 * Test TTC with reduced VAT rate (renovation)
	 *
	 * @return void
	 */
	public function testCalculatePriceTTCReducedVAT()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;
		$this->rule->vat_rate = 10.0;

		$priceTTC = $this->rule->calculatePriceTTC();
		$this->assertEqualsWithDelta(110.00, $priceTTC, 0.01, 'TTC should include 10% reduced VAT');
	}

	/**
	 * Test TTC with super-reduced VAT (energy improvements)
	 *
	 * @return void
	 */
	public function testCalculatePriceTTCSuperReducedVAT()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;
		$this->rule->vat_rate = 5.5;

		$priceTTC = $this->rule->calculatePriceTTC();
		$this->assertEquals(105.50, $priceTTC);
	}

	// ------------------------------------------------------------------
	// Cost breakdown tests
	// ------------------------------------------------------------------

	/**
	 * Test labor cost calculation
	 *
	 * @return void
	 */
	public function testGetLaborCost()
	{
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 3.0;

		$laborCost = $this->rule->getLaborCost();
		$this->assertEquals(135.00, $laborCost);
	}

	/**
	 * Test labor cost with override rate
	 *
	 * @return void
	 */
	public function testGetLaborCostWithOverride()
	{
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 3.0;

		$laborCost = $this->rule->getLaborCost(50.00);
		$this->assertEquals(150.00, $laborCost);
	}

	/**
	 * Test material cost calculation
	 *
	 * @return void
	 */
	public function testGetMaterialCost()
	{
		$this->rule->base_price = 80.00;
		$this->rule->base_multiplier = 1.5;

		$materialCost = $this->rule->getMaterialCost();
		$this->assertEquals(120.00, $materialCost);
	}

	/**
	 * Test margin amount
	 *
	 * @return void
	 */
	public function testGetMarginAmount()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->mo_hourly_rate = 0;
		$this->rule->mo_hours = 0;
		$this->rule->margin_percent = 25.0;

		$margin = $this->rule->getMarginAmount();
		$this->assertEquals(25.00, $margin);
	}

	/**
	 * Test full price breakdown
	 *
	 * @return void
	 */
	public function testGetPriceBreakdown()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_MIXED;
		$this->rule->base_price = 100.00;
		$this->rule->mo_hourly_rate = 50.00;
		$this->rule->mo_hours = 2.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 20.0;
		$this->rule->vat_rate = 20.0;

		$breakdown = $this->rule->getPriceBreakdown();

		$this->assertEquals(100.00, $breakdown['material_cost']);
		$this->assertEquals(100.00, $breakdown['labor_cost']);      // 50 * 2
		$this->assertEquals(200.00, $breakdown['subtotal']);         // (100 + 100) * 1.0
		$this->assertEquals(20.0, $breakdown['margin_percent']);
		$this->assertEquals(40.00, $breakdown['margin_amount']);     // 200 * 20%
		$this->assertEquals(240.00, $breakdown['price_ht']);         // 200 + 40
		$this->assertEquals(20.0, $breakdown['vat_rate']);
		$this->assertEquals(48.00, $breakdown['vat_amount']);        // 240 * 20%
		$this->assertEquals(288.00, $breakdown['price_ttc']);        // 240 + 48
	}

	// ------------------------------------------------------------------
	// Validation tests
	// ------------------------------------------------------------------

	/**
	 * Test valid rule passes validation
	 *
	 * @return void
	 */
	public function testValidRulePassesValidation()
	{
		$this->rule->ref = 'TEST-001';
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;

		$errors = $this->rule->validate();
		$this->assertEmpty($errors, 'Valid rule should have no errors');
	}

	/**
	 * Test missing ref fails validation
	 *
	 * @return void
	 */
	public function testMissingRefFailsValidation()
	{
		$this->rule->ref = '';
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;

		$errors = $this->rule->validate();
		$this->assertNotEmpty($errors);
		$this->assertContains('Reference is required', $errors);
	}

	/**
	 * Test invalid rule type fails validation
	 *
	 * @return void
	 */
	public function testInvalidRuleTypeFailsValidation()
	{
		$this->rule->ref = 'TEST-001';
		$this->rule->rule_type = 'invalid';

		$errors = $this->rule->validate();
		$this->assertNotEmpty($errors);
	}

	/**
	 * Test negative price fails validation
	 *
	 * @return void
	 */
	public function testNegativePriceFailsValidation()
	{
		$this->rule->ref = 'TEST-001';
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = -10.00;

		$errors = $this->rule->validate();
		$this->assertNotEmpty($errors);
		$this->assertContains('Base price cannot be negative', $errors);
	}

	/**
	 * Test zero multiplier fails validation
	 *
	 * @return void
	 */
	public function testZeroMultiplierFailsValidation()
	{
		$this->rule->ref = 'TEST-001';
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_multiplier = 0;

		$errors = $this->rule->validate();
		$this->assertNotEmpty($errors);
		$this->assertContains('Base multiplier must be positive', $errors);
	}

	/**
	 * Test date range validation
	 *
	 * @return void
	 */
	public function testDateRangeValidation()
	{
		$this->rule->ref = 'TEST-001';
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_multiplier = 1.0;
		$this->rule->date_start = '2026-12-31';
		$this->rule->date_end = '2026-01-01';

		$errors = $this->rule->validate();
		$this->assertNotEmpty($errors);
		$this->assertContains('Start date cannot be after end date', $errors);
	}

	// ------------------------------------------------------------------
	// Date validity tests
	// ------------------------------------------------------------------

	/**
	 * Test rule validity check with active status
	 *
	 * @return void
	 */
	public function testIsValidAtDateActive()
	{
		$this->rule->status = PricingRule::STATUS_ACTIVE;
		$this->rule->date_start = '2020-01-01';
		$this->rule->date_end = '2030-12-31';

		$this->assertTrue($this->rule->isValidAtDate('2025-06-15'));
	}

	/**
	 * Test rule validity check with draft status
	 *
	 * @return void
	 */
	public function testIsValidAtDateDraft()
	{
		$this->rule->status = PricingRule::STATUS_DRAFT;

		$this->assertFalse($this->rule->isValidAtDate());
	}

	/**
	 * Test rule validity before start date
	 *
	 * @return void
	 */
	public function testIsValidAtDateBeforeStart()
	{
		$this->rule->status = PricingRule::STATUS_ACTIVE;
		$this->rule->date_start = '2030-01-01';

		$this->assertFalse($this->rule->isValidAtDate('2025-06-15'));
	}

	/**
	 * Test rule validity after end date
	 *
	 * @return void
	 */
	public function testIsValidAtDateAfterEnd()
	{
		$this->rule->status = PricingRule::STATUS_ACTIVE;
		$this->rule->date_end = '2020-12-31';

		$this->assertFalse($this->rule->isValidAtDate('2025-06-15'));
	}

	// ------------------------------------------------------------------
	// Specimen tests
	// ------------------------------------------------------------------

	/**
	 * Test initAsSpecimen
	 *
	 * @return void
	 */
	public function testInitAsSpecimen()
	{
		$result = $this->rule->initAsSpecimen();
		$this->assertEquals(1, $result);
		$this->assertNotEmpty($this->rule->ref);
		$this->assertNotEmpty($this->rule->label);
		$this->assertEquals(PricingRule::RULE_TYPE_MIXED, $this->rule->rule_type);
		$this->assertGreaterThan(0, $this->rule->base_price);
		$this->assertGreaterThan(0, $this->rule->mo_hourly_rate);
		$this->assertGreaterThan(0, $this->rule->mo_hours);
	}

	// ------------------------------------------------------------------
	// Override margin tests
	// ------------------------------------------------------------------

	/**
	 * Test price calculation with overridden margin
	 *
	 * @return void
	 */
	public function testCalculatePriceWithOverrideMargin()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 10.0;

		// Override margin to 30%
		$price = $this->rule->calculatePrice(null, 30.0);
		$this->assertEquals(130.00, $price, 'Should use overridden margin of 30%');
	}

	/**
	 * Test zero margin results in cost price
	 *
	 * @return void
	 */
	public function testZeroMarginGivesCostPrice()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_MIXED;
		$this->rule->base_price = 80.00;
		$this->rule->mo_hourly_rate = 40.00;
		$this->rule->mo_hours = 1.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		$price = $this->rule->calculatePrice();
		$this->assertEquals(120.00, $price, 'Zero margin should give cost price (80 + 40)');
	}

	// ------------------------------------------------------------------
	// Edge case tests
	// ------------------------------------------------------------------

	/**
	 * Test service pricing ignores base_price
	 *
	 * @return void
	 */
	public function testServiceIgnoresBasePrice()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_SERVICE;
		$this->rule->base_price = 999.00; // Should be ignored
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 1.0;
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		$price = $this->rule->calculatePrice();
		$this->assertEquals(45.00, $price, 'Service type should ignore base_price');
	}

	/**
	 * Test product pricing ignores MO hours
	 *
	 * @return void
	 */
	public function testProductIgnoresMoHours()
	{
		$this->rule->rule_type = PricingRule::RULE_TYPE_PRODUCT;
		$this->rule->base_price = 100.00;
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 999.0; // Should be ignored
		$this->rule->base_multiplier = 1.0;
		$this->rule->margin_percent = 0;

		$price = $this->rule->calculatePrice();
		$this->assertEquals(100.00, $price, 'Product type should ignore MO hours');
	}

	/**
	 * Test zero hours gives zero labor cost
	 *
	 * @return void
	 */
	public function testZeroHoursGivesZeroLaborCost()
	{
		$this->rule->mo_hourly_rate = 45.00;
		$this->rule->mo_hours = 0;

		$laborCost = $this->rule->getLaborCost();
		$this->assertEquals(0.00, $laborCost);
	}
}
