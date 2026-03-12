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
 * \file       htdocs/constructioncosts/class/supplierpriceimporter.class.php
 * \ingroup    constructioncosts
 * \brief      Multi-source supplier price import and management
 */

/**
 * Class SupplierPriceImporter
 *
 * Handles supplier price import from multiple sources:
 * - CSV/Excel files
 * - URL-based data fetch (public price feeds)
 * - Interactive manual entry with last-known-value presets
 *
 * Each import source stores the last imported prices so they can be offered
 * as defaults during the next interactive session.
 */
class SupplierPriceImporter
{
	/** @var object Database handler */
	public $db;

	/** @var string Error message */
	public $error = '';

	/** @var array Error messages */
	public $errors = array();

	/** @var array Import statistics */
	public $import_stats = array(
		'total' => 0,
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'errors' => 0,
	);

	/** @var array Last known prices cache: product_ref => price_data */
	public $last_known_prices = array();

	// Import source types
	const SOURCE_CSV = 'csv';
	const SOURCE_URL = 'url';
	const SOURCE_MANUAL = 'manual';

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
	 * Import supplier prices from a CSV file.
	 *
	 * @param string $filepath   Path to the CSV file
	 * @param string $supplier   Supplier name
	 * @param string $separator  CSV field separator
	 * @param string $matchField Field to match products ('ean' or 'ref')
	 * @return array Array with keys: stats, prices (imported price records)
	 */
	public function importFromCSV($filepath, $supplier, $separator = ';', $matchField = 'ref')
	{
		$this->resetStats();
		$prices = array();

		if (!file_exists($filepath)) {
			$this->error = 'File not found: ' . $filepath;
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		$handle = fopen($filepath, 'r');
		if ($handle === false) {
			$this->error = 'Unable to open file: ' . $filepath;
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		$headers = fgetcsv($handle, 0, $separator);
		if ($headers === false) {
			$this->error = 'Empty or invalid CSV file';
			fclose($handle);
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}
		$headers = array_map('trim', array_map('strtolower', $headers));

		$lineNum = 1;
		while (($data = fgetcsv($handle, 0, $separator)) !== false) {
			$lineNum++;
			$this->import_stats['total']++;

			$row = array();
			foreach ($headers as $idx => $header) {
				$row[$header] = isset($data[$idx]) ? trim($data[$idx]) : '';
			}

			$validation = $this->validateSupplierPriceRow($row, $matchField);
			if (!empty($validation)) {
				$this->errors[] = 'Line ' . $lineNum . ': ' . implode('; ', $validation);
				$this->import_stats['errors']++;
				continue;
			}

			$matchValue = $matchField === 'ean'
				? (isset($row['ean']) ? $row['ean'] : '')
				: (isset($row['ref']) ? $row['ref'] : '');

			$priceRecord = array(
				'match_field' => $matchField,
				'match_value' => $matchValue,
				'supplier' => $supplier,
				'supplier_ref' => isset($row['supplier_ref']) ? $row['supplier_ref'] : '',
				'price_ht' => isset($row['price_ht']) ? (float) $row['price_ht'] : 0,
				'currency' => isset($row['currency']) ? $row['currency'] : getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR'),
				'date_price' => date('Y-m-d'),
				'source' => self::SOURCE_CSV,
			);

			$prices[] = $priceRecord;
			$this->last_known_prices[$matchValue] = $priceRecord;
			$this->import_stats['created']++;
		}

		fclose($handle);
		return array('stats' => $this->import_stats, 'prices' => $prices);
	}

	/**
	 * Import supplier prices from a URL (fetch remote CSV or JSON).
	 *
	 * @param string $url        URL to fetch data from
	 * @param string $supplier   Supplier name
	 * @param string $format     Format: 'csv' or 'json'
	 * @param string $separator  CSV separator (for CSV format)
	 * @param string $matchField Match field: 'ean' or 'ref'
	 * @return array Array with keys: stats, prices
	 */
	public function importFromURL($url, $supplier, $format = 'csv', $separator = ';', $matchField = 'ref')
	{
		$this->resetStats();
		$prices = array();

		// Validate URL
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			$this->error = 'Invalid URL: ' . $url;
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		// Validate URL scheme - only allow http(s)
		$scheme = parse_url($url, PHP_URL_SCHEME);
		if (!in_array($scheme, array('http', 'https'), true)) {
			$this->error = 'URL must use http or https scheme';
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		// Download to temp file
		$tmpFile = tempnam(sys_get_temp_dir(), 'supplier_import_');
		if ($tmpFile === false) {
			$this->error = 'Cannot create temporary file';
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		$context = stream_context_create(array(
			'http' => array(
				'timeout' => 30,
				'user_agent' => 'Dolibarr-ConstructionCosts/1.0',
			),
		));

		$content = @file_get_contents($url, false, $context);
		if ($content === false) {
			$this->error = 'Failed to fetch URL: ' . $url;
			@unlink($tmpFile);
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		file_put_contents($tmpFile, $content);

		if ($format === 'json') {
			$result = $this->parseJSONPrices($tmpFile, $supplier, $matchField);
		} else {
			$result = $this->importFromCSV($tmpFile, $supplier, $separator, $matchField);
		}

		@unlink($tmpFile);
		return $result;
	}

	/**
	 * Parse JSON supplier prices file.
	 *
	 * Expected JSON format:
	 * { "prices": [ { "ref": "...", "ean": "...", "price_ht": 12.50, "supplier_ref": "..." }, ... ] }
	 *
	 * @param string $filepath   Path to JSON file
	 * @param string $supplier   Supplier name
	 * @param string $matchField Match field: 'ean' or 'ref'
	 * @return array Array with keys: stats, prices
	 */
	public function parseJSONPrices($filepath, $supplier, $matchField = 'ref')
	{
		$this->resetStats();
		$prices = array();

		$content = file_get_contents($filepath);
		if ($content === false) {
			$this->error = 'Cannot read file: ' . $filepath;
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		$data = json_decode($content, true);
		if ($data === null) {
			$this->error = 'Invalid JSON data';
			return array('stats' => $this->import_stats, 'prices' => $prices);
		}

		$items = isset($data['prices']) ? $data['prices'] : (is_array($data) ? $data : array());

		foreach ($items as $item) {
			$this->import_stats['total']++;

			$matchValue = $matchField === 'ean'
				? (isset($item['ean']) ? $item['ean'] : '')
				: (isset($item['ref']) ? $item['ref'] : '');

			if (empty($matchValue)) {
				$this->import_stats['skipped']++;
				continue;
			}

			$priceHT = isset($item['price_ht']) ? $item['price_ht'] : (isset($item['price']) ? $item['price'] : 0);

			$priceRecord = array(
				'match_field' => $matchField,
				'match_value' => $matchValue,
				'supplier' => $supplier,
				'supplier_ref' => isset($item['supplier_ref']) ? $item['supplier_ref'] : '',
				'price_ht' => (float) $priceHT,
				'currency' => isset($item['currency']) ? $item['currency'] : getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR'),
				'date_price' => date('Y-m-d'),
				'source' => self::SOURCE_URL,
			);

			$prices[] = $priceRecord;
			$this->last_known_prices[$matchValue] = $priceRecord;
			$this->import_stats['created']++;
		}

		return array('stats' => $this->import_stats, 'prices' => $prices);
	}

	/**
	 * Prepare an interactive price entry form data.
	 *
	 * Returns product references with their last known supplier prices
	 * so the user can update them one by one with pre-populated defaults.
	 *
	 * @param array  $productRefs  Array of product references to get prices for
	 * @param string $supplier     Supplier name
	 * @return array Array of price entries with keys: ref, last_price, last_date, supplier
	 */
	public function prepareInteractiveEntry($productRefs, $supplier = '')
	{
		$entries = array();

		foreach ($productRefs as $ref) {
			$lastKnown = isset($this->last_known_prices[$ref]) ? $this->last_known_prices[$ref] : null;

			$entries[] = array(
				'ref' => $ref,
				'supplier' => !empty($supplier) ? $supplier : ($lastKnown ? $lastKnown['supplier'] : ''),
				'last_price_ht' => $lastKnown ? (float) $lastKnown['price_ht'] : 0,
				'last_date' => $lastKnown ? $lastKnown['date_price'] : '',
				'last_source' => $lastKnown ? $lastKnown['source'] : '',
				'new_price_ht' => null, // To be filled by user
			);
		}

		return $entries;
	}

	/**
	 * Process interactive price entries submitted by user.
	 *
	 * @param array  $entries  Array of entries from prepareInteractiveEntry with new_price_ht filled
	 * @param string $supplier Supplier name
	 * @return array Array with keys: stats, prices
	 */
	public function processInteractiveEntry($entries, $supplier)
	{
		$this->resetStats();
		$prices = array();

		foreach ($entries as $entry) {
			$this->import_stats['total']++;

			if ($entry['new_price_ht'] === null || $entry['new_price_ht'] === '') {
				$this->import_stats['skipped']++;
				continue;
			}

			$newPrice = (float) $entry['new_price_ht'];
			if ($newPrice < 0) {
				$this->errors[] = 'Negative price for ref ' . $entry['ref'];
				$this->import_stats['errors']++;
				continue;
			}

			$priceRecord = array(
				'match_field' => 'ref',
				'match_value' => $entry['ref'],
				'supplier' => $supplier,
				'supplier_ref' => '',
				'price_ht' => $newPrice,
				'currency' => getDolGlobalString('CONSTRUCTIONCOSTS_DEFAULT_CURRENCY', 'EUR'),
				'date_price' => date('Y-m-d'),
				'source' => self::SOURCE_MANUAL,
			);

			$prices[] = $priceRecord;
			$this->last_known_prices[$entry['ref']] = $priceRecord;

			// Was it an update or creation?
			if ($entry['last_price_ht'] > 0) {
				$this->import_stats['updated']++;
			} else {
				$this->import_stats['created']++;
			}
		}

		return array('stats' => $this->import_stats, 'prices' => $prices);
	}

	/**
	 * Load last known prices from previously imported data (JSON cache file).
	 *
	 * @param string $cacheFile Path to JSON cache file
	 * @return bool True on success
	 */
	public function loadPriceCache($cacheFile)
	{
		if (!file_exists($cacheFile)) {
			return false;
		}

		$content = file_get_contents($cacheFile);
		if ($content === false) {
			return false;
		}

		$data = json_decode($content, true);
		if (!is_array($data)) {
			return false;
		}

		$this->last_known_prices = $data;
		return true;
	}

	/**
	 * Save last known prices to a JSON cache file.
	 *
	 * @param string $cacheFile Path to JSON cache file
	 * @return bool True on success
	 */
	public function savePriceCache($cacheFile)
	{
		$dir = dirname($cacheFile);
		if (!is_dir($dir)) {
			return false;
		}
		$result = file_put_contents($cacheFile, json_encode($this->last_known_prices, JSON_PRETTY_PRINT));
		return $result !== false;
	}

	/**
	 * Validate a supplier price row.
	 *
	 * @param array  $row        Row data
	 * @param string $matchField Match field: 'ean' or 'ref'
	 * @return array Validation errors (empty if valid)
	 */
	public function validateSupplierPriceRow($row, $matchField = 'ref')
	{
		$errors = array();

		$matchValue = $matchField === 'ean'
			? (isset($row['ean']) ? $row['ean'] : '')
			: (isset($row['ref']) ? $row['ref'] : '');

		if (empty($matchValue)) {
			$errors[] = 'Product ' . $matchField . ' is required';
		}

		$priceHT = isset($row['price_ht']) ? $row['price_ht'] : '';
		if ($priceHT === '' || !is_numeric($priceHT)) {
			$errors[] = 'Price HT must be a valid number';
		} elseif ((float) $priceHT < 0) {
			$errors[] = 'Price HT cannot be negative';
		}

		return $errors;
	}

	/**
	 * Get price comparison across suppliers for a product reference.
	 *
	 * @param string $productRef Product reference
	 * @param array  $allPrices  Array of all imported price records
	 * @return array Array of supplier prices sorted by price ascending
	 */
	public function compareSupplierPrices($productRef, $allPrices)
	{
		$matching = array();

		foreach ($allPrices as $price) {
			if ($price['match_value'] === $productRef) {
				$matching[] = $price;
			}
		}

		// Sort by price ascending (cheapest first)
		usort($matching, function ($a, $b) {
			return $a['price_ht'] <=> $b['price_ht'];
		});

		return $matching;
	}

	/**
	 * Reset import statistics.
	 *
	 * @return void
	 */
	private function resetStats()
	{
		$this->import_stats = array(
			'total' => 0,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
		);
		$this->errors = array();
		$this->error = '';
	}
}
