<?php
/**
 * Unit tests for CSV import field-mapping wizard logic.
 *
 * Tests cover:
 *   1. Column rename via col_map (mapinitcsv path)
 *   2. __skip__ sentinel produces ignore-friendly column names
 *   3. Core-property flag sync from mapped column names
 *   4. CSV column header parsing (BOM/whitespace stripping)
 *   5. record_separator: literal '\n' vs actual newline edge-case
 *
 * These tests are intentionally standalone — no Joomla bootstrap required.
 *
 * @package FLEXIcontent
 * @since   4.0
 */

namespace FLEXIcontent\Tests\Unit\Import;

use PHPUnit\Framework\TestCase;

/**
 * @covers FlexicontentControllerImport (logic extracted for unit-testability)
 */
class CsvColumnMappingTest extends TestCase
{
	// ────────────────────────────────────────────────────────────────────────
	// Helpers that mirror the production code (extracted pure functions)
	// ────────────────────────────────────────────────────────────────────────

	/**
	 * Apply col_map to rename CSV column headers.
	 * Mirrors the block in FlexicontentControllerImport::importcsv()
	 * labelled "Apply field mapping (mapinitcsv)".
	 *
	 * @param   string[]  $columns  Original CSV header names
	 * @param   string[]  $col_map  POST col_map: csvColName => fcFieldName
	 *
	 * @return  string[]  Renamed columns
	 */
	private function applyColumnMap(array $columns, array $col_map): array
	{
		foreach ($columns as $i => $col)
		{
			// Mirror client-side sanitization: alphanumeric + underscore only, max 64.
			// Empty result falls back to col_<index> (same as parseCsv() JS logic).
			$col_key = preg_replace('/[^A-Za-z0-9_]/', '_', $col);
			$col_key = $col_key !== '' ? substr($col_key, 0, 64) : ('col_' . $i);

			// Accept both sanitized key (new client) and raw header (legacy).
			$lookup  = isset($col_map[$col_key]) ? $col_key
			         : (isset($col_map[$col]) ? $col : null);

			if ($lookup !== null)
			{
				$mapped = trim($col_map[$lookup]);
				$columns[$i] = ($mapped === '' || $mapped === '__skip__')
					? ('__skip_' . $i . '__')
					: $mapped;
			}
		}

		return $columns;
	}

	/**
	 * Sanitize a raw CSV header into an HTML-name-safe key.
	 * Mirrors parseCsv() JS in admin/views/import/tmpl/import.php and
	 * the lookup-key logic in importcsv() (Batch 1 fix B1).
	 */
	private function safeColumnKey(string $rawHeader, int $index): string
	{
		$safe = preg_replace('/[^A-Za-z0-9_]/', '_', $rawHeader);

		return $safe !== '' ? substr($safe, 0, 64) : ('col_' . $index);
	}

	/**
	 * Sync xxx_col flags from the renamed column list.
	 * Mirrors the "Sync xxx_col flags from mapped column names" block.
	 *
	 * @param   string[]  $columns  Renamed column headers
	 * @param   array     $conf     Import config (mutated in-place)
	 *
	 * @return  array     Updated $conf
	 */
	private function syncColFlags(array $columns, array $conf): array
	{
		if (in_array('catid', $columns))
		{
			$conf['maincat_col'] = 1;
		}

		if (in_array('cid', $columns))
		{
			$conf['seccats_col'] = 1;
		}

		if (in_array('state', $columns))
		{
			$conf['state'] = -99;
		}

		if (in_array('access', $columns))
		{
			$conf['access'] = 0;
		}

		if (in_array('language', $columns))
		{
			$conf['language'] = '-99';
		}

		foreach (['created_by','modified_by','metadesc','metakey','custom_ititle',
		          'modified','created','publish_up','publish_down'] as $cf)
		{
			if (in_array($cf, $columns))
			{
				$conf[$cf . '_col'] = 1;
			}
		}

		if (in_array('tags_raw', $columns) || in_array('tags_names', $columns))
		{
			$conf['tags_col'] = 1;
		}

		return $conf;
	}

	/**
	 * Expand escape sequences in a separator string.
	 * Mirrors the preg_replace_callback expand logic.
	 *
	 * @param   string  $sep  Separator with possible escape sequences like '\n'
	 *
	 * @return  string  Expanded separator
	 */
	private function expandSeparator(string $sep): string
	{
		$pattern = '/(?<!\\\)(\\\(?:n|r|t|v|f|[0-7]{1,3}|x[0-9a-f]{1,2}))/i';

		return preg_replace_callback(
			$pattern,
			static function (array $m): string {
				$r = $m[1];
				eval("\$r = \"$r\";");

				return $r;
			},
			$sep
		);
	}

	/**
	 * Strip BOM + control characters from column names.
	 * Mirrors the preg_replace in the column-cleaning loop.
	 *
	 * @param   string[]  $columns  Raw column names from CSV header
	 *
	 * @return  string[]  Cleaned column names
	 */
	private function cleanColumnNames(array $columns): array
	{
		foreach ($columns as $i => $col)
		{
			$columns[$i] = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', trim($col));
		}

		return $columns;
	}


	// ────────────────────────────────────────────────────────────────────────
	// Tests: column rename via col_map
	// ────────────────────────────────────────────────────────────────────────

	/** @test */
	public function column_map_renames_csv_headers_to_fc_field_names(): void
	{
		$columns = ['Product Name', 'Description', 'Category ID', 'Price', 'SKU'];
		$col_map = [
			'Product Name' => 'title',
			'Description'  => 'text',
			'Category ID'  => 'catid',
			'Price'        => 'price_field',
			'SKU'          => '__skip__',
		];

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertSame('title',       $result[0]);
		$this->assertSame('text',        $result[1]);
		$this->assertSame('catid',       $result[2]);
		$this->assertSame('price_field', $result[3]);
		$this->assertStringStartsWith('__skip_', $result[4], 'Skipped columns get unique __skip_ prefix');
	}

	/** @test */
	public function empty_mapping_value_is_treated_as_skip(): void
	{
		$columns = ['Name', 'Notes'];
		$col_map = ['Name' => 'title', 'Notes' => ''];

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertStringStartsWith('__skip_', $result[1]);
	}

	/** @test */
	public function columns_not_in_col_map_are_left_unchanged(): void
	{
		$columns = ['title', 'extra_col'];
		$col_map = ['title' => 'title'];    // extra_col not in map

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertSame('extra_col', $result[1], 'Unmapped columns remain as-is');
	}

	/** @test */
	public function col_map_with_whitespace_values_is_treated_as_skip(): void
	{
		$columns = ['Col'];
		$col_map = ['Col' => '   '];   // only spaces

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertStringStartsWith('__skip_', $result[0]);
	}

	/** @test */
	public function skip_tokens_are_unique_per_column_index(): void
	{
		$columns = ['A', 'B', 'C'];
		$col_map = ['A' => '__skip__', 'B' => '__skip__', 'C' => '__skip__'];

		$result = $this->applyColumnMap($columns, $col_map);

		// All three skip tokens must be unique
		$this->assertCount(3, array_unique($result), 'Each __skip__ column gets a unique token');
	}


	// ────────────────────────────────────────────────────────────────────────
	// Tests: col-flag sync
	// ────────────────────────────────────────────────────────────────────────

	/** @test */
	public function catid_column_sets_maincat_col_flag(): void
	{
		$conf = ['maincat_col' => 0, 'maincat' => 5];
		$conf = $this->syncColFlags(['title', 'catid', 'text'], $conf);

		$this->assertSame(1, $conf['maincat_col']);
	}

	/** @test */
	public function state_column_sets_state_to_minus99(): void
	{
		$conf = ['state' => 1];
		$conf = $this->syncColFlags(['title', 'state'], $conf);

		$this->assertSame(-99, $conf['state']);
	}

	/** @test */
	public function access_column_sets_access_to_zero(): void
	{
		$conf = ['access' => 1];
		$conf = $this->syncColFlags(['title', 'access'], $conf);

		$this->assertSame(0, $conf['access']);
	}

	/** @test */
	public function language_column_sets_language_to_minus99_string(): void
	{
		$conf = ['language' => '*'];
		$conf = $this->syncColFlags(['title', 'language'], $conf);

		$this->assertSame('-99', $conf['language']);
	}

	/** @test */
	public function tags_raw_column_sets_tags_col_flag(): void
	{
		$conf = ['tags_col' => 0];
		$conf = $this->syncColFlags(['title', 'tags_raw'], $conf);

		$this->assertSame(1, $conf['tags_col']);
	}

	/** @test */
	public function tags_names_column_also_sets_tags_col_flag(): void
	{
		$conf = ['tags_col' => 0];
		$conf = $this->syncColFlags(['title', 'tags_names'], $conf);

		$this->assertSame(1, $conf['tags_col']);
	}

	/** @test */
	public function cid_column_sets_seccats_col_flag(): void
	{
		$conf = ['seccats_col' => 0];
		$conf = $this->syncColFlags(['title', 'cid'], $conf);

		$this->assertSame(1, $conf['seccats_col']);
	}

	/** @test */
	public function created_by_column_sets_created_by_col_flag(): void
	{
		$conf = ['created_by_col' => 0];
		$conf = $this->syncColFlags(['title', 'created_by'], $conf);

		$this->assertSame(1, $conf['created_by_col']);
	}

	/** @test */
	public function absent_core_column_leaves_flags_unchanged(): void
	{
		$conf = ['maincat_col' => 0, 'seccats_col' => 0, 'state' => 1];
		$conf = $this->syncColFlags(['title', 'text', 'price'], $conf);

		$this->assertSame(0, $conf['maincat_col']);
		$this->assertSame(0, $conf['seccats_col']);
		$this->assertSame(1, $conf['state']);
	}


	// ────────────────────────────────────────────────────────────────────────
	// Tests: column name cleaning
	// ────────────────────────────────────────────────────────────────────────

	/** @test */
	public function utf8_bom_is_stripped_from_first_column(): void
	{
		// UTF-8 BOM is EF BB BF — stored in hex as \xEF\xBB\xBF
		$bom   = "\xEF\xBB\xBF";
		$cols  = [$bom . 'title', 'text'];
		$clean = $this->cleanColumnNames($cols);

		$this->assertSame('title', $clean[0], 'BOM must be stripped from first column name');
	}

	/** @test */
	public function control_characters_are_stripped_from_column_names(): void
	{
		$cols  = ["\x00\x01title\x1F", "text\x7F"];
		$clean = $this->cleanColumnNames($cols);

		$this->assertSame('title', $clean[0]);
		$this->assertSame('text', $clean[1]);
	}

	/** @test */
	public function column_names_are_trimmed_of_surrounding_spaces(): void
	{
		$cols  = ['  title  ', "\ttext\t"];
		$clean = $this->cleanColumnNames($cols);

		$this->assertSame('title', $clean[0]);
		$this->assertSame('text',  $clean[1]);
	}


	// ────────────────────────────────────────────────────────────────────────
	// Tests: record_separator expand
	// ────────────────────────────────────────────────────────────────────────

	/** @test */
	public function literal_backslash_n_expands_to_newline(): void
	{
		$sep = '\n';   // two chars: backslash + n (as stored in session/POST)
		$this->assertSame("\n", $this->expandSeparator($sep));
	}

	/** @test */
	public function actual_newline_is_left_unchanged_by_expand(): void
	{
		$sep = "\n";   // actual LF character
		$this->assertSame("\n", $this->expandSeparator($sep));
	}

	/** @test */
	public function literal_backslash_r_n_expands_to_crlf(): void
	{
		$sep = '\r\n';
		$this->assertSame("\r\n", $this->expandSeparator($sep));
	}

	/** @test */
	public function literal_backslash_t_expands_to_tab(): void
	{
		$sep = '\t';
		$this->assertSame("\t", $this->expandSeparator($sep));
	}

	/** @test */
	public function regular_comma_separator_is_unchanged(): void
	{
		$sep = ',';
		$this->assertSame(',', $this->expandSeparator($sep));
	}


	// ────────────────────────────────────────────────────────────────────────
	// Tests: integration of map + sync (end-to-end unit logic)
	// ────────────────────────────────────────────────────────────────────────

	/** @test */
	public function full_mapping_workflow_title_text_catid(): void
	{
		// CSV has generic column names; user maps them in Step 2
		$rawColumns = ['Product Name', 'Product Description', 'Cat ID', 'Old Price'];
		$col_map = [
			'Product Name'        => 'title',
			'Product Description' => 'text',
			'Cat ID'              => 'catid',
			'Old Price'           => '__skip__',
		];
		$conf = [
			'maincat_col' => 0,
			'maincat'     => 10,
			'state'       => 1,
		];

		$mapped = $this->applyColumnMap($rawColumns, $col_map);
		$conf   = $this->syncColFlags($mapped, $conf);

		$this->assertSame(['title', 'text', 'catid', '__skip_3__'], $mapped);
		$this->assertSame(1, $conf['maincat_col'], 'catid column must activate maincat_col flag');
		$this->assertSame(1, $conf['state'],       'state unchanged when state column not mapped');
	}

	/** @test */
	public function mapping_with_state_and_language_columns(): void
	{
		$rawColumns = ['title', 'body', 'publish_state', 'lang_code'];
		$col_map = [
			'title'         => 'title',
			'body'          => 'text',
			'publish_state' => 'state',
			'lang_code'     => 'language',
		];
		$conf = ['state' => 1, 'language' => 'en-GB'];

		$mapped = $this->applyColumnMap($rawColumns, $col_map);
		$conf   = $this->syncColFlags($mapped, $conf);

		$this->assertSame(['title', 'text', 'state', 'language'], $mapped);
		$this->assertSame(-99,   $conf['state'],    'state col must set state=-99');
		$this->assertSame('-99', $conf['language'], 'language col must set language=-99');
	}


	// ────────────────────────────────────────────────────────────────────────
	// Tests: Batch 1 fix B1 — sanitized lookup keys
	//   Headers containing [, ], ", ', spaces, or other non-[A-Za-z0-9_] chars
	//   could break HTML name attribute parsing (PHP $_POST[col_map] nesting).
	//   Client + server both sanitize headers to alphanumeric + underscore.
	// ────────────────────────────────────────────────────────────────────────

	/** @test */
	public function safe_key_strips_brackets_and_quotes(): void
	{
		$this->assertSame('foo_bar_', $this->safeColumnKey('foo[bar]', 0));
		$this->assertSame('_quoted_', $this->safeColumnKey('"quoted"', 1));
		$this->assertSame('col_w_space', $this->safeColumnKey('col w space', 2));
	}

	/** @test */
	public function safe_key_replaces_specials_with_underscores(): void
	{
		// Specials become '_', not stripped — matches JS regex behavior
		$this->assertSame('___',   $this->safeColumnKey('!!!', 5));
		$this->assertSame('a_b_c', $this->safeColumnKey('a b@c', 0));
	}

	/** @test */
	public function safe_key_falls_back_to_index_for_empty_header(): void
	{
		// Only truly empty headers (already filtered out client-side) fall back.
		$this->assertSame('col_0', $this->safeColumnKey('', 0));
		$this->assertSame('col_7', $this->safeColumnKey('', 7));
	}

	/** @test */
	public function safe_key_truncates_to_64_chars(): void
	{
		$long = str_repeat('a', 100);
		$key  = $this->safeColumnKey($long, 0);
		$this->assertSame(64, strlen($key));
	}

	/** @test */
	public function safe_key_preserves_existing_safe_headers(): void
	{
		$this->assertSame('title',    $this->safeColumnKey('title', 0));
		$this->assertSame('catid',    $this->safeColumnKey('catid', 1));
		$this->assertSame('user_id',  $this->safeColumnKey('user_id', 2));
	}

	/** @test */
	public function column_map_lookup_uses_sanitized_key_for_injection_safe_headers(): void
	{
		// Header has bracket — client sends col_map[foo_bar_], server must match.
		$columns = ['foo[bar]'];
		$col_map = ['foo_bar_' => 'title'];

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertSame('title', $result[0],
			'Server must resolve mapping via sanitized key when raw header contains special chars');
	}

	/** @test */
	public function column_map_lookup_falls_back_to_raw_header_for_legacy_clients(): void
	{
		// Legacy client (or test harness) sends raw header — server still resolves it.
		$columns = ['Product Name'];
		$col_map = ['Product Name' => 'title'];

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertSame('title', $result[0],
			'Legacy raw-header keys must continue to resolve (backward compat)');
	}

	/** @test */
	public function sanitized_key_wins_over_raw_when_both_present(): void
	{
		// Defensive: if both keys are submitted, the sanitized one is authoritative.
		$columns = ['foo[bar]'];
		$col_map = [
			'foo_bar_'  => 'title',
			'foo[bar]'  => '__skip__',
		];

		$result = $this->applyColumnMap($columns, $col_map);

		$this->assertSame('title', $result[0]);
	}
}
