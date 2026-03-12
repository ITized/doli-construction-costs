<?php
/* Copyright (C) 2024-2026	ITized <https://github.com/ITized>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       htdocs/constructioncosts/test/phpunit/SupplierPriceImporterTest.php
 * \ingroup    constructioncosts
 * \brief      PHPUnit tests for SupplierPriceImporter class
 */

/**
 * Class SupplierPriceImporterTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class SupplierPriceImporterTest extends PHPUnit\Framework\TestCase
{
	/** @var SupplierPriceImporter */
	private $importer;

	/** @var string Temp directory for test files */
	private $tmpDir;

	protected function setUp(): void
	{
		$this->importer = new SupplierPriceImporter(null);
		$this->tmpDir = sys_get_temp_dir() . '/cc_test_' . getmypid();
		if (!is_dir($this->tmpDir)) {
			mkdir($this->tmpDir, 0755, true);
		}
	}

	protected function tearDown(): void
	{
		// Clean up temp files
		$files = glob($this->tmpDir . '/*');
		if ($files) {
			foreach ($files as $file) {
				@unlink($file);
			}
		}
		@rmdir($this->tmpDir);
	}

	// ------------------------------------------------------------------
	// CSV import tests
	// ------------------------------------------------------------------

	public function testImportCSVBasic()
	{
		$csvFile = $this->tmpDir . '/prices.csv';
		$content = "ref;price_ht;supplier_ref\n";
		$content .= "CIM-32-25;8.50;LM-12345\n";
		$content .= "SABLE-0-4;45.00;LM-12346\n";
		file_put_contents($csvFile, $content);

		$result = $this->importer->importFromCSV($csvFile, 'Leroy Merlin', ';', 'ref');

		$this->assertEquals(2, $result['stats']['total']);
		$this->assertEquals(2, $result['stats']['created']);
		$this->assertEquals(0, $result['stats']['errors']);
		$this->assertCount(2, $result['prices']);

		$this->assertEquals('CIM-32-25', $result['prices'][0]['match_value']);
		$this->assertEquals(8.50, $result['prices'][0]['price_ht']);
		$this->assertEquals('Leroy Merlin', $result['prices'][0]['supplier']);
		$this->assertEquals('csv', $result['prices'][0]['source']);
	}

	public function testImportCSVWithEAN()
	{
		$csvFile = $this->tmpDir . '/prices_ean.csv';
		$content = "ean;price_ht;supplier_ref\n";
		$content .= "3245676543210;12.50;REF-A\n";
		file_put_contents($csvFile, $content);

		$result = $this->importer->importFromCSV($csvFile, 'Point P', ';', 'ean');

		$this->assertEquals(1, $result['stats']['created']);
		$this->assertEquals('3245676543210', $result['prices'][0]['match_value']);
	}

	public function testImportCSVWithErrors()
	{
		$csvFile = $this->tmpDir . '/prices_bad.csv';
		$content = "ref;price_ht\n";
		$content .= ";10.00\n";        // Missing ref
		$content .= "GOOD-REF;25.00\n"; // Valid
		$content .= "BAD;abc\n";        // Non-numeric price
		file_put_contents($csvFile, $content);

		$result = $this->importer->importFromCSV($csvFile, 'Test', ';', 'ref');

		$this->assertEquals(3, $result['stats']['total']);
		$this->assertEquals(1, $result['stats']['created']);
		$this->assertEquals(2, $result['stats']['errors']);
		$this->assertCount(2, $this->importer->errors);
	}

	public function testImportCSVFileNotFound()
	{
		$result = $this->importer->importFromCSV('/nonexistent/file.csv', 'Test');

		$this->assertEquals(0, $result['stats']['total']);
		$this->assertNotEmpty($this->importer->error);
	}

	public function testImportCSVCommaSeparated()
	{
		$csvFile = $this->tmpDir . '/prices_comma.csv';
		$content = "ref,price_ht,supplier_ref\n";
		$content .= "CIM-32-25,8.50,LM-12345\n";
		file_put_contents($csvFile, $content);

		$result = $this->importer->importFromCSV($csvFile, 'Test', ',', 'ref');

		$this->assertEquals(1, $result['stats']['created']);
		$this->assertEquals(8.50, $result['prices'][0]['price_ht']);
	}

	// ------------------------------------------------------------------
	// JSON import tests
	// ------------------------------------------------------------------

	public function testParseJSONPricesBasic()
	{
		$jsonFile = $this->tmpDir . '/prices.json';
		$data = array(
			'prices' => array(
				array('ref' => 'CIM-32-25', 'price_ht' => 8.50, 'supplier_ref' => 'LM-001'),
				array('ref' => 'SABLE-0-4', 'price_ht' => 45.00),
			),
		);
		file_put_contents($jsonFile, json_encode($data));

		$result = $this->importer->parseJSONPrices($jsonFile, 'Leroy Merlin', 'ref');

		$this->assertEquals(2, $result['stats']['total']);
		$this->assertEquals(2, $result['stats']['created']);
		$this->assertCount(2, $result['prices']);
		$this->assertEquals('url', $result['prices'][0]['source']);
	}

	public function testParseJSONPricesWithEAN()
	{
		$jsonFile = $this->tmpDir . '/prices_ean.json';
		$data = array(
			'prices' => array(
				array('ean' => '1234567890123', 'price_ht' => 15.00),
			),
		);
		file_put_contents($jsonFile, json_encode($data));

		$result = $this->importer->parseJSONPrices($jsonFile, 'Supplier', 'ean');

		$this->assertEquals(1, $result['stats']['created']);
		$this->assertEquals('1234567890123', $result['prices'][0]['match_value']);
	}

	public function testParseJSONPricesSkipMissingRef()
	{
		$jsonFile = $this->tmpDir . '/prices_noref.json';
		$data = array(
			'prices' => array(
				array('price_ht' => 10.00), // No ref
				array('ref' => 'GOOD', 'price_ht' => 20.00),
			),
		);
		file_put_contents($jsonFile, json_encode($data));

		$result = $this->importer->parseJSONPrices($jsonFile, 'Test', 'ref');

		$this->assertEquals(2, $result['stats']['total']);
		$this->assertEquals(1, $result['stats']['created']);
		$this->assertEquals(1, $result['stats']['skipped']);
	}

	public function testParseJSONInvalidData()
	{
		$jsonFile = $this->tmpDir . '/invalid.json';
		file_put_contents($jsonFile, 'not json');

		$result = $this->importer->parseJSONPrices($jsonFile, 'Test');

		$this->assertEquals(0, $result['stats']['created']);
		$this->assertNotEmpty($this->importer->error);
	}

	// ------------------------------------------------------------------
	// URL import tests
	// ------------------------------------------------------------------

	public function testImportFromURLInvalidURL()
	{
		$result = $this->importer->importFromURL('not-a-url', 'Test');

		$this->assertEquals(0, $result['stats']['total']);
		$this->assertStringContainsString('Invalid URL', $this->importer->error);
	}

	public function testImportFromURLInvalidScheme()
	{
		$result = $this->importer->importFromURL('ftp://example.com/prices.csv', 'Test');

		$this->assertEquals(0, $result['stats']['total']);
		$this->assertStringContainsString('http or https', $this->importer->error);
	}

	// ------------------------------------------------------------------
	// Interactive entry tests
	// ------------------------------------------------------------------

	public function testPrepareInteractiveEntryEmpty()
	{
		$entries = $this->importer->prepareInteractiveEntry(array('CIM-32-25', 'SABLE-0-4'), 'Leroy Merlin');

		$this->assertCount(2, $entries);
		$this->assertEquals('CIM-32-25', $entries[0]['ref']);
		$this->assertEquals('Leroy Merlin', $entries[0]['supplier']);
		$this->assertEquals(0, $entries[0]['last_price_ht']);
		$this->assertNull($entries[0]['new_price_ht']);
	}

	public function testPrepareInteractiveEntryWithCache()
	{
		// Pre-populate cache
		$this->importer->last_known_prices['CIM-32-25'] = array(
			'supplier' => 'Point P',
			'price_ht' => 9.00,
			'date_price' => '2026-01-15',
			'source' => 'csv',
		);

		$entries = $this->importer->prepareInteractiveEntry(array('CIM-32-25'));

		$this->assertCount(1, $entries);
		$this->assertEquals(9.00, $entries[0]['last_price_ht']);
		$this->assertEquals('2026-01-15', $entries[0]['last_date']);
		$this->assertEquals('Point P', $entries[0]['supplier']);
	}

	public function testProcessInteractiveEntry()
	{
		$entries = array(
			array('ref' => 'CIM-32-25', 'last_price_ht' => 0, 'new_price_ht' => 8.50),
			array('ref' => 'SABLE-0-4', 'last_price_ht' => 40.00, 'new_price_ht' => 45.00),
			array('ref' => 'SKIP-ME', 'last_price_ht' => 10.00, 'new_price_ht' => null),
		);

		$result = $this->importer->processInteractiveEntry($entries, 'Manual Supplier');

		$this->assertEquals(3, $result['stats']['total']);
		$this->assertEquals(1, $result['stats']['created']);  // CIM was new
		$this->assertEquals(1, $result['stats']['updated']);  // SABLE was update
		$this->assertEquals(1, $result['stats']['skipped']);  // SKIP-ME
		$this->assertCount(2, $result['prices']);
		$this->assertEquals('manual', $result['prices'][0]['source']);
	}

	public function testProcessInteractiveEntryNegativePrice()
	{
		$entries = array(
			array('ref' => 'BAD', 'last_price_ht' => 0, 'new_price_ht' => -5.00),
		);

		$result = $this->importer->processInteractiveEntry($entries, 'Test');

		$this->assertEquals(1, $result['stats']['errors']);
		$this->assertCount(0, $result['prices']);
	}

	// ------------------------------------------------------------------
	// Price cache tests
	// ------------------------------------------------------------------

	public function testSaveAndLoadPriceCache()
	{
		$cacheFile = $this->tmpDir . '/price_cache.json';

		$this->importer->last_known_prices['CIM-32-25'] = array(
			'price_ht' => 8.50,
			'supplier' => 'Leroy Merlin',
			'date_price' => '2026-03-01',
			'source' => 'csv',
		);

		$this->assertTrue($this->importer->savePriceCache($cacheFile));
		$this->assertFileExists($cacheFile);

		// New instance to load
		$importer2 = new SupplierPriceImporter(null);
		$this->assertTrue($importer2->loadPriceCache($cacheFile));
		$this->assertArrayHasKey('CIM-32-25', $importer2->last_known_prices);
		$this->assertEquals(8.50, $importer2->last_known_prices['CIM-32-25']['price_ht']);
	}

	public function testLoadPriceCacheNonexistent()
	{
		$this->assertFalse($this->importer->loadPriceCache('/nonexistent/cache.json'));
	}

	// ------------------------------------------------------------------
	// Price comparison tests
	// ------------------------------------------------------------------

	public function testCompareSupplierPrices()
	{
		$allPrices = array(
			array('match_value' => 'CIM-32-25', 'supplier' => 'Leroy Merlin', 'price_ht' => 8.50),
			array('match_value' => 'CIM-32-25', 'supplier' => 'Point P', 'price_ht' => 7.90),
			array('match_value' => 'CIM-32-25', 'supplier' => 'BigMat', 'price_ht' => 9.20),
			array('match_value' => 'OTHER', 'supplier' => 'Leroy Merlin', 'price_ht' => 100.00),
		);

		$comparison = $this->importer->compareSupplierPrices('CIM-32-25', $allPrices);

		$this->assertCount(3, $comparison);
		// Should be sorted cheapest first
		$this->assertEquals('Point P', $comparison[0]['supplier']);
		$this->assertEquals(7.90, $comparison[0]['price_ht']);
		$this->assertEquals('Leroy Merlin', $comparison[1]['supplier']);
		$this->assertEquals('BigMat', $comparison[2]['supplier']);
	}

	// ------------------------------------------------------------------
	// Validation tests
	// ------------------------------------------------------------------

	public function testValidateRowValid()
	{
		$row = array('ref' => 'TEST', 'price_ht' => '25.00');
		$errors = $this->importer->validateSupplierPriceRow($row, 'ref');
		$this->assertEmpty($errors);
	}

	public function testValidateRowMissingRef()
	{
		$row = array('ref' => '', 'price_ht' => '25.00');
		$errors = $this->importer->validateSupplierPriceRow($row, 'ref');
		$this->assertNotEmpty($errors);
	}

	public function testValidateRowMissingPrice()
	{
		$row = array('ref' => 'TEST', 'price_ht' => '');
		$errors = $this->importer->validateSupplierPriceRow($row, 'ref');
		$this->assertNotEmpty($errors);
	}

	public function testValidateRowNegativePrice()
	{
		$row = array('ref' => 'TEST', 'price_ht' => '-5.00');
		$errors = $this->importer->validateSupplierPriceRow($row, 'ref');
		$this->assertContains('Price HT cannot be negative', $errors);
	}
}
