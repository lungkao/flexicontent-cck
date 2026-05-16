<?php
/**
 * FLEXIcontent Import — Single-Page Wizard
 *
 * UX flow (no page reloads):
 *   1. Select CSV file  → FileReader parses columns + preview rows client-side
 *   2. Select type      → Alpine fetches FC fields via AJAX (getfieldsajax task)
 *   3. Mapping table    → appears automatically; auto-mapped by name
 *   4. Click "Start Import" → submits file + col_map + config → mapinitcsv
 *
 * Legacy "Preview & Map" flow (Step-2 layout) is preserved for back-compat.
 *
 * @package     FLEXIcontent
 * @license     GNU/GPL v2
 * @since       4.0
 */

defined('_JEXEC') or die('Restricted access');

/** @var FlexicontentViewImport $this */

$fv     = $this->formvals;
$lists  = $this->lists;
$token  = \Joomla\CMS\HTML\HTMLHelper::_('form.token');

// Token name for AJAX calls (Alpine fetches FC fields)
$fc_token_name = \Joomla\CMS\Session\Session::getFormToken();

// Load Alpine.js
$this->document->addScript(
	'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js',
	['version' => 'auto'],
	['defer' => true]
);

if (FLEXI_J40GE) \Joomla\CMS\Toolbar\ToolbarHelper::inlinehelp();
?>

<style>
/* ── Import Wizard styles ─────────────────────────────────────────── */
[x-cloak] { display: none !important; }

.fc-import-wizard .mapping-row-mapped   { background: rgba(25,135,84,.04); }
.fc-import-wizard .mapping-row-skipped  { opacity: .55; }
.fc-import-wizard .col-dot {
	display: inline-block; width: 10px; height: 10px;
	border-radius: 50%; margin-right: 6px; flex-shrink: 0;
}
.fc-import-wizard .col-dot-mapped   { background: #198754; }
.fc-import-wizard .col-dot-skipped  { background: #adb5bd; }

.fc-import-wizard .step-badge {
	display: inline-flex; align-items: center; justify-content: center;
	width: 28px; height: 28px; border-radius: 50%;
	background: #0d6efd; color: #fff; font-size: .8rem; font-weight: 700;
	flex-shrink: 0;
}
.fc-import-wizard .step-badge.done  { background: #198754; }
.fc-import-wizard .step-badge.idle  { background: #dee2e6; color: #6c757d; }

.fc-import-wizard .mapping-table th { font-size: .8rem; white-space: nowrap; }
.fc-import-wizard .mapping-table td { vertical-align: middle; }
.fc-import-wizard .sample-cell {
	font-size: .75rem; color: #6c757d;
	max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
</style>

<div id="flexicontent" class="flexicontent fc-import-wizard"
     x-data="csvImporter()"
     x-cloak>

<form action="index.php" method="post" name="adminForm" id="adminForm"
      enctype="multipart/form-data"
      @submit.prevent="handleSubmit($event)">


	<!-- ════════════════════════════════════════════════════════════════
	     HEADER: Step progress indicator
	     ════════════════════════════════════════════════════════════════ -->
	<div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded border">

		<!-- Step 1 -->
		<span :class="csvColumns.length ? 'step-badge done' : 'step-badge'">
			<template x-if="csvColumns.length"><span>✓</span></template>
			<template x-if="!csvColumns.length"><span>1</span></template>
		</span>
		<span class="fw-semibold" :class="csvColumns.length ? 'text-success' : ''">
			Select CSV File
			<small x-show="csvColumns.length" class="d-block fw-normal text-success"
			       x-text="csvColumns.length + ' columns'"></small>
		</span>

		<span class="text-muted mx-1">→</span>

		<!-- Step 2 -->
		<span :class="typeId && fcLoaded ? 'step-badge done' : (typeId ? 'step-badge' : 'step-badge idle')">
			<template x-if="typeId && fcLoaded"><span>✓</span></template>
			<template x-if="!(typeId && fcLoaded)"><span>2</span></template>
		</span>
		<span class="fw-semibold" :class="typeId && fcLoaded ? 'text-success' : ''">
			Content Type
			<small x-show="typeId && fcLoaded" class="d-block fw-normal text-success"
			       x-text="fcFields.length + ' custom fields'"></small>
			<small x-show="fcLoading" class="d-block fw-normal text-primary">Loading…</small>
		</span>

		<span class="text-muted mx-1">→</span>

		<!-- Step 3 -->
		<span :class="isReady ? 'step-badge done' : 'step-badge idle'">
			<template x-if="isReady"><span>✓</span></template>
			<template x-if="!isReady"><span>3</span></template>
		</span>
		<span class="fw-semibold" :class="isReady ? 'text-success' : 'text-muted'">
			Map Fields
			<small x-show="isReady" class="d-block fw-normal text-success"
			       x-text="mappedCount + '/' + csvColumns.length + ' mapped'"></small>
		</span>

		<span class="text-muted mx-1">→</span>
		<span class="step-badge idle">4</span>
		<span class="fw-semibold text-muted">Import</span>

	</div>


	<!-- ════════════════════════════════════════════════════════════════
	     SECTION 1: File + Type (always visible)
	     ════════════════════════════════════════════════════════════════ -->
	<div class="row g-3 mb-3">

		<!-- ── 1a. CSV File ─────────────────────────────────────────── -->
		<div class="col-md-6">
			<div class="card h-100" :class="csvColumns.length ? 'border-success' : 'border-primary'">
				<div class="card-header fw-semibold"
				     :class="csvColumns.length ? 'bg-success text-white' : 'bg-primary text-white'">
					📁 Step 1 — Select CSV File
				</div>
				<div class="card-body">
					<label for="csvfile" class="form-label fw-semibold">
						CSV File <span class="text-danger" aria-hidden="true">*</span>
					</label>
					<input type="file" name="csvfile" id="csvfile"
					       class="form-control mb-2" accept=".csv,.txt"
					       required aria-required="true"
					       aria-describedby="csvfile-hint csvfile-error"
					       @change="onFileChange($event)" />

					<!-- Column list preview -->
					<div x-show="csvColumns.length > 0" class="mt-2">
						<div class="text-success fw-semibold mb-1">
							✓ <span x-text="csvColumns.length"></span> columns detected
						</div>
						<div class="d-flex flex-wrap gap-1">
							<template x-for="col in csvColumns.slice(0,12)" :key="col.name">
								<span class="badge bg-secondary text-truncate" style="max-width:120px;"
								      :title="col.name" x-text="col.name"></span>
							</template>
							<span x-show="csvColumns.length > 12" class="badge bg-light text-muted"
							      x-text="'+ ' + (csvColumns.length - 12) + ' more'"></span>
						</div>
					</div>

					<!-- Error -->
					<div id="csvfile-error" x-show="fileError"
					     role="alert" aria-live="assertive"
					     class="alert alert-danger mt-2 mb-0 py-1 px-2 small"
					     x-text="fileError"></div>

					<!-- Hint -->
					<div id="csvfile-hint" x-show="!csvColumns.length && !fileError" class="form-text">
						Standard CSV (comma/quote) or FLEXIcontent format supported.
					</div>
				</div>
			</div>
		</div>

		<!-- ── 1b. Content Type ─────────────────────────────────────── -->
		<div class="col-md-6">
			<div class="card h-100" :class="typeId && fcLoaded ? 'border-success' : 'border-primary'">
				<div class="card-header fw-semibold"
				     :class="typeId && fcLoaded ? 'bg-success text-white' : 'bg-primary text-white'">
					📋 Step 2 — Select Content Type
				</div>
				<div class="card-body">
					<label for="type_id" class="form-label fw-semibold">
						Content Type <span class="text-danger" aria-hidden="true">*</span>
					</label>
					<div id="type_id-wrapper">
						<?php echo $lists['type_id']; ?>
					</div>

					<!-- AJAX status (announced to assistive tech) -->
					<div aria-live="polite" :aria-busy="fcLoading ? 'true' : 'false'">
						<!-- Loading indicator -->
						<div x-show="fcLoading" class="mt-2 text-primary small">
							<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
							Loading fields…
						</div>

						<!-- FC fields summary -->
						<div x-show="typeId && fcLoaded && !fcLoading" class="mt-2 text-success small">
							<span aria-hidden="true">✓</span>
							<span x-text="fcFields.length"></span> custom fields available
						</div>
					</div>

					<div class="form-text mt-1">
						Used to identify custom fields and assign type to new items.
					</div>
				</div>
			</div>
		</div>

	</div>


	<!-- ════════════════════════════════════════════════════════════════
	     SECTION 2a: Placeholder — shown when not ready yet
	     ════════════════════════════════════════════════════════════════ -->
	<div x-show="!isReady" class="alert alert-secondary d-flex align-items-center gap-2 mb-3">
		<span class="fs-4">🗺️</span>
		<div>
			<template x-if="!csvColumns.length && !typeId">
				<span>Select a <strong>CSV file</strong> and <strong>content type</strong> above to configure field mapping.</span>
			</template>
			<template x-if="!csvColumns.length && typeId">
				<span>✓ Content type selected. Now choose a <strong>CSV file</strong> to see the mapping table.</span>
			</template>
			<template x-if="csvColumns.length && !typeId">
				<span>✓ <strong x-text="csvColumns.length"></strong> columns detected. Now select a <strong>content type</strong>.</span>
			</template>
		</div>
	</div>


	<!-- ════════════════════════════════════════════════════════════════
	     SECTION 2b: Field Mapping Table — shown when both ready
	     ════════════════════════════════════════════════════════════════ -->
	<div x-show="isReady" class="card mb-3 border-primary">
		<!-- SR-only live region: announces mapping progress -->
		<div class="visually-hidden" aria-live="polite"
		     x-text="isReady ? (mappedCount + ' of ' + csvColumns.length + ' columns mapped') : ''"></div>

		<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
			<span class="fw-semibold">
				🔗 Step 3 — Map Fields
				(<span x-text="mappedCount"></span>/<span x-text="csvColumns.length"></span> mapped)
			</span>
			<button type="button" class="btn btn-sm btn-light" @click="autoMap(true)">
				✨ Re-auto-map
			</button>
		</div>

		<div class="card-body p-0">
			<table class="table table-bordered table-hover mapping-table mb-0">
				<thead class="table-light">
					<tr>
						<th style="width:28%">CSV Column</th>
						<th style="width:22%">Sample Data</th>
						<th style="width:50%">→ FLEXIcontent Field</th>
					</tr>
				</thead>
				<tbody>
					<template x-for="(col, idx) in csvColumns" :key="col.key">
						<tr :class="(mappings[col.key] && mappings[col.key] !== '__skip__')
							? 'mapping-row-mapped' : 'mapping-row-skipped'">

							<!-- Column name -->
							<td>
								<div class="d-flex align-items-center">
									<span class="col-dot"
									      :class="(mappings[col.key] && mappings[col.key] !== '__skip__')
									              ? 'col-dot-mapped' : 'col-dot-skipped'"></span>
									<code class="text-truncate" style="max-width:200px;" x-text="col.name"></code>
								</div>
							</td>

							<!-- Sample value (Alpine :title escapes attribute value; safe vs injection) -->
							<td class="sample-cell" :title="col.sample" x-text="col.sample"></td>

							<!-- FC Field selector -->
							<td>
								<select :name="'col_map[' + col.key + ']'"
								        :aria-label="'Map CSV column ' + col.name + ' to FLEXIcontent field'"
								        x-model="mappings[col.key]"
								        class="form-select form-select-sm">

									<option value="__skip__">— Skip this column —</option>

									<template x-for="opt in fieldOptions" :key="opt.value">
										<option :value="opt.value"
										        :disabled="!!opt.disabled"
										        :class="opt.disabled ? 'text-muted fw-semibold' : ''"
										        x-text="opt.label"></option>
									</template>

								</select>
							</td>

						</tr>
					</template>
				</tbody>
			</table>
		</div>

		<div class="card-footer text-muted small">
			<span x-text="mappedCount"></span> column(s) will be imported ·
			<span x-text="csvColumns.length - mappedCount"></span> will be skipped.
			Columns mapped to <strong>catid</strong> automatically enable "use category column" mode.
		</div>
	</div>


	<!-- ════════════════════════════════════════════════════════════════
	     SECTION 3: Import Settings
	     ════════════════════════════════════════════════════════════════ -->
	<div class="card mb-3">
		<div class="card-header fw-semibold">⚙️ Import Settings</div>
		<div class="card-body">
			<div class="row g-3">

				<!-- Main Category -->
				<div class="col-md-6">
					<label class="form-label fw-semibold">
						Main Category
						<span class="text-danger" x-show="!hasCatidMapping">*</span>
					</label>
					<?php echo $lists['maincat']; ?>
					<!-- Dynamic maincat_col: auto=1 when catid is mapped -->
					<input type="hidden" name="maincat_col"
					       :value="hasCatidMapping ? 1 : 0" />
					<div x-show="hasCatidMapping" class="form-text text-success">
						✓ Will read category from <code>catid</code> column
					</div>
					<div x-show="!hasCatidMapping" class="form-text">
						Required when no <code>catid</code> column is mapped.
					</div>
				</div>

				<!-- Item ID handling -->
				<div class="col-md-6">
					<label class="form-label fw-semibold">Item ID handling</label>
					<?php $dv = $fv['id_col']; ?>
					<div class="border rounded p-2">
						<div class="form-check">
							<input class="form-check-input" type="radio" name="id_col"
							       id="id_col0" value="0" <?php echo $dv==0 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="id_col0">Auto-assign — create new items only</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="id_col"
							       id="id_col2" value="2" <?php echo $dv==2 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="id_col2">Create <em>or</em> update (needs <code>id</code> column)</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="id_col"
							       id="id_col3" value="3" <?php echo $dv==3 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="id_col3">Update existing only</label>
						</div>
					</div>
				</div>

				<!-- State -->
				<div class="col-md-4">
					<label class="form-label fw-semibold">Default State</label>
					<?php echo $lists['states']; ?>
				</div>

				<!-- Access -->
				<div class="col-md-4">
					<label class="form-label fw-semibold">Access Level</label>
					<?php echo $lists['access']; ?>
				</div>

				<!-- Language -->
				<div class="col-md-4">
					<label class="form-label fw-semibold">Language</label>
					<?php echo $lists['languages']; ?>
				</div>

				<!-- Items per batch -->
				<div class="col-md-4">
					<label for="items_per_step" class="form-label fw-semibold">Items per batch</label>
					<input type="number" name="items_per_step" id="items_per_step"
					       min="1" max="50"
					       value="<?php echo (int) $fv['items_per_step']; ?>"
					       class="form-control" />
					<div class="form-text">Max 50. Lower = safer for large files.</div>
				</div>

			</div><!-- /row -->

			<!-- ── Advanced (collapsed) ──────────────────────────────────── -->
			<details class="mt-3">
				<summary class="fw-semibold text-secondary" style="cursor:pointer;">
					▸ Advanced: Secondary Categories, Tags, Dates, CSV Format…
				</summary>
				<div class="row g-3 mt-2">

					<!-- Secondary cats -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">Secondary Categories</label>
						<?php echo $lists['seccats']; ?>
						<div class="form-check mt-1">
							<input class="form-check-input" type="checkbox"
							       name="seccats_col" id="seccats_col" value="1"
							       <?php echo $fv['seccats_col'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="seccats_col">
								Override with <code>cid</code> column (comma-separated IDs)
							</label>
						</div>
					</div>

					<!-- Tags -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">Tags</label>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="tags_col" value="0"
							       id="tags_col0" <?php echo $fv['tags_col']==0 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="tags_col0">Do not import tags</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="tags_col" value="1"
							       id="tags_col1" <?php echo $fv['tags_col']==1 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="tags_col1">Use <code>tags_names</code> column</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="tags_col" value="2"
							       id="tags_col2" <?php echo $fv['tags_col']==2 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="tags_col2">Use <code>tags_raw</code> column (IDs)</label>
						</div>
					</div>

					<!-- Author -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">Author</label>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="created_by_col" value="0"
							       id="created_by_col0" <?php echo $fv['created_by_col']==0 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="created_by_col0">Current logged-in user</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="radio" name="created_by_col" value="1"
							       id="created_by_col1" <?php echo $fv['created_by_col']==1 ? 'checked' : ''; ?> />
							<label class="form-check-label" for="created_by_col1">From <code>created_by</code> column</label>
						</div>
					</div>

					<!-- Dates -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">Date columns</label>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="created_col" value="1"
							       id="created_col" <?php echo $fv['created_col'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="created_col">Use <code>created</code> column</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="publish_up_col" value="1"
							       id="publish_up_col" <?php echo $fv['publish_up_col'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="publish_up_col">Use <code>publish_up</code> column</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="publish_down_col" value="1"
							       id="publish_down_col" <?php echo $fv['publish_down_col'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="publish_down_col">Use <code>publish_down</code> column</label>
						</div>
					</div>

					<!-- META -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">META columns</label>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="metadesc_col" value="1"
							       id="metadesc_col" <?php echo $fv['metadesc_col'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="metadesc_col">Use <code>metadesc</code> column</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="metakey_col" value="1"
							       id="metakey_col" <?php echo $fv['metakey_col'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="metakey_col">Use <code>metakey</code> column</label>
						</div>
					</div>

					<!-- Media folders -->
					<div class="col-md-6">
						<label for="media_folder" class="form-label fw-semibold">Media / image folder</label>
						<input type="text" name="media_folder" id="media_folder"
						       value="<?php echo htmlspecialchars($this->model->getState('media_folder')); ?>"
						       class="form-control" />
						<div class="form-text">Relative to Joomla root, e.g. <code>tmp/fcimport_media</code></div>
					</div>

					<!-- ── CSV Format ──────────────────────────────────────────── -->
					<div class="col-12">
						<hr class="my-2" />
						<h6 class="fw-semibold">CSV Format Settings</h6>
						<div class="alert alert-info py-1 px-2 mb-2" style="font-size:.8rem;">
							Standard: separator <code>,</code> · enclosure <code>"</code> ·
							record <code>\n</code> · multi-value <code>%%</code> · multi-property <code>!!</code>
						</div>
					</div>

					<div class="col-md-4 col-sm-6">
						<label for="field_separator" class="form-label">Field separator</label>
						<input type="text" name="field_separator" id="field_separator"
						       value="<?php echo htmlspecialchars($fv['field_separator']); ?>"
						       class="form-control required" />
					</div>

					<div class="col-md-4 col-sm-6">
						<label for="enclosure_char" class="form-label">Enclosure char</label>
						<input type="text" name="enclosure_char" id="enclosure_char"
						       value="<?php echo htmlspecialchars($fv['enclosure_char']); ?>"
						       class="form-control" />
					</div>

					<div class="col-md-4 col-sm-6">
						<label for="record_separator" class="form-label">Record separator</label>
						<input type="text" name="record_separator" id="record_separator"
						       value="<?php echo htmlspecialchars($fv['record_separator']); ?>"
						       class="form-control required" />
					</div>

					<div class="col-md-4 col-sm-6">
						<label for="mval_separator" class="form-label">Multi-value sep.</label>
						<input type="text" name="mval_separator" id="mval_separator"
						       value="<?php echo htmlspecialchars($fv['mval_separator']); ?>"
						       class="form-control required" />
					</div>

					<div class="col-md-4 col-sm-6">
						<label for="mprop_separator" class="form-label">Multi-property sep.</label>
						<input type="text" name="mprop_separator" id="mprop_separator"
						       value="<?php echo htmlspecialchars($fv['mprop_separator']); ?>"
						       class="form-control required" />
					</div>

					<div class="col-md-4 col-sm-6">
						<label for="debug_records" class="form-label">Preview rows (test)</label>
						<input type="number" name="debug_records" id="debug_records"
						       value="<?php echo (int) $fv['debug_records']; ?>"
						       class="form-control" min="0" />
					</div>

					<!-- Ignore unused -->
					<div class="col-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox"
							       name="ignore_unused_cols" id="ignore_unused_cols" value="1"
							       <?php echo $fv['ignore_unused_cols'] ? 'checked' : ''; ?> />
							<label class="form-check-label" for="ignore_unused_cols">
								Ignore unused / unrecognised columns (always enabled when using Field Mapping)
							</label>
						</div>
					</div>

					<!-- File field skip -->
					<?php if (!empty($this->file_fields)) : ?>
					<div class="col-12">
						<label class="form-label fw-semibold">Skip file existence check:</label>
						<?php foreach ($this->file_fields as $i => $ff) : ?>
						<div class="form-check">
							<input class="form-check-input" type="checkbox"
							       name="skip_file_field[]"
							       id="skip_ff_<?php echo $i; ?>"
							       value="<?php echo htmlspecialchars($ff->name); ?>" />
							<label class="form-check-label" for="skip_ff_<?php echo $i; ?>">
								<?php echo htmlspecialchars($ff->label); ?>
								<small class="text-muted">[<?php echo htmlspecialchars($ff->name); ?>]</small>
							</label>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

				</div><!-- /advanced row -->
			</details>

		</div><!-- /card-body -->
	</div><!-- /card settings -->


	<!-- ════════════════════════════════════════════════════════════════
	     ACTION BUTTONS
	     ════════════════════════════════════════════════════════════════ -->

	<!-- Validation error live region (announced + receives focus shift via setFormError) -->
	<div role="alert" aria-live="assertive" x-show="formError" x-cloak
	     class="alert alert-danger mb-2" x-text="formError"></div>

	<div class="d-flex flex-wrap gap-2 mb-4">

		<!-- Primary: use mapping wizard -->
		<button type="submit" name="task" value="mapinitcsv"
		        class="btn btn-primary btn-lg">
			📤 Upload &amp; Start Import
		</button>

		<div class="vr d-none d-md-block mx-1"></div>

		<!-- Secondary: skip mapping (header must match FC field names exactly) -->
		<button type="submit" name="task" value="initcsv"
		        class="btn btn-outline-secondary">
			⚡ Quick Import
			<small class="d-block" style="font-size:.7rem;">(headers must be exact FC field names)</small>
		</button>

		<button type="submit" name="task" value="testcsv"
		        class="btn btn-outline-info">
			🔍 Test Format
		</button>

		<!-- Legacy Step-2 flow (kept for power users) -->
		<button type="submit" name="task" value="previewcsv"
		        class="btn btn-outline-secondary ms-auto">
			🔀 Preview &amp; Map (Step-2 mode)
		</button>

	</div>


	<!-- ── Hidden common fields ──────────────────────────────────────── -->
	<input type="hidden" name="option"     value="com_flexicontent" />
	<input type="hidden" name="controller" value="import" />
	<input type="hidden" name="view"       value="import" />
	<input type="hidden" name="task"       value="" />
	<input type="hidden" name="fcform"     value="1" />
	<?php echo $token; ?>

</form>
</div><!-- #flexicontent .fc-import-wizard -->


<script>
/* Token name passed from PHP for AJAX calls */
const fcToken = <?php echo json_encode($fc_token_name); ?>;

function csvImporter() {
	return {

		/* ── State ─────────────────────────────────────────────────── */
		csvColumns : [],   // [{name:string, sample:string}]
		fcFields   : [],   // [{id, name, label, field_type}]
		mappings   : {},   // {csvColName → fcFieldName|'__skip__'}
		typeId     : 0,
		fcLoading  : false,
		fcLoaded   : false,
		fileError  : '',
		formError  : '',

		/* ── Static data ────────────────────────────────────────────── */
		coreProps: [
			{name:'title',        label:'Title'},
			{name:'text',         label:'Description / Intro text'},
			{name:'alias',        label:'Alias (URL slug)'},
			{name:'catid',        label:'Primary Category (ID)'},
			{name:'cid',          label:'Secondary Categories (comma IDs)'},
			{name:'state',        label:'State  (0/1)'},
			{name:'access',       label:'Access Level (ID)'},
			{name:'language',     label:'Language code (e.g. en-GB)'},
			{name:'created',      label:'Created date'},
			{name:'created_by',   label:'Author (user ID)'},
			{name:'modified',     label:'Modified date'},
			{name:'modified_by',  label:'Modifier (user ID)'},
			{name:'publish_up',   label:'Publish-up date'},
			{name:'publish_down', label:'Publish-down date'},
			{name:'metadesc',     label:'META description'},
			{name:'metakey',      label:'META keywords'},
			{name:'custom_ititle',label:'Custom <title> tag'},
			{name:'tags_names',   label:'Tags (comma-separated names)'},
			{name:'tags_raw',     label:'Tags (comma-separated IDs)'},
			{name:'id',           label:'Item ID (update mode)'},
		],

		aliases: {
			'description'  : 'text',
			'body'         : 'text',
			'content'      : 'text',
			'intro'        : 'text',
			'intro text'   : 'text',
			'category'     : 'catid',
			'category_id'  : 'catid',
			'category id'  : 'catid',
			'cat'          : 'catid',
			'cat_id'       : 'catid',
			'lang'         : 'language',
			'lang_code'    : 'language',
			'slug'         : 'alias',
			'url_alias'    : 'alias',
			'author'       : 'created_by',
			'tags'         : 'tags_names',
			'tag_names'    : 'tags_names',
			'publish_state': 'state',
		},

		/* ── Computed ───────────────────────────────────────────────── */

		get fieldOptions() {
			const opts = [];
			opts.push({value:'__sep_core__',   label:'── Core Properties ──',   disabled:true});
			this.coreProps.forEach(f =>
				opts.push({value:f.name, label: f.label + '  [' + f.name + ']'})
			);
			if (this.fcFields.length) {
				opts.push({value:'__sep_custom__', label:'── Custom Fields ──', disabled:true});
				this.fcFields.forEach(f =>
					opts.push({value:f.name, label: (f.label || f.name) + '  [' + f.name + ']'})
				);
			}
			return opts;
		},

		get isReady() {
			return this.csvColumns.length > 0 && this.typeId > 0 && this.fcLoaded;
		},

		get mappedCount() {
			return Object.values(this.mappings).filter(v => v && v !== '__skip__').length;
		},

		get hasCatidMapping() {
			return Object.values(this.mappings).includes('catid');
		},

		/* ── File handling ──────────────────────────────────────────── */

		async onFileChange(event) {
			const file = event.target.files[0];
			if (!file) { this.csvColumns = []; this.mappings = {}; return; }
			this.fileError = '';
			await this.parseCsv(file);
			if (this.isReady) this.autoMap(false);
		},

		parseCsv(file) {
			return new Promise(resolve => {
				const reader = new FileReader();

				reader.onload = (e) => {
					try {
						const text  = e.target.result;
						const sep   = document.getElementById('field_separator')?.value || ',';
						const lines = text.split(/\r?\n/);

						if (!lines.length) { resolve(); return; }

						const header   = this.parseCsvLine(lines[0], sep);
						const dataRows = lines.slice(1, 6)
							.filter(l => l.trim())
							.map(l => this.parseCsvLine(l, sep));

						this.csvColumns = header.map((raw, i) => {
							// Strip BOM + control chars (mirrors PHP logic)
							const name   = raw.trim()
								.replace(/^﻿/, '')
								.replace(/[\x00-\x1F\x7F]/g, '');
							// Safe key for HTML name attribute + Alpine reactive keys.
							// PHP server mirrors this regex in mapinitcsv lookup (col_map[key]).
							const safe   = name.replace(/[^A-Za-z0-9_]/g, '_');
							const key    = (safe !== '' ? safe : ('col_' + i)).slice(0, 64);
							const sample = dataRows
								.map(r => (r[i] || '').trim())
								.find(v => v) || '';
							return {name, key, sample};
						}).filter(c => c.name);

					} catch (err) {
						this.fileError = 'Could not parse CSV: ' + err.message;
					}
					resolve();
				};

				reader.onerror = () => { this.fileError = 'Could not read file'; resolve(); };
				reader.readAsText(file, 'UTF-8');
			});
		},

		parseCsvLine(line, sep) {
			const result = [];
			let cur = '', inQ = false;
			for (let i = 0; i < line.length; i++) {
				const ch = line[i];
				if (ch === '"') {
					if (inQ && line[i+1] === '"') { cur += '"'; i++; }  // escaped quote
					else inQ = !inQ;
				} else if (ch === sep && !inQ) {
					result.push(cur); cur = '';
				} else {
					cur += ch;
				}
			}
			result.push(cur);
			return result;
		},

		/* ── Type change → AJAX load FC fields ─────────────────────── */

		async onTypeChange(event) {
			this.typeId  = parseInt(event.target.value) || 0;
			this.fcFields  = [];
			this.fcLoaded  = false;
			if (!this.typeId) return;

			this.fcLoading = true;
			try {
				const url = 'index.php?option=com_flexicontent'
					+ '&controller=import&task=getfieldsajax'
					+ '&type_id=' + this.typeId
					+ '&' + encodeURIComponent(fcToken) + '=1';

				const res  = await fetch(url, {credentials: 'same-origin'});
				const data = await res.json();
				this.fcFields = data.fields || [];
				this.fcLoaded = true;
				if (this.isReady) this.autoMap(false);
			} catch (err) {
				console.error('[fcImport] Failed to load fields:', err);
				this.fcFields = [];
				this.fcLoaded = true;  // allow manual mapping
			} finally {
				this.fcLoading = false;
			}
		},

		/* ── Auto-map ───────────────────────────────────────────────── */

		autoMap(force = false) {
			const all = [...this.coreProps, ...this.fcFields];
			this.csvColumns.forEach(col => {
				// Don't override existing explicit mapping unless forced
				if (!force && this.mappings[col.key] && this.mappings[col.key] !== '__skip__') return;

				// Match by display name (raw header) — preserves alias behavior
				const lower = col.name.toLowerCase().trim();

				// 1. Exact name match (case-insensitive)
				const exact = all.find(f => f.name.toLowerCase() === lower);
				if (exact) { this.mappings[col.key] = exact.name; return; }

				// 2. Alias map
				const alias = this.aliases[lower];
				if (alias && all.find(f => f.name === alias)) {
					this.mappings[col.key] = alias; return;
				}

				// 3. Default: skip
				if (!this.mappings[col.key]) this.mappings[col.key] = '__skip__';
			});
		},

		/* ── Submit validation ──────────────────────────────────────── */

		handleSubmit(event) {
			// Form has @submit.prevent — native submission is blocked.
			// We must explicitly call form.submit() on success path.
			const task = event.submitter?.value || '';
			this.formError = '';

			// Only validate for mapped import tasks
			if (task === 'mapinitcsv' || task === 'initcsv') {

				// File required
				const fileInput = document.getElementById('csvfile');
				if (!fileInput || !fileInput.files.length) {
					this.setFormError('Please select a CSV file first.', 'csvfile');
					return;
				}

				// Type required
				if (!this.typeId) {
					this.setFormError('Please select a Content Type first.', 'type_id');
					return;
				}

				// If catid not mapped, maincat must be selected
				if (!this.hasCatidMapping) {
					const maincatEl = document.getElementById('maincat') ||
					                  document.querySelector('[name="maincat"]');
					if (maincatEl && !parseInt(maincatEl.value)) {
						this.setFormError(
							'Please select a Main Category, or map a CSV column to "catid".',
							maincatEl.id || 'maincat'
						);
						return;
					}
				}
			}

			// All good — trigger native submit (bypasses @submit.prevent)
			event.target.submit();
		},

		setFormError(msg, targetId) {
			this.formError = msg;
			this.$nextTick(() => {
				const el = targetId ? document.getElementById(targetId) : null;
				if (el && typeof el.focus === 'function') el.focus();
			});
		},

	}; /* end csvImporter */
}


/* ── Wire up Alpine to the Joomla select2 type dropdown ──────────── */
document.addEventListener('DOMContentLoaded', function() {
	// type_id may be rendered by Joomla as a select2 widget;
	// listen for the native change event which select2 also fires.
	const typeEl = document.getElementById('type_id');
	if (typeEl) {
		typeEl.addEventListener('change', function(e) {
			// Trigger Alpine's handler if alpine is running
			const alpineRoot = document.querySelector('[x-data]');
			if (alpineRoot && alpineRoot._x_dataStack) {
				const component = alpineRoot._x_dataStack[0];
				if (component && typeof component.onTypeChange === 'function') {
					component.onTypeChange(e);
				}
			}
		});
	}
});
</script>
