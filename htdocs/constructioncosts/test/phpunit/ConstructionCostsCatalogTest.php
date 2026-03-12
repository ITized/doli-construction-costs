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
 * \file       htdocs/constructioncosts/test/phpunit/ConstructionCostsCatalogTest.php
 * \ingroup    constructioncosts
 * \brief      PHPUnit tests for ConstructionCostsCatalog class
 */

/**
 * Class ConstructionCostsCatalogTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ConstructionCostsCatalogTest extends PHPUnit\Framework\TestCase
{
	/**
	 * @var ConstructionCostsCatalog
	 */
	private $catalog;

	/**
	 * @var string Path to test data directory
	 */
	private $testDataDir;

	/**
	 * Set up test fixture
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->catalog = new ConstructionCostsCatalog(null);
		$this->testDataDir = dirname(__FILE__) . '/../../data/';
	}

	// ------------------------------------------------------------------
	// CSV parsing tests
	// ------------------------------------------------------------------

	/**
	 * Test parsing sample products CSV
	 *
	 * @return void
	 */
	public function testParseProductsCSV()
	{
		$filepath = $this->testDataDir . 'sample_products.csv';
		if (!file_exists($filepath)) {
			$this->markTestSkipped('Sample products CSV not found at: ' . $filepath);
		}

		$rows = $this->catalog->parseCSV($filepath, ';', '"', true);

		$this->assertIsArray($rows);
		$this->assertGreaterThan(0, count($rows), 'Should parse at least one row');

		// Check first row has expected keys
		$firstRow = $rows[0];
		$this->assertArrayHasKey('ref', $firstRow);
		$this->assertArrayHasKey('label', $firstRow);
		$this->assertArrayHasKey('price_ht', $firstRow);
		$this->assertArrayHasKey('category', $firstRow);
		$this->assertArrayHasKey('type', $firstRow);
	}

	/**
	 * Test parsing sample services CSV
	 *
	 * @return void
	 */
	public function testParseServicesCSV()
	{
		$filepath = $this->testDataDir . 'sample_services.csv';
		if (!file_exists($filepath)) {
			$this->markTestSkipped('Sample services CSV not found at: ' . $filepath);
		}

		$rows = $this->catalog->parseCSV($filepath, ';', '"', true);

		$this->assertIsArray($rows);
		$this->assertGreaterThan(0, count($rows));

		$firstRow = $rows[0];
		$this->assertArrayHasKey('ref', $firstRow);
		$this->assertArrayHasKey('mo_hours', $firstRow);
		$this->assertArrayHasKey('mo_hourly_rate', $firstRow);
	}

	/**
	 * Test parsing non-existent file returns false
	 *
	 * @return void
	 */
	public function testParseCSVFileNotFound()
	{
		$result = $this->catalog->parseCSV('/nonexistent/path/file.csv');

		$this->assertFalse($result);
		$this->assertNotEmpty($this->catalog->error);
		$this->assertStringContainsString('not found', $this->catalog->error);
	}

	/**
	 * Test CSV template generation
	 *
	 * @return void
	 */
	public function testGenerateCSVTemplate()
	{
		$template = $this->catalog->generateCSVTemplate();

		$this->assertIsString($template);
		$this->assertStringContainsString('ref', $template);
		$this->assertStringContainsString('label', $template);
		$this->assertStringContainsString('price_ht', $template);
		$this->assertStringContainsString('ean', $template);
		$this->assertStringContainsString('supplier', $template);
	}

	// ------------------------------------------------------------------
	// Row validation tests
	// ------------------------------------------------------------------

	/**
	 * Test valid row passes validation
	 *
	 * @return void
	 */
	public function testValidateValidRow()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => 'Test Product',
			'type' => 'product',
			'price_ht' => '25.50',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertEmpty($errors, 'Valid row should have no errors');
	}

	/**
	 * Test missing ref fails validation
	 *
	 * @return void
	 */
	public function testValidateRowMissingRef()
	{
		$row = array(
			'ref' => '',
			'label' => 'Test Product',
			'type' => 'product',
			'price_ht' => '25.50',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertNotEmpty($errors);
		$this->assertContains('Reference is required', $errors);
	}

	/**
	 * Test missing label fails validation
	 *
	 * @return void
	 */
	public function testValidateRowMissingLabel()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => '',
			'type' => 'product',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertNotEmpty($errors);
		$this->assertContains('Label is required', $errors);
	}

	/**
	 * Test invalid type fails validation
	 *
	 * @return void
	 */
	public function testValidateRowInvalidType()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => 'Test Product',
			'type' => 'invalid_type',
			'price_ht' => '25.50',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertNotEmpty($errors);
		$this->assertContains('Type must be product, service or mixed', $errors);
	}

	/**
	 * Test non-numeric price fails validation
	 *
	 * @return void
	 */
	public function testValidateRowNonNumericPrice()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => 'Test Product',
			'type' => 'product',
			'price_ht' => 'not_a_number',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertNotEmpty($errors);
		$this->assertContains('Price HT must be numeric', $errors);
	}

	/**
	 * Test negative price fails validation
	 *
	 * @return void
	 */
	public function testValidateRowNegativePrice()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => 'Test Product',
			'type' => 'product',
			'price_ht' => '-10.00',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertNotEmpty($errors);
		$this->assertContains('Price HT cannot be negative', $errors);
	}

	/**
	 * Test non-numeric VAT rate fails validation
	 *
	 * @return void
	 */
	public function testValidateRowNonNumericVAT()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => 'Test Product',
			'type' => 'product',
			'price_ht' => '25.50',
			'vat_rate' => 'abc',
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertNotEmpty($errors);
		$this->assertContains('VAT rate must be numeric', $errors);
	}

	/**
	 * Test empty optional fields pass validation
	 *
	 * @return void
	 */
	public function testValidateRowEmptyOptionalFields()
	{
		$row = array(
			'ref' => 'TEST-001',
			'label' => 'Test Product',
			'type' => '',       // empty type is OK
			'price_ht' => '',   // empty price is OK
			'vat_rate' => '',   // empty VAT is OK
		);

		$errors = $this->catalog->validateImportRow($row);
		$this->assertEmpty($errors, 'Empty optional fields should pass');
	}

	// ------------------------------------------------------------------
	// Row value helper tests
	// ------------------------------------------------------------------

	/**
	 * Test getRowValue with associative key
	 *
	 * @return void
	 */
	public function testGetRowValueAssociativeKey()
	{
		$row = array('ref' => 'ABC-123', 'label' => 'Test');

		$value = $this->catalog->getRowValue($row, 'ref', 0);
		$this->assertEquals('ABC-123', $value);
	}

	/**
	 * Test getRowValue with numeric index fallback
	 *
	 * @return void
	 */
	public function testGetRowValueNumericIndex()
	{
		$row = array(0 => 'ABC-123', 1 => 'Test');

		$value = $this->catalog->getRowValue($row, 'ref', 0);
		$this->assertEquals('ABC-123', $value);
	}

	/**
	 * Test getRowValue returns empty for missing key
	 *
	 * @return void
	 */
	public function testGetRowValueMissingKey()
	{
		$row = array('label' => 'Test');

		$value = $this->catalog->getRowValue($row, 'ref', 99);
		$this->assertEquals('', $value);
	}

	// ------------------------------------------------------------------
	// Integration-style tests with sample data
	// ------------------------------------------------------------------

	/**
	 * Test all sample product rows validate
	 *
	 * @return void
	 */
	public function testAllSampleProductsValidate()
	{
		$filepath = $this->testDataDir . 'sample_products.csv';
		if (!file_exists($filepath)) {
			$this->markTestSkipped('Sample products CSV not found');
		}

		$rows = $this->catalog->parseCSV($filepath, ';', '"', true);
		$this->assertIsArray($rows);

		foreach ($rows as $idx => $row) {
			$errors = $this->catalog->validateImportRow($row);
			$this->assertEmpty(
				$errors,
				'Row ' . ($idx + 2) . ' (ref: ' . ($row['ref'] ?? 'N/A') . ') has validation errors: ' . implode(', ', $errors)
			);
		}
	}

	/**
	 * Test all sample service rows validate
	 *
	 * @return void
	 */
	public function testAllSampleServicesValidate()
	{
		$filepath = $this->testDataDir . 'sample_services.csv';
		if (!file_exists($filepath)) {
			$this->markTestSkipped('Sample services CSV not found');
		}

		$rows = $this->catalog->parseCSV($filepath, ';', '"', true);
		$this->assertIsArray($rows);

		foreach ($rows as $idx => $row) {
			$errors = $this->catalog->validateImportRow($row);
			$this->assertEmpty(
				$errors,
				'Row ' . ($idx + 2) . ' (ref: ' . ($row['ref'] ?? 'N/A') . ') has validation errors: ' . implode(', ', $errors)
			);
		}
	}

	/**
	 * Test sample products have expected categories
	 *
	 * @return void
	 */
	public function testSampleProductsHaveCategories()
	{
		$filepath = $this->testDataDir . 'sample_products.csv';
		if (!file_exists($filepath)) {
			$this->markTestSkipped('Sample products CSV not found');
		}

		$rows = $this->catalog->parseCSV($filepath, ';', '"', true);
		$this->assertIsArray($rows);

		$categories = array();
		foreach ($rows as $row) {
			if (!empty($row['category'])) {
				$categories[$row['category']] = true;
			}
		}

		// Should have diverse construction categories
		$this->assertArrayHasKey('GROS_OEUVRE', $categories);
		$this->assertArrayHasKey('MACONNERIE', $categories);
		$this->assertArrayHasKey('ELECTRICITE', $categories);
	}
}
