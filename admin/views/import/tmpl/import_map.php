<?php
/**
 * FLEXIcontent Import — Step 2: Field Mapping
 *
 * @package     FLEXIcontent
 * @license     GNU/GPL v2
 */

defined('_JEXEC') or die('Restricted access');

/** @var FlexicontentViewImport $this */

$preview   = $this->preview;   // array from session: columns, rows, type_id, config…
$fc_fields = $this->fc_fields; // array of stdClass{id, name, label, field_type}
$type_name = $this->type_name;

$columns      = $preview['columns'];
$preview_rows = $preview['rows'] ?? [];

// Core properties always available for mapping
$core_props = [
	'title'         => 'Title',
	'text'          => 'Description / Intro text',
	'alias'         => 'Alias (URL slug)',
	'catid'         => 'Primary category (ID)',
	'cid'           => 'Secondary categories (comma-sep IDs)',
	'state'         => 'State (0=unpublished, 1=published)',
	'access'        => 'Access level (ID)',
	'language'      => 'Language code (e.g. en-GB)',
	'created'       => 'Created date',
	'created_by'    => 'Author (user ID)',
	'modified'      => 'Modified date',
	'modified_by'   => 'Modifier (user ID)',
	'publish_up'    => 'Publish-up date',
	'publish_down'  => 'Publish-down date',
	'metadesc'      => 'META description',
	'metakey'       => 'META keywords',
	'custom_ititle' => 'Custom <title> tag',
	'tags_names'    => 'Tags (comma-sep names)',
	'tags_raw'      => 'Tags (comma-sep IDs)',
	'id'            => 'Item ID (for update mode)',
];

// Build JS array of all FC field names for auto-map
$js_field_names = json_encode(array_merge(
	array_keys($core_props),
	array_map(fn($f) => $f->name, $fc_fields)
));

// Load Alpine.js
$this->document->addScript(
	'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js',
	['version' => 'auto'],
	['defer' => true]
);

$token = \Joomla\CMS\HTML\HTMLHelper::_('form.token');
?>

<div id="flexicontent" class="flexicontent">

<form action="index.php" method="post" name="adminForm" id="adminForm"
      enctype="multipart/form-data"
      x-data="fcMapper()"
      x-init="init()">

	<!-- ── Step indicator ───────────────────────────────────────────── -->
	<div class="alert alert-info d-flex align-items-center gap-2 mb-3">
		<span class="fs-5">🔀</span>
		<div>
			<strong>Step 2 of 3 — Map fields</strong>
			&nbsp;|&nbsp; Content type: <strong><?php echo htmlspecialchars($type_name); ?></strong>
			&nbsp;|&nbsp; <?php echo count($columns); ?> columns detected
		</div>
	</div>

	<!-- ── CSV preview table ─────────────────────────────────────────── -->
	<?php if (!empty($preview_rows)) : ?>
	<div class="card mb-3">
		<div class="card-header fw-semibold">CSV Preview (first <?php echo count($preview_rows); ?> rows)</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-sm table-bordered table-striped mb-0" style="font-size:0.8rem;">
					<thead class="table-dark">
						<tr>
							<?php foreach ($columns as $col) : ?>
							<th><?php echo htmlspecialchars($col); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($preview_rows as $row) : ?>
						<tr>
							<?php foreach ($columns as $ci => $col) : ?>
							<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
							    title="<?php echo htmlspecialchars($row[$ci] ?? ''); ?>">
								<?php echo htmlspecialchars(mb_substr($row[$ci] ?? '', 0, 60)); ?>
							</td>
							<?php endforeach; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- ── Field mapping table ──────────────────────────────────────── -->
	<div class="card mb-3">
		<div class="card-header d-flex justify-content-between align-items-center">
			<span class="fw-semibold">Map CSV Columns → FLEXIcontent Fields</span>
			<button type="button" class="btn btn-sm btn-outline-secondary" @click="autoMap()">
				✨ Auto-map matching names
			</button>
		</div>
		<div class="card-body p-0">
			<table class="table table-bordered table-hover mb-0">
				<thead class="table-light">
					<tr>
						<th style="width:28%">CSV Column</th>
						<th style="width:30%">Sample value</th>
						<th style="width:42%">Map to FLEXIcontent field</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($columns as $ci => $col) :
						$sample = '';
						foreach ($preview_rows as $r) {
							if (!empty($r[$ci])) { $sample = mb_substr($r[$ci], 0, 80); break; }
						}
					?>
					<tr>
						<td class="align-middle">
							<code><?php echo htmlspecialchars($col); ?></code>
						</td>
						<td class="align-middle text-muted" style="font-size:0.8rem;">
							<?php echo htmlspecialchars($sample); ?>
						</td>
						<td class="align-middle">
							<select name="col_map[<?php echo htmlspecialchars($col); ?>]"
							        id="colmap_<?php echo $ci; ?>"
							        class="form-select form-select-sm"
							        x-ref="sel_<?php echo $ci; ?>">
								<option value="__skip__">— Skip this column —</option>

								<optgroup label="Core Properties">
									<?php foreach ($core_props as $cprop => $clabel) : ?>
									<option value="<?php echo $cprop; ?>"
									        <?php if (strtolower($col) === $cprop) echo 'selected'; ?>>
										<?php echo htmlspecialchars($clabel); ?> [<?php echo $cprop; ?>]
									</option>
									<?php endforeach; ?>
								</optgroup>

								<?php if (!empty($fc_fields)) : ?>
								<optgroup label="Custom Fields — <?php echo htmlspecialchars($type_name); ?>">
									<?php foreach ($fc_fields as $f) : ?>
									<option value="<?php echo htmlspecialchars($f->name); ?>"
									        <?php if (strtolower($col) === strtolower($f->name)) echo 'selected'; ?>>
										<?php echo htmlspecialchars($f->label ?: $f->name); ?>
										[<?php echo htmlspecialchars($f->name); ?>]
										<small>(<?php echo htmlspecialchars($f->field_type); ?>)</small>
									</option>
									<?php endforeach; ?>
								</optgroup>
								<?php endif; ?>
							</select>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- ── Carry all Step-1 config as hidden fields ────────────────── -->
	<?php
	$carry_fields = [
		'type_id', 'id_col', 'maincat', 'maincat_col', 'seccats_col',
		'language', 'state', 'access', 'tags_col',
		'created_by_col', 'modified_by_col', 'metadesc_col', 'metakey_col',
		'custom_ititle_col', 'modified_col', 'created_col',
		'publish_up_col', 'publish_down_col',
		'field_separator', 'enclosure_char', 'record_separator',
		'mval_separator', 'mprop_separator',
		'items_per_step', 'debug_records', 'media_folder', 'docs_folder',
	];

	foreach ($carry_fields as $cf) :
		$val = $preview[$cf] ?? '';
		if (!is_array($val)) :
	?>
	<input type="hidden" name="<?php echo $cf; ?>" value="<?php echo htmlspecialchars((string) $val); ?>" />
	<?php
		endif;
	endforeach;

	// seccats is an array
	if (!empty($preview['seccats']) && is_array($preview['seccats'])) :
		foreach ($preview['seccats'] as $sc) : ?>
	<input type="hidden" name="seccats[]" value="<?php echo (int) $sc; ?>" />
	<?php
		endforeach;
	endif;
	?>
	<input type="hidden" name="ignore_unused_cols" value="1" />

	<!-- ── Buttons ───────────────────────────────────────────────────── -->
	<div class="d-flex gap-2 mb-4">
		<a href="index.php?option=com_flexicontent&view=import"
		   class="btn btn-outline-secondary">
			← Back to Step 1
		</a>

		<button type="submit" name="task" value="mapinitcsv"
		        class="btn btn-success">
			→ Prepare Import
		</button>

		<button type="submit" name="task" value="testcsv"
		        class="btn btn-outline-primary ms-2">
			🔍 Test format (no import)
		</button>
	</div>

	<!-- Common hidden fields -->
	<input type="hidden" name="option"     value="com_flexicontent" />
	<input type="hidden" name="controller" value="import" />
	<input type="hidden" name="view"       value="import" />
	<input type="hidden" name="fcform"     value="1" />
	<?php echo $token; ?>

</form>
</div><!-- #flexicontent -->

<script>
function fcMapper() {
	return {
		knownFields: <?php echo $js_field_names; ?>,

		init() {
			// Auto-apply selections already set by PHP (attribute selected)
			// Also run auto-map by default on page load
			this.autoMap();
		},

		autoMap() {
			// For each select, try to find a matching FC field name (case-insensitive)
			const selects = document.querySelectorAll('[name^="col_map["]');
			selects.forEach(sel => {
				// Get CSV column name from the name attribute: col_map[COLNAME]
				const match = sel.name.match(/^col_map\[(.+)\]$/);
				if (!match) return;
				const csvCol = match[1].toLowerCase().trim();

				// Check if already set to something other than __skip__
				if (sel.value && sel.value !== '__skip__') return;

				// Try exact match first
				const exact = this.knownFields.find(f => f.toLowerCase() === csvCol);
				if (exact) {
					sel.value = exact;
					return;
				}

				// Try partial / common aliases
				const aliases = {
					'description': 'text',
					'intro':       'text',
					'body':        'text',
					'content':     'text',
					'cat':         'catid',
					'category':    'catid',
					'category id': 'catid',
					'category_id': 'catid',
					'lang':        'language',
					'slug':        'alias',
					'url_alias':   'alias',
					'author':      'created_by',
					'created_by_alias': 'created_by',
				};

				const aliasMatch = aliases[csvCol];
				if (aliasMatch) {
					sel.value = aliasMatch;
				}
			});
		},
	};
}
</script>
