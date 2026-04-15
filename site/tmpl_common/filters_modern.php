<?php
/**
 * FLEXIcontent — Modern Filter/Search Template
 * Bootstrap 5 + Mobile-first + Accessible
 * @version 6.1.0
 */
defined('_JEXEC') or die;

$app      = \Joomla\CMS\Factory::getApplication();
$document = \Joomla\CMS\Factory::getDocument();

$show_search_go     = $params->get('show_search_go', 1);
$show_search_reset  = $params->get('show_search_reset', 1);
$filter_autosubmit  = (int)$params->get('filter_autosubmit', 0);
$badge_position     = (int)$params->get('badge_position', 0);
$use_search         = $params->get('use_search', 1);
$show_search_label  = $params->get('show_search_label', 1);
$use_filters        = $params->get('use_filters', 0) && !empty($filters);
$show_filter_labels = $params->get('show_filter_labels', 1);
$show_search_go     = $show_search_go || !$filter_autosubmit;
$filter_placement   = (int)$params->get('filter_placement', 1);
$filters_in_tabs    = $filter_placement === 3;
$filters_in_slide   = $params->get('fc_filter_in_slide', 0);

if (!($use_search || $use_filters)) return;

// Load modern CSS
$document->getWebAssetManager()->registerAndUseStyle(
    'fc-flexi-modern',
    \Joomla\CMS\Uri\Uri::root().'components/com_flexicontent/assets/css/flexi_modern.css',
    ['version' => FLEXI_VHASH]
);

// Count active filters
$active_count = 0;
$active_filter_data = [];
if ($use_filters && $badge_position > 0) {
    foreach ($filters as $filt) {
        if (empty($filt->html)) continue;
        $val = $app->input->get('filter_'.$filt->id, '', 'array');
        if (empty($val)) continue;
        preg_match('/<option[^>]+selected=["\']selected["\'][^>]*>(.*?)<\/option>/is', $filt->html, $m);
        $dv = isset($m[1]) ? $m[1] : (is_array($val) ? implode(', ', $val) : $val);
        $dv = trim(strip_tags(preg_replace('/\s*\([^)]*\)/', '', (string)$dv)));
        if ($dv && strpos($dv, '-') !== 0 && !in_array(strtolower($dv), ['all','tous','any','',','])) {
            $active_count++;
            $active_filter_data[] = (object)['name'=>'filter_'.$filt->id,'label'=>trim(strip_tags($filt->label)),'value'=>$dv];
        }
    }
}

$uniq_id = $form_id.'_fc_filter';
$searchphrase_selector = flexicontent_html::searchphrase_selector($params, $form_name);

if ($filters_in_slide) {
    $ff_slider_id = (!empty($module->id) ? '_module_'.$module->id : '_category');
    $ff_slider_tagid = 'fcfilter_form_slider'.$ff_slider_id;
    $last_active_slide = isset($active_slides->$ff_slider_tagid) ? $active_slides->$ff_slider_tagid : null;
}
?>

<div id="<?php echo $form_id; ?>_filter_box" class="fc-filter-box-modern">

    <?php /* Active badges — top */ ?>
    <?php if ($badge_position === 1 && !empty($active_filter_data)) : ?>
    <div class="d-flex flex-wrap gap-2 mb-3" role="group">
        <?php foreach ($active_filter_data as $b) : ?>
        <span class="fc-active-badge">
            <span class="fc-badge-label"><?php echo htmlspecialchars($b->label); ?>:</span>
            <strong><?php echo htmlspecialchars($b->value); ?></strong>
            <button type="button" class="btn-close btn-close-white btn-sm" aria-label="<?php echo \Joomla\CMS\Language\Text::_('JREMOVE'); ?>"
                onclick="fcRemoveSingleFilter('<?php echo htmlspecialchars($b->name); ?>', this)" style="font-size:.55em;opacity:.8"></button>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php /* Mobile toggle button */ ?>
    <?php if ($use_filters) : ?>
    <button type="button" class="fc-mobile-filter-btn" aria-expanded="false" aria-controls="<?php echo $uniq_id; ?>_collapse"
        onclick="var c=document.getElementById('<?php echo $uniq_id; ?>_collapse'),open=c.classList.toggle('fc-open');this.setAttribute('aria-expanded',open);">
        <span>
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right:.4rem"><path d="M1.5 2h13a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1zm2 4h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1 0-1zm2 4h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/></svg>
            <?php echo \Joomla\CMS\Language\Text::_('FLEXI_FILTERS'); ?>
            <?php if ($active_count > 0) : ?>
                <span class="badge bg-primary ms-1"><?php echo $active_count; ?></span>
            <?php endif; ?>
        </span>
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
    </button>
    <?php endif; ?>

    <?php /* Main filter card */ ?>
    <div id="<?php echo $uniq_id; ?>_collapse" class="fc-filter-collapsible fc-open">
    <div class="fc-filter-card card border-0">
    <div class="card-body p-3 p-md-4">

        <?php /* Text search */ ?>
        <?php if ($use_search) : ?>
        <div class="mb-3">
            <?php if ($show_search_label) : ?>
            <label for="<?php echo $form_id; ?>_filter"
                class="form-label fc-filter-heading mb-1">
                <?php echo \Joomla\CMS\Language\Text::_('FLEXI_TEXT_SEARCH'); ?>
            </label>
            <?php endif; ?>
            <div class="input-group fc-search-input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/></svg>
                </span>
                <input type="text"
                    class="form-control border-start-0 fc_text_filter ps-1"
                    name="filter"
                    id="<?php echo $form_id; ?>_filter"
                    value="<?php echo htmlspecialchars($text_search_val ?? '', ENT_COMPAT, 'UTF-8'); ?>"
                    placeholder="<?php echo \Joomla\CMS\Language\Text::_('FLEXI_TYPE_TO_LIST'); ?>"
                    autocomplete="off"
                    aria-label="<?php echo \Joomla\CMS\Language\Text::_('FLEXI_TEXT_SEARCH'); ?>"
                />
                <?php if ($show_search_go && !$use_filters) : ?>
                <button type="button" class="btn btn-primary px-4"
                    onclick="var f=jQuery(this).closest('form')[0];adminFormPrepare(f,2);return false;">
                    <?php echo \Joomla\CMS\Language\Text::_('FLEXI_GO'); ?>
                </button>
                <?php endif; ?>
            </div>
            <?php echo $searchphrase_selector; ?>
        </div>
        <?php endif; ?>

        <?php /* Filter fields grid */ ?>
        <?php if ($use_filters) : ?>
        <?php
        $opentag  = !$filters_in_tabs ? $params->get('filter_opentag','') : '';
        $closetag = !$filters_in_tabs ? $params->get('filter_closetag','') : '';
        echo $opentag;
        if ($filters_in_slide) {
            echo \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.startAccordion', $ff_slider_tagid, ['active'=>$last_active_slide]);
        }
        ?>
        <div class="row g-2 g-md-3">
        <?php
        $n = 0;
        foreach ($filters as $filt) {
            if (empty($filt->html)) continue;
            $label_show = !$filters_in_tabs && ($show_filter_labels==1 || ($show_filter_labels==0 && $filt->parameters->get('display_label_filter')==1));
            $is_active  = !empty($app->input->get('filter_'.$filt->id,'','array'));
            $n++;
            if ($filters_in_slide) {
                echo \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.addSlide', $ff_slider_tagid, $filt->label, '_filters_slide'.$filt->id);
            }
        ?>
            <div class="col-12 col-sm-6 col-xl-4 fc-filter-col fc_filter_id_<?php echo $filt->id;?><?php echo $is_active?' fc-filter-active':''; ?>">
                <?php if ($label_show) : ?>
                <label class="form-label fc-filter-label mb-1<?php echo $is_active?' text-primary':''; ?>">
                    <?php echo strip_tags($filt->label); ?>
                    <?php if ($is_active) : ?><span class="fc-active-dot"></span><?php endif; ?>
                </label>
                <?php endif; ?>
                <div class="fc-filter-control<?php echo $is_active?' fc-control-active':''; ?>">
                    <?php echo $filt->html; ?>
                </div>
            </div>
        <?php
            if ($filters_in_slide) echo \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.endSlide');
        }
        ?>
        </div>
        <?php
        if ($filters_in_slide) echo \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.endAccordion');
        echo $closetag;
        ?>
        <?php endif; ?>

        <?php /* Action buttons */ ?>
        <?php if ($show_search_go || $show_search_reset) : ?>
        <div class="fc-filter-actions d-flex gap-2 flex-wrap mt-3 pt-3 border-top">
            <?php if ($show_search_go) : ?>
            <button type="button" class="btn btn-primary fc-btn-go"
                onclick="var f=jQuery(this).closest('form')[0];adminFormPrepare(f,2);return false;">
                <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.018-.118zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/></svg>
                <?php echo \Joomla\CMS\Language\Text::_('FLEXI_GO'); ?>
            </button>
            <?php endif; ?>
            <?php if ($show_search_reset) : ?>
            <button type="button" class="btn btn-outline-secondary fc-btn-reset"
                onclick="var f=jQuery(this).closest('form')[0];adminFormClearFilters(f);adminFormPrepare(f,2);return false;">
                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854z"/></svg>
                <?php echo \Joomla\CMS\Language\Text::_('FLEXI_RESET'); ?>
            </button>
            <?php endif; ?>
            <?php if ($use_filters && count((array)$filters) > 0) : ?>
            <span class="ms-auto align-self-center text-muted small d-none d-sm-inline">
                <?php echo count((array)$filters); ?> <?php echo \Joomla\CMS\Language\Text::_('FLEXI_FILTERS'); ?>
                <?php if ($active_count > 0) : ?>
                    · <span class="text-primary fw-semibold"><?php echo $active_count; ?> active</span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
    </div>
    </div><!-- /.fc-filter-collapsible -->

    <?php /* Active badges — bottom */ ?>
    <?php if ($badge_position === 2 && !empty($active_filter_data)) : ?>
    <div class="d-flex flex-wrap gap-2 mt-3" role="group">
        <?php foreach ($active_filter_data as $b) : ?>
        <span class="fc-active-badge">
            <span class="fc-badge-label"><?php echo htmlspecialchars($b->label); ?>:</span>
            <strong><?php echo htmlspecialchars($b->value); ?></strong>
            <button type="button" class="btn-close btn-close-white btn-sm" style="font-size:.55em;opacity:.8"
                onclick="fcRemoveSingleFilter('<?php echo htmlspecialchars($b->name); ?>', this)"></button>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php
$js_autosubmit = (int)$filter_autosubmit;
$js = <<<JS
window.fcRemoveSingleFilter = function(n, el) {
    var f = jQuery(el).closest('form');
    if (!f.length) return;
    f.find('[name="'+n+'"],[name="'+n+'[]"],[name="'+n+'[1]"],[name="'+n+'[2]"]').each(function(){
        var el = jQuery(this);
        if (el.is('select')) { el.prop('selectedIndex',0); if(el.data('select2')) el.val(null).trigger('change.select2'); }
        else el.val('');
    });
    if (typeof adminFormPrepare==='function') adminFormPrepare(f[0], 2); else f[0].submit();
};
jQuery(document).ready(function($){
    var cid = '{$form_id}_filter_box';
    var form = $('#'+cid).closest('form');
    if (!form.attr('data-fc-autosubmit') || form.attr('data-fc-autosubmit')==='0')
        form.attr('data-fc-autosubmit', '{$js_autosubmit}' == '1' ? '2' : '1');
    $(document).on('change','#'+cid+' input:not([type=hidden]),#'+cid+' select',function(){
        if (!$(this).hasClass('fc_autosubmit_exclude')) {
            var f = this.form || $(this).closest('form')[0];
            if (typeof adminFormPrepare==='function') adminFormPrepare(f, '$js_autosubmit'=='1'?'2':'1');
        }
    });
});
JS;
$document->addScriptDeclaration($js);
