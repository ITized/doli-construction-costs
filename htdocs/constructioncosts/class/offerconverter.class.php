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
 * \file       htdocs/constructioncosts/class/offerconverter.class.php
 * \ingroup    constructioncosts
 * \brief      Offer-to-invoice conversion with construction cost data preservation
 */

/**
 * Class OfferConverter
 *
 * Handles the conversion of proposals (offers/quotes) into invoices
 * while preserving all construction cost metadata:
 * - Work template origin reference
 * - Material cost breakdown
 * - Labor cost breakdown
 * - Margin details
 * - Original parameters used for generation
 *
 * This ensures full traceability from initial offer to final invoice.
 */
class OfferConverter
{
	/** @var object Database handler */
	public $db;

	/** @var string Error message */
	public $error = '';

	/** @var array Error messages */
	public $errors = array();

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
	 * Build invoice lines from proposal lines, preserving construction cost metadata.
	 *
	 * Takes generated lines from WorkTemplate (which are stored on the proposal)
	 * and prepares them for invoice creation with all metadata intact.
	 *
	 * @param array $propalLines Array of proposal line data (from WorkTemplate::generateLines output)
	 * @param array $propalMeta  Proposal metadata (work_template_ref, params, etc.)
	 * @return array Invoice-ready line data with metadata
	 */
	public function convertLinesToInvoice($propalLines, $propalMeta = array())
	{
		$invoiceLines = array();

		foreach ($propalLines as $line) {
			$invoiceLine = array(
				'description' => $this->buildInvoiceDescription($line),
				'qty' => (float) $line['qty'],
				'unit_price_ht' => (float) $line['unit_price_ht'],
				'vat_rate' => (float) $line['vat_rate'],
				'total_ht' => (float) $line['total_ht'],
				'total_vat' => (float) price2num($line['total_ht'] * $line['vat_rate'] / 100, 'MT'),
				'total_ttc' => (float) price2num($line['total_ht'] * (1 + $line['vat_rate'] / 100), 'MT'),
				'product_ref' => isset($line['product_ref']) ? $line['product_ref'] : '',
				'unit_code' => isset($line['unit_code']) ? $line['unit_code'] : '',
				// Construction cost metadata preserved on invoice
				'cc_meta' => array(
					'template_ref' => isset($propalMeta['work_template_ref']) ? $propalMeta['work_template_ref'] : '',
					'step_ref' => isset($line['ref']) ? $line['ref'] : '',
					'step_type' => isset($line['step_type']) ? $line['step_type'] : '',
					'step_category' => isset($line['step_category']) ? $line['step_category'] : '',
					'original_params' => isset($propalMeta['params']) ? $propalMeta['params'] : array(),
					'generated_date' => isset($propalMeta['generated_date']) ? $propalMeta['generated_date'] : '',
					'propal_ref' => isset($propalMeta['propal_ref']) ? $propalMeta['propal_ref'] : '',
				),
			);

			$invoiceLines[] = $invoiceLine;
		}

		return $invoiceLines;
	}

	/**
	 * Build a rich invoice description for a line.
	 *
	 * @param array $line Line data from WorkTemplate
	 * @return string Formatted description
	 */
	public function buildInvoiceDescription($line)
	{
		$desc = '';

		if (!empty($line['label'])) {
			$desc .= $line['label'];
		}

		if (!empty($line['description'])) {
			$desc .= "\n" . $line['description'];
		}

		if (!empty($line['product_ref'])) {
			$desc .= "\n[Réf: " . $line['product_ref'] . "]";
		}

		return $desc;
	}

	/**
	 * Generate a cost summary for an invoice, breaking down materials vs labor.
	 *
	 * @param array $invoiceLines Invoice lines (from convertLinesToInvoice)
	 * @return array Summary with keys: total_materials_ht, total_labor_ht, total_ht, total_vat, total_ttc, margin_info
	 */
	public function generateCostSummary($invoiceLines)
	{
		$totalMaterials = 0;
		$totalLabor = 0;
		$totalHT = 0;
		$totalVAT = 0;
		$totalTTC = 0;
		$categories = array();

		foreach ($invoiceLines as $line) {
			$totalHT += $line['total_ht'];
			$totalVAT += $line['total_vat'];
			$totalTTC += $line['total_ttc'];

			$stepType = isset($line['cc_meta']['step_type']) ? $line['cc_meta']['step_type'] : 'product';
			if ($stepType === 'service') {
				$totalLabor += $line['total_ht'];
			} else {
				$totalMaterials += $line['total_ht'];
			}

			$cat = isset($line['cc_meta']['step_category']) ? $line['cc_meta']['step_category'] : 'other';
			if (!isset($categories[$cat])) {
				$categories[$cat] = 0;
			}
			$categories[$cat] += $line['total_ht'];
		}

		return array(
			'total_materials_ht' => (float) price2num($totalMaterials, 'MT'),
			'total_labor_ht' => (float) price2num($totalLabor, 'MT'),
			'total_ht' => (float) price2num($totalHT, 'MT'),
			'total_vat' => (float) price2num($totalVAT, 'MT'),
			'total_ttc' => (float) price2num($totalTTC, 'MT'),
			'material_percent' => $totalHT > 0 ? round($totalMaterials / $totalHT * 100, 1) : 0,
			'labor_percent' => $totalHT > 0 ? round($totalLabor / $totalHT * 100, 1) : 0,
			'categories' => $categories,
		);
	}

	/**
	 * Validate that all lines from a proposal can be converted to invoice.
	 *
	 * @param array $propalLines Proposal lines
	 * @return array Array of validation errors (empty if all OK)
	 */
	public function validateForConversion($propalLines)
	{
		$errors = array();

		if (empty($propalLines)) {
			$errors[] = 'No lines to convert';
			return $errors;
		}

		foreach ($propalLines as $idx => $line) {
			$lineNum = $idx + 1;

			if (!isset($line['qty']) || (float) $line['qty'] <= 0) {
				$errors[] = 'Line ' . $lineNum . ': quantity must be positive';
			}

			if (!isset($line['unit_price_ht'])) {
				$errors[] = 'Line ' . $lineNum . ': unit price HT is required';
			}

			if (!isset($line['vat_rate'])) {
				$errors[] = 'Line ' . $lineNum . ': VAT rate is required';
			}
		}

		return $errors;
	}

	/**
	 * Prepare metadata for the invoice note/extrafields.
	 *
	 * @param string $templateRef Work template reference
	 * @param array  $params      Original generation parameters
	 * @param string $propalRef   Source proposal reference
	 * @return array Metadata array
	 */
	public function prepareInvoiceMeta($templateRef, $params, $propalRef)
	{
		return array(
			'work_template_ref' => $templateRef,
			'params' => $params,
			'propal_ref' => $propalRef,
			'generated_date' => date('Y-m-d H:i:s'),
			'module' => 'constructioncosts',
			'version' => '1.0.0',
		);
	}

	/**
	 * Generate a note text for the invoice preserving origin info.
	 *
	 * @param array $meta Metadata from prepareInvoiceMeta
	 * @return string Note text for invoice
	 */
	public function generateInvoiceNote($meta)
	{
		$note = '';

		if (!empty($meta['work_template_ref'])) {
			$note .= 'Work template: ' . $meta['work_template_ref'] . "\n";
		}

		if (!empty($meta['propal_ref'])) {
			$note .= 'From proposal: ' . $meta['propal_ref'] . "\n";
		}

		if (!empty($meta['params'])) {
			$note .= 'Parameters:';
			foreach ($meta['params'] as $key => $value) {
				$note .= ' ' . $key . '=' . $value;
			}
			$note .= "\n";
		}

		if (!empty($meta['generated_date'])) {
			$note .= 'Generated: ' . $meta['generated_date'] . "\n";
		}

		return $note;
	}
}
