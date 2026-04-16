/**
 * FLEXIcontent select2 Compatibility Layer
 * Ensures select2 v3.5.4 works correctly alongside Joomla 5/6
 * which may load select2 v4.x via vendor assets
 * @version 6.1.0
 */
(function($) {
  'use strict';

  // ── Step 1: Detect select2 version ────────────────────────────
  function getSelect2Version() {
    if (!$.fn.select2) return null;
    // v4.x has $.fn.select2.amd
    if ($.fn.select2.amd) return 4;
    // v3.x has $.fn.select2.defaults
    if ($.fn.select2.defaults) return 3;
    return null;
  }

  // ── Step 2: Save FC's select2 v3 reference immediately ────────
  // FC loads its own select2 v3 BEFORE this runs.
  // Store it so we can restore after Joomla might override with v4.
  var fc_select2_v3 = null;
  var fc_select2_v3_css = null;

  $(document).ready(function() {
    var ver = getSelect2Version();

    if (ver === 3) {
      // ✅ v3 loaded — save reference
      fc_select2_v3 = $.fn.select2;
      window._fc_select2_v3 = fc_select2_v3;

    } else if (ver === 4) {
      // ⚠️ Joomla v4 loaded instead of FC's v3
      // This breaks FC's event API: 'select2-open', 'select2-close', 'select2-selecting'
      // Patch: wrap v4 to emit v3 events too
      patchSelect2V4toV3Events($);
    }

    // ── Step 3: Patch fc_attachSelect2 for safety ───────────────
    patchFcAttachSelect2($, ver);
  });


  /**
   * Patch select2 v4 to emit legacy v3 events
   * so FC's event handlers (select2-open, etc.) still fire
   */
  function patchSelect2V4toV3Events($) {
    var originalS2 = $.fn.select2;
    if (!originalS2 || !originalS2.amd) return; // not v4

    var originalOn = $.fn.on;

    // Map v3 event names → v4 equivalents
    var v3ToV4Events = {
      'select2-open':       'select2:open',
      'select2-close':      'select2:close',
      'select2-selecting':  'select2:selecting',
      'select2-selected':   'select2:select',
      'select2-unselecting':'select2:unselecting',
      'select2-removed':    'select2:unselect'
    };

    // Intercept .on() calls to remap v3→v4 event names
    $.fn.on = function(events) {
      if (typeof events === 'string') {
        var parts = events.split(' ');
        var mapped = parts.map(function(ev) {
          var base = ev.split('.')[0]; // strip namespace
          var ns   = ev.indexOf('.') >= 0 ? ev.slice(ev.indexOf('.')) : '';
          return (v3ToV4Events[base] || base) + ns;
        });
        arguments[0] = mapped.join(' ');
      }
      return originalOn.apply(this, arguments);
    };

    // Also patch trigger to translate v3→v4
    var originalTrigger = $.fn.trigger;
    $.fn.trigger = function(eventName) {
      var mapped = v3ToV4Events[eventName] || eventName;
      arguments[0] = mapped;
      return originalTrigger.apply(this, arguments);
    };

    // Patch .select2('data', x) → .val(x).trigger('change')
    // .select2('val', x) → .val(x).trigger('change')
    var originalS2Plugin = $.fn.select2;
    $.fn.select2 = function(method) {
      if (typeof method === 'string') {
        if (method === 'data' || method === 'val') {
          if (arguments.length > 1) {
            var val = arguments[1];
            if (val !== null && val !== undefined) {
              var ids = Array.isArray(val) ? val.map(function(v){ return typeof v === 'object' ? v.id : v; }) : val;
              return this.val(ids).trigger('change');
            }
          }
        }
        if (method === 'open') {
          return originalS2Plugin.apply(this, arguments);
        }
        if (method === 'destroy') {
          return originalS2Plugin.apply(this, arguments);
        }
      }
      return originalS2Plugin.apply(this, arguments);
    };
    // Restore prototype
    $.fn.select2.amd = originalS2.amd;
    $.fn.select2.defaults = originalS2.defaults;

    console.log('[FC] select2 v4→v3 compatibility layer active');
  }


  /**
   * Patch fc_attachSelect2 to handle both v3 and v4 gracefully
   */
  function patchFcAttachSelect2($, ver) {
    if (typeof window.fc_attachSelect2 !== 'function') return;

    var originalAttach = window.fc_attachSelect2;

    window.fc_attachSelect2 = function(sel, s2_elems) {
      try {
        return originalAttach.call(this, sel, s2_elems);
      } catch(e) {
        console.warn('[FC] fc_attachSelect2 error (select2 compat):', e.message);

        // Fallback: basic attach without checkbox mode
        var sbox = $(sel || 'body');
        var elems = s2_elems || sbox.find('select.use_select2_lib');
        elems.each(function() {
          var el = $(this);
          if (el.data('select2') || el.data('select2-id')) return; // already init'd
          try {
            var opts = { minimumResultsForSearch: 10 };
            if (el.attr('multiple')) opts.closeOnSelect = false;

            // v4 API
            if ($.fn.select2.amd) {
              opts.templateResult = function(item) {
                var dt = $(item.element).attr('data-title');
                return dt ? $('<span title="'+dt+'">'+item.text+'</span>') : item.text;
              };
            } else {
              // v3 API
              opts.formatResult = function(item) {
                var dt = $(item.element).attr('data-title');
                return dt ? '<div title="'+dt+'">'+item.text+'</div>' : item.text;
              };
            }

            el.select2(opts);
          } catch(inner) {
            console.warn('[FC] select2 init failed for element:', inner.message);
          }
        });
      }
    };

    // Guard: re-init select2 after AJAX loads
    $(document).on('fc:fieldsLoaded fc:itemLoaded', function(e) {
      var container = $(e.target);
      var newSelects = container.find('select.use_select2_lib').not('[data-select2-id]');
      if (newSelects.length) {
        window.fc_attachSelect2(container, newSelects);
      }
    });
  }

})(jQuery);
