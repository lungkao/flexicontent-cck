<?php
/**
 * FLEXIcontent Import — Step 1: Upload & Configure
 *
 * Clean Bootstrap 5 form replacing the old 7-tab tabber layout.
 * Two actions:
 *   a) "Preview & Map Fields" → previewcsv → import_map.php (Step 2)
 *   b) "Quick Import"         → initcsv    (field names must match exactly)
 *
 * @package     FLEXIcontent
 * @license     GNU/GPL v2
 */

defined('_JEXEC') or die('Restricted access');

/** @var FlexicontentViewImport $this */

$fv     = $this->formvals;   // saved/default values from model state
$lists  = $this->lists;      // pre-built HTML select elements
$token  = \Joomla\CMS\HTML\HTMLHelper::_('form.token');
$link   = 'index.php?option=com_flexicontent&view=import';

if (FLEXI_J40GE) \Joomla\CMS\Toolbar\ToolbarHelper::inlinehelp();
?>

<div id="flexicontent" class="flexicontent">

<form action="index.php" method="post" name="adminForm" id="adminForm"
      class="form-validate"
      enctype="multipart/form-data">

	<!-- ════════════════════════════════════════════════════════════════
	     SECTION 1: File upload + Content Type
	     ════════════════════════════════════════════════════════════════ -->
	<div class="card mb-3 border-primary">
		<div class="card-header bg-primary text-white fw-semibold">
			📁 Step 1 — Upload CSV File &amp; Select Content Type
		</div>
		<div class="card-body">
			<div class="row g-3">

				<div class="col-md-6">
					<label for="csvfile" class="form-label fw-semibold">
						CSV File <span class="text-danger">*</span>
					</label>
					<input type="file" name="csvfile" id="csvfile"
					       class="form-control required" accept=".csv,.txt" />
					<div class="form-text">Supported: standard CSV (Excel), or FLEXIcontent custom format.</div>
				</div>

				<div class="col-md-6">
					<label for="type_id" class="form-label fw-semibold">
						Content Type <span class="text-danger">*</span>
					</label>
					<div id="type_id-wrapper">
						<?php echo $lists['type_id']; ?>
					</div>
					<div class="form-text">Used to identify fields and set type for new items.</div>
				</div>

			</div>
		</div>
	</div>

	<!-- ════════════════════════════════════════════════════════════════
	     SECTION 2: Import Mode & Defaults (accordion)
	     ════════════════════════════════════════════════════════════════ -->
	<div class="accordion mb-3" id="accImport">

		<!-- ── 2a. Import Mode ───────────────────────────────────────── -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="hMode">
				<button class="accordion-button" type="button"
				        data-bs-toggle="collapse" data-bs-target="#colMode"
				        aria-expanded="true" aria-controls="colMode">
					⚙️ Import Mode &amp; Defaults
				</button>
			</h2>
			<div id="colMode" class="accordion-collapse collapse show"
			     aria-labelledby="hMode">
				<div class="accordion-body">
					<div class="row g-3">

						<div class="col-md-6">
							<label class="form-label fw-semibold">Item ID handling</label>
							<?php $dv = $fv['id_col']; ?>
							<div class="mb-2">
								<div class="form-check">
									<input class="form-check-input" type="radio" name="id_col"
									       id="id_col0" value="0" <?php echo $dv==0 ? 'checked' : ''; ?> />
									<label class="form-check-label" for="id_col0">
										Auto-assign IDs — create new items only
									</label>
								</div>
								<div class="border rounded p-2 mt-1">
									<small class="text-muted fw-semibold d-block mb-1">Use 'id' column in CSV:</small>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="id_col"
										       id="id_col1" value="1" <?php echo $dv==1 ? 'checked' : ''; ?> />
										<label class="form-check-label" for="id_col1">Create items with given IDs</label>
									</div>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="id_col"
										       id="id_col2" value="2" <?php echo $dv==2 ? 'checked' : ''; ?> />
										<label class="form-check-label" for="id_col2">Create <em>or</em> update</label>
									</div>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="id_col"
										       id="id_col3" value="3" <?php echo $dv==3 ? 'checked' : ''; ?> />
										<label class="form-check-label" for="id_col3">Update existing items only</label>
									</div>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="alert alert-info mb-2" style="font-size:.875rem;">
								<b>Defaults apply to new items only.</b><br>
								<b>'Use column'</b> applies to both new and existing items.
							</div>

							<label class="form-label fw-semibold">
								Language
							</label>
							<?php echo $lists['languages']; ?>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">Main Category <span class="text-danger">*</span></label>
							<?php echo $lists['maincat']; ?>
							<div class="form-check mt-1">
								<input class="form-check-input" type="checkbox"
								       name="maincat_col" id="maincat_col" value="1"
								       <?php echo $fv['maincat_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="maincat_col">
									Override with 'catid' column
								</label>
							</div>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">Secondary Categories</label>
							<?php echo $lists['seccats']; ?>
							<div class="form-check mt-1">
								<input class="form-check-input" type="checkbox"
								       name="seccats_col" id="seccats_col" value="1"
								       <?php echo $fv['seccats_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="seccats_col">
									Override with 'cid' column (comma-sep IDs)
								</label>
							</div>
						</div>

						<div class="col-md-4">
							<label class="form-label fw-semibold">State</label>
							<?php echo $lists['states']; ?>
						</div>

						<div class="col-md-4">
							<label class="form-label fw-semibold">Access Level</label>
							<?php echo $lists['access']; ?>
						</div>

						<div class="col-md-4">
							<label for="items_per_step" class="form-label fw-semibold">Items per batch</label>
							<input type="number" name="items_per_step" id="items_per_step"
							       min="1" max="50"
							       value="<?php echo (int) $fv['items_per_step']; ?>"
							       class="form-control" />
							<div class="form-text">Max 50. Lower = safer for large files.</div>
						</div>

					</div><!-- /row -->
				</div><!-- /accordion-body -->
			</div><!-- /colMode -->
		</div><!-- /accordion-item -->


		<!-- ── 2b. Tags & Metadata ───────────────────────────────────── -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="hMeta">
				<button class="accordion-button collapsed" type="button"
				        data-bs-toggle="collapse" data-bs-target="#colMeta"
				        aria-expanded="false" aria-controls="colMeta">
					🏷️ Tags, Author &amp; Dates (optional)
				</button>
			</h2>
			<div id="colMeta" class="accordion-collapse collapse"
			     aria-labelledby="hMeta">
				<div class="accordion-body">
					<div class="row g-3">

						<div class="col-md-6">
							<label class="form-label fw-semibold">Tags column</label>
							<div>
								<?php $dv = $fv['tags_col']; ?>
								<div class="form-check">
									<input class="form-check-input" type="radio" name="tags_col" value="0"
									       id="tags_col0" <?php echo $dv==0 ? 'checked' : ''; ?> />
									<label class="form-check-label" for="tags_col0">Do not import tags</label>
								</div>
								<div class="form-check">
									<input class="form-check-input" type="radio" name="tags_col" value="1"
									       id="tags_col1" <?php echo $dv==1 ? 'checked' : ''; ?> />
									<label class="form-check-label" for="tags_col1">Use 'tags_names' column (comma-sep names)</label>
								</div>
								<div class="form-check">
									<input class="form-check-input" type="radio" name="tags_col" value="2"
									       id="tags_col2" <?php echo $dv==2 ? 'checked' : ''; ?> />
									<label class="form-check-label" for="tags_col2">Use 'tags_raw' column (comma-sep IDs)</label>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">Author</label>
							<div>
								<?php $dv = $fv['created_by_col']; ?>
								<div class="form-check">
									<input class="form-check-input" type="radio" name="created_by_col" value="0"
									       id="created_by_col0" <?php echo $dv==0 ? 'checked' : ''; ?> />
									<label class="form-check-label" for="created_by_col0">Current logged-in user</label>
								</div>
								<div class="form-check">
									<input class="form-check-input" type="radio" name="created_by_col" value="1"
									       id="created_by_col1" <?php echo $dv==1 ? 'checked' : ''; ?> />
									<label class="form-check-label" for="created_by_col1">Use 'created_by' column (user ID)</label>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">Dates in CSV</label>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="created_col" value="1"
								       id="created_col" <?php echo $fv['created_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="created_col">Use 'created' column</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="publish_up_col" value="1"
								       id="publish_up_col" <?php echo $fv['publish_up_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="publish_up_col">Use 'publish_up' column</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="publish_down_col" value="1"
								       id="publish_down_col" <?php echo $fv['publish_down_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="publish_down_col">Use 'publish_down' column</label>
							</div>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">META data columns</label>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="metadesc_col" value="1"
								       id="metadesc_col" <?php echo $fv['metadesc_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="metadesc_col">Use 'metadesc' column</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="metakey_col" value="1"
								       id="metakey_col" <?php echo $fv['metakey_col'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="metakey_col">Use 'metakey' column</label>
							</div>
						</div>

					</div><!-- /row -->
				</div><!-- /accordion-body -->
			</div><!-- /colMeta -->
		</div><!-- /accordion-item -->


		<!-- ── 2c. CSV Format ────────────────────────────────────────── -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="hFmt">
				<button class="accordion-button collapsed" type="button"
				        data-bs-toggle="collapse" data-bs-target="#colFmt"
				        aria-expanded="false" aria-controls="colFmt">
					📋 CSV Format Settings
				</button>
			</h2>
			<div id="colFmt" class="accordion-collapse collapse"
			     aria-labelledby="hFmt">
				<div class="accordion-body">

					<div class="alert alert-info mb-3" style="font-size:.875rem;">
						<b>Standard CSV (Excel compatible):</b>
						Field separator <code>,</code> &nbsp;·&nbsp;
						Enclosure <code>"</code> &nbsp;·&nbsp;
						Item separator <code>\n</code> &nbsp;·&nbsp;
						Multi-value <code>%%</code> &nbsp;·&nbsp;
						Multi-property <code>!!</code>
					</div>

					<div class="row g-3">

						<div class="col-md-4">
							<label for="field_separator" class="form-label">Field separator</label>
							<input type="text" name="field_separator" id="field_separator"
							       value="<?php echo htmlspecialchars($fv['field_separator']); ?>"
							       class="form-control required" />
						</div>

						<div class="col-md-4">
							<label for="enclosure_char" class="form-label">Enclosure char</label>
							<input type="text" name="enclosure_char" id="enclosure_char"
							       value="<?php echo htmlspecialchars($fv['enclosure_char']); ?>"
							       class="form-control" />
						</div>

						<div class="col-md-4">
							<label for="record_separator" class="form-label">Item (record) separator</label>
							<input type="text" name="record_separator" id="record_separator"
							       value="<?php echo htmlspecialchars($fv['record_separator']); ?>"
							       class="form-control required" />
						</div>

						<div class="col-md-4">
							<label for="mval_separator" class="form-label">Multi-value separator</label>
							<input type="text" name="mval_separator" id="mval_separator"
							       value="<?php echo htmlspecialchars($fv['mval_separator']); ?>"
							       class="form-control required" />
						</div>

						<div class="col-md-4">
							<label for="mprop_separator" class="form-label">Multi-property separator</label>
							<input type="text" name="mprop_separator" id="mprop_separator"
							       value="<?php echo htmlspecialchars($fv['mprop_separator']); ?>"
							       class="form-control required" />
						</div>

						<div class="col-md-4">
							<label for="debug_records" class="form-label">Preview rows (test mode)</label>
							<input type="number" name="debug_records" id="debug_records"
							       value="<?php echo (int) $fv['debug_records']; ?>"
							       class="form-control" min="0" />
						</div>

					</div><!-- /row -->
				</div><!-- /accordion-body -->
			</div><!-- /colFmt -->
		</div><!-- /accordion-item -->


		<!-- ── 2d. Media & Advanced ──────────────────────────────────── -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="hAdv">
				<button class="accordion-button collapsed" type="button"
				        data-bs-toggle="collapse" data-bs-target="#colAdv"
				        aria-expanded="false" aria-controls="colAdv">
					🔧 Media Folders &amp; Advanced
				</button>
			</h2>
			<div id="colAdv" class="accordion-collapse collapse"
			     aria-labelledby="hAdv">
				<div class="accordion-body">
					<div class="row g-3">

						<div class="col-md-6">
							<label for="media_folder" class="form-label">Media / image folder</label>
							<input type="text" name="media_folder" id="media_folder"
							       value="<?php echo htmlspecialchars($this->model->getState('media_folder')); ?>"
							       class="form-control" />
							<div class="form-text">Relative to Joomla root. E.g. <code>tmp/fcimport_media</code></div>
						</div>

						<div class="col-md-6">
							<label for="docs_folder" class="form-label">Documents folder</label>
							<input type="text" name="docs_folder" id="docs_folder"
							       value="<?php echo htmlspecialchars($this->model->getState('docs_folder')); ?>"
							       class="form-control" />
						</div>

						<div class="col-md-6">
							<label class="form-label">File field checking</label>
							<div>
								<?php if (!empty($this->file_fields)) : ?>
									<?php foreach ($this->file_fields as $i => $ff) : ?>
									<div class="form-check">
										<input class="form-check-input" type="checkbox"
										       name="skip_file_field[]"
										       id="skip_ff_<?php echo $i; ?>"
										       value="<?php echo htmlspecialchars($ff->name); ?>" />
										<label class="form-check-label" for="skip_ff_<?php echo $i; ?>">
											Skip file check:
											<?php echo htmlspecialchars($ff->label); ?>
											<small class="text-muted">[<?php echo htmlspecialchars($ff->name); ?>]</small>
										</label>
									</div>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="text-muted">No file fields found.</span>
								<?php endif; ?>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-check mt-3">
								<input class="form-check-input" type="checkbox"
								       name="ignore_unused_cols" id="ignore_unused_cols" value="1"
								       <?php echo $fv['ignore_unused_cols'] ? 'checked' : ''; ?> />
								<label class="form-check-label" for="ignore_unused_cols">
									Ignore unused / unrecognised columns
								</label>
							</div>
						</div>

					</div><!-- /row -->
				</div><!-- /accordion-body -->
			</div><!-- /colAdv -->
		</div><!-- /accordion-item -->


		<!-- ── 2e. Quick Format Example ──────────────────────────────── -->
		<div class="accordion-item">
			<h2 class="accordion-header" id="hEx">
				<button class="accordion-button collapsed" type="button"
				        data-bs-toggle="collapse" data-bs-target="#colEx"
				        aria-expanded="false" aria-controls="colEx">
					📖 CSV Format Example (Quick Import)
				</button>
			</h2>
			<div id="colEx" class="accordion-collapse collapse"
			     aria-labelledby="hEx">
				<div class="accordion-body">
					<p><b>Header row</b> must contain exact FLEXIcontent field names (or core property names):</p>
					<pre class="bg-light p-2 rounded" style="font-size:.8rem;">title,text,catid,mygallery,emailfield,weblinkfld
"My article","Article body text",31,"photo.jpg","user@example.com","https://example.com"</pre>
					<ul class="small mb-0">
						<li>Multi-value: <code>val1%%val2%%val3</code></li>
						<li>Multi-property: <code>[-addr-]=email@x.com!![-text-]=Label</code></li>
						<li>Use <b>Preview &amp; Map Fields</b> to avoid needing exact names.</li>
					</ul>
				</div>
			</div>
		</div><!-- /accordion-item -->

	</div><!-- /accordion -->


	<!-- ════════════════════════════════════════════════════════════════
	     ACTION BUTTONS
	     ════════════════════════════════════════════════════════════════ -->
	<div class="d-flex flex-wrap gap-2 mb-4">

		<button type="submit" name="task" value="previewcsv"
		        class="btn btn-primary btn-lg">
			🔀 Preview &amp; Map Fields →
		</button>

		<div class="vr d-none d-md-block mx-1"></div>

		<button type="submit" name="task" value="initcsv"
		        class="btn btn-outline-secondary">
			⚡ Quick Import
			<small class="d-block" style="font-size:.7rem;">(header row must use exact FC field names)</small>
		</button>

		<button type="submit" name="task" value="testcsv"
		        class="btn btn-outline-info">
			🔍 Test File Format
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
</div><!-- #flexicontent -->
