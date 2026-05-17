<?php
defined('_JEXEC') or die('Restricted access');

/**
 * REMAINING FORM
 */
?>
			<?php if ($buttons_placement === 1) : /* PLACE buttons at BOTTOM of form */ ?>
			<div class="fctoolbar_bottom_placement fcpos_right">
				<div id="fctoolbar_btn" class="btn btn-primary" onclick="fc_toggle_box_via_btn(<?php echo FLEXI_J40GE ? "jQuery('#fctoolbar').parent()" : "'fctoolbar'"; ?>, this, 'btn-primary');" >
					<?php echo \Joomla\CMS\Language\Text::_('JTOOLBAR'); ?> <span class="icon-wrench" aria-hidden="true"></span></a>
				</div>
				<?php // An EXAMPLE of adding more buttons: $this->toolbar->appendButton('Standard', 'cancel', 'JCANCEL', 'items.cancel', false);
				echo $this->toolbar->render(); ?>
			</div>
			<?php endif; ?>

				<br class="clear" />
				<?php echo \Joomla\CMS\HTML\HTMLHelper::_( 'form.token' ); ?>
				<input type="hidden" name="task" id="task" value="" />
				<input type="hidden" name="option" value="com_flexicontent" />
				<input type="hidden" name="controller" value="items" />
				<input type="hidden" name="view" value="item" />
				<?php echo $this->form->getInput('id');?>
				<?php echo $this->form->getInput('hits'); /* this is ignored by form validation */ ?>

				<?php if ($is_autopublished) :
				/* Auto publish new item via MENU OVERRIDE, (these are overwritten by the controller checks) */
				?>
					<input type="hidden" id="jform_state" name="jform[state]" value="1" />
					<input type="hidden" id="jform_vstate" name="jform[vstate]" value="2" />
				<?php elseif (!$usestate) :
				/* Not using state (this is overwritten by the controller checks to maintain current value) */
				?>
					<input type="hidden" id="jform_state" name="jform[state]" value="<?php echo (int) $this->row->state; ?>" />
					<input type="hidden" id="jform_vstate" name="jform[vstate]" value="2" />
				<?php elseif ($this->perms['canpublish'] && (!$use_versioning || $auto_approve)) :?>
					<input type="hidden" id="jform_vstate" name="jform[vstate]" value="2" />
				<?php endif; ?>

				<?php if ( $isnew && $typeid ) : /* this is compared to submit menu item configuration by the controller */ ?>
					<input type="hidden" name="jform[type_id]" value="<?php echo $typeid; ?>" />
				<?php endif;?>

				<input type="hidden" name="referer" value="<?php echo htmlspecialchars($this->referer ?? '', ENT_COMPAT, 'UTF-8'); ?>" />

				<?php if ($isSite) : ?>
					<?php if ($isnew) echo $this->submitConf; ?>
				<?php endif; ?>

				<input type="hidden" name="unique_tmp_itemid" value="<?php echo substr($app->input->get('unique_tmp_itemid', '', 'string'), 0, 1000);?>" />

			</form>
			<div class="fcclear"></div>

		</div> <!-- class="span** col**" -->

	<?php if ($buttons_placement === 3) : /* PLACE buttons at RIGHT of form */ ?>
		<div class="span2 col-md-2 fctoolbar_side_placement">
			<div id="fctoolbar_btn" class="btn btn-primary" onclick="fc_toggle_box_via_btn(<?php echo FLEXI_J40GE ? "jQuery('#fctoolbar').parent()" : "'fctoolbar'"; ?>, this, 'btn-primary');" >
				<?php echo \Joomla\CMS\Language\Text::_('JTOOLBAR'); ?> <span class="icon-wrench" aria-hidden="true"></span></a>
			</div>
			<?php // An EXAMPLE of adding more buttons: $this->toolbar->appendButton('Standard', 'cancel', 'JCANCEL', 'items.cancel', false);
			echo $this->toolbar->render(); ?>
		</div>
	<?php endif; ?>

	</div>  <!-- class="container-fluid row" -->
</div>  <!-- id="flexicontent" -->

<?php /* B7: shared SR announcer (polite + assertive). Idempotent. */ ?>
<?php require_once JPATH_ADMINISTRATOR . '/components/com_flexicontent/tmpl_inc/announcer.php'; ?>
<script>
(function () {
	/**
	 * B7 — per-field error region bridge.
	 * Joomla formvalidator adds .invalid + aria-invalid to inputs but
	 * writes error text to the input's title attribute (browser tooltip)
	 * rather than into a sibling error region. This bridge mirrors the
	 * message into the existing #err_fcfield_<id> region added in B4
	 * so role="alert" + aria-live="polite" fires.
	 */
	function findErrEl(input) {
		var container = input.closest && input.closest('.control-group');
		if (!container) { return null; }
		var labelLink = container.querySelector('[id^="label_fcfield_"]');
		if (!labelLink) { return null; }
		var fid = labelLink.id.replace('label_fcfield_', '');
		return document.getElementById('err_fcfield_' + fid);
	}
	function reportError(input) {
		var errEl = findErrEl(input);
		if (!errEl) { return; }
		var msg = input.getAttribute('data-validation-text')
			|| input.validationMessage
			|| input.title
			|| "<?php echo \Joomla\CMS\Language\Text::_('JLIB_FORM_FIELD_INVALID', true); ?>";
		errEl.textContent = msg;
		errEl.hidden = false;
		input.setAttribute('aria-invalid', 'true');
		var existing = input.getAttribute('aria-describedby') || '';
		if (existing.indexOf(errEl.id) === -1) {
			input.setAttribute('aria-describedby', (existing ? existing + ' ' : '') + errEl.id);
		}
	}
	function clearError(input) {
		var errEl = findErrEl(input);
		if (!errEl) { return; }
		errEl.textContent = '';
		errEl.hidden = true;
		input.removeAttribute('aria-invalid');
	}
	document.addEventListener('invalid', function (e) {
		if (e.target instanceof Element) { reportError(e.target); }
	}, true);
	document.addEventListener('input', function (e) {
		var t = e.target;
		if (t && t.matches && t.matches('.invalid, [aria-invalid="true"]')) {
			if (t.checkValidity && t.checkValidity()) { clearError(t); }
		}
	}, true);
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.querySelectorAll) { return; }
		var firstInvalid = form.querySelector(':invalid, .invalid, [aria-invalid="true"]');
		if (firstInvalid && window.flexicontent && flexicontent.announce) {
			flexicontent.announce(
				"<?php echo \Joomla\CMS\Language\Text::_('FLEXI_FORM_HAS_ERRORS', true); ?>",
				{ assertive: true }
			);
		}
	}, true);
})();
</script>

<?php
//keep session alive while editing
\Joomla\CMS\HTML\HTMLHelper::_('behavior.keepalive');
