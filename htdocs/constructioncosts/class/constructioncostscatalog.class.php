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
 * \file       htdocs/constructioncosts/class/constructioncostscatalog.class.php
 * \ingroup    constructioncosts
 * \brief      Catalog management class for construction products and services
 */

/**
 * Class ConstructionCostsCatalog
 *
 * Manages the import/export and synchronization of construction product catalogs.
 * Supports CSV import, multi-supplier management, and EAN-based product matching.
 */
class ConstructionCostsCatalog
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
	 * @var array Import statistics
	 */
	public $import_stats = array(
		'total' => 0,
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'errors' => 0,
	);

	// CSV column mapping constants
	const CSV_COL_REF = 0;
	const CSV_COL_LABEL = 1;
	const CSV_COL_DESCRIPTION = 2;
	const CSV_COL_CATEGORY = 3;
	const CSV_COL_TYPE = 4;
	const CSV_COL_PRICE_HT = 5;
	const CSV_COL_VAT_RATE = 6;
	const CSV_COL_UNIT = 7;
	const CSV_COL_EAN = 8;
	const CSV_COL_SUPPLIER = 9;
	const CSV_COL_SUPPLIER_REF = 10;
	const CSV_COL_SUPPLIER_PRICE = 11;

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
	 * Parse a CSV file and return structured data.
	 *
	 * @param string $filepath     Path to the CSV file
	 * @param string $separator    Field separator (default: ';')
	 * @param string $enclosure    Field enclosure (default: '"')
	 * @param bool   $hasHeader    Whether first row is a header (default: true)
	 * @return array|false Array of rows, or false on error
	 */
	public function parseCSV($filepath, $separator = ';', $enclosure = '"', $hasHeader = true)
	{
		if (!file_exists($filepath)) {
			$this->error = 'File not found: ' . $filepath;
			return false;
		}

		if (!is_readable($filepath)) {
			$this->error = 'File not readable: ' . $filepath;
			return false;
		}

		$handle = fopen($filepath, 'r');
		if ($handle === false) {
			$this->error = 'Unable to open file: ' . $filepath;
			return false;
		}

		$rows = array();
		$lineNum = 0;
		$headers = array();

		while (($data = fgetcsv($handle, 0, $separator, $enclosure)) !== false) {
			$lineNum++;

			if ($hasHeader && $lineNum === 1) {
				$headers = $data;
				continue;
			}

			if ($hasHeader && !empty($headers)) {
				$row = array();
				foreach ($headers as $idx => $header) {
					$row[trim($header)] = isset($data[$idx]) ? trim($data[$idx]) : '';
				}
				$rows[] = $row;
			} else {
				$rows[] = array_map('trim', $data);
			}
		}

		fclose($handle);
		return $rows;
	}

	/**
	 * Validate a CSV row for import.
	 *
	 * @param array $row Row data (associative or indexed)
	 * @return array Array of validation errors (empty if valid)
	 */
	public function validateImportRow($row)
	{
		$errors = array();

		// Check required fields based on associative keys or numeric indices
		$ref = $this->getRowValue($row, 'ref', self::CSV_COL_REF);
		$label = $this->getRowValue($row, 'label', self::CSV_COL_LABEL);
		$type = $this->getRowValue($row, 'type', self::CSV_COL_TYPE);
		$priceHT = $this->getRowValue($row, 'price_ht', self::CSV_COL_PRICE_HT);

		if (empty($ref)) {
			$errors[] = 'Reference is required';
		}

		if (empty($label)) {
			$errors[] = 'Label is required';
		}

		if (!empty($type) && !in_array(strtolower($type), array('product', 'service', 'mixed'))) {
			$errors[] = 'Type must be product, service or mixed';
		}

		if ($priceHT !== '' && !is_numeric($priceHT)) {
			$errors[] = 'Price HT must be numeric';
		}

		if ($priceHT !== '' && (float) $priceHT < 0) {
			$errors[] = 'Price HT cannot be negative';
		}

		$vatRate = $this->getRowValue($row, 'vat_rate', self::CSV_COL_VAT_RATE);
		if ($vatRate !== '' && !is_numeric($vatRate)) {
			$errors[] = 'VAT rate must be numeric';
		}

		return $errors;
	}

	/**
	 * Get a value from a row by key or index.
	 *
	 * @param array      $row   Row data
	 * @param string     $key   Associative key
	 * @param int        $index Numeric index fallback
	 * @return string The value, or empty string if not found
	 */
	public function getRowValue($row, $key, $index)
	{
		if (isset($row[$key])) {
			return (string) $row[$key];
		}
		if (isset($row[$index])) {
			return (string) $row[$index];
		}
		return '';
	}

	/**
	 * Match a product by EAN/barcode.
	 *
	 * @param string $ean    The EAN/barcode to search
	 * @return int|false The product rowid, or false if not found
	 */
	public function findProductByEAN($ean)
	{
		if (empty($ean)) {
			return false;
		}

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product";
		$sql .= " WHERE barcode = '" . $this->db->escape($ean) . "'";
		$sql .= " LIMIT 1";

		dol_syslog(get_class($this) . "::findProductByEAN", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);
				return (int) $obj->rowid;
			}
		}

		return false;
	}

	/**
	 * Match a product by reference.
	 *
	 * @param string $ref The product reference to search
	 * @return int|false The product rowid, or false if not found
	 */
	public function findProductByRef($ref)
	{
		if (empty($ref)) {
			return false;
		}

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product";
		$sql .= " WHERE ref = '" . $this->db->escape($ref) . "'";
		$sql .= " LIMIT 1";

		dol_syslog(get_class($this) . "::findProductByRef", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);
				return (int) $obj->rowid;
			}
		}

		return false;
	}

	/**
	 * Generate a CSV template for import.
	 *
	 * @return string CSV content string with header row
	 */
	public function generateCSVTemplate()
	{
		$separator = getDolGlobalString('CONSTRUCTIONCOSTS_CSV_SEPARATOR', ';');

		$headers = array(
			'ref',
			'label',
			'description',
			'category',
			'type',
			'price_ht',
			'vat_rate',
			'unit',
			'ean',
			'supplier',
			'supplier_ref',
			'supplier_price',
		);

		return implode($separator, $headers) . "\n";
	}

	/**
	 * Scheduled job: Synchronize supplier prices.
	 * Called by Dolibarr cron system.
	 *
	 * @return int 0 if OK, -1 if KO
	 */
	public function doScheduledJob()
	{
		dol_syslog(get_class($this) . "::doScheduledJob starting supplier price sync", LOG_INFO);

		// Only run if supplier sync is enabled
		if (!getDolGlobalInt('CONSTRUCTIONCOSTS_ENABLE_SUPPLIER_SYNC')) {
			dol_syslog(get_class($this) . "::doScheduledJob supplier sync is disabled", LOG_INFO);
			return 0;
		}

		// Placeholder for supplier sync logic
		// In future versions, this will iterate over active supplier configs
		// and fetch/update prices from external APIs or feeds

		dol_syslog(get_class($this) . "::doScheduledJob completed", LOG_INFO);
		return 0;
	}
}
