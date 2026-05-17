# FLEXIcontent Plus Changelog

## v6.1.0-alpha.7 — 17 May 2026

### Major additions since alpha.6

**Admin UI accessibility overhaul (B2–B7) — WCAG 2.1 AA targeting:**
- B2 Foundation: 16px typography baseline, 4-step font scale tokens,
  unified `:focus-visible` ring (3:1 contrast on light/dark Atum),
  `:lang(th)` 18px bump for Thai diacritic readability, 23 legacy
  `outline:none` declarations removed across 4 CSS files.
- B3 List views (8 templates): `<caption>` + `<th scope>` semantics,
  `<div onclick>` toolbar/disclosure widgets converted to `<button>`
  with `aria-expanded`/`aria-controls`, search inputs gain labels,
  type-filter `<h3 onmouseup>` rewritten as `<button aria-pressed>`
  toggle group.
- B4 Item edit form: tab widget ARIA (`role=region/tabpanel`,
  panel `tabindex`), heading hierarchy fix (no `<h1>→<h3>` skips),
  canonical custom-field row gets `role=group`/`aria-labelledby`/
  `visually-hidden JREQUIRED`, stable `desc_fcfield_<id>` and
  `err_fcfield_<id>` ids, 15 core field row wrappers role=group,
  11 publishing/meta wrappers role=group.
- B5 Dashboard + modals: cpanel single `<h1>`, 8 `h3→h2` section
  headers, filemanager `<h1>` + copyUrlModal `aria-modal=true`,
  batch popup `<h1>` from `FLEXI_BATCH_OPTIONS`/`FLEXI_TRANSLATE_OPTIONS`.
- B6 Field row layout + Select2 v3 normalisation: dropdowns now
  fill row width consistently, info-icon kept inline, 36px input
  height. Scoped `[class*="required"]` pill rule so it no longer
  matches `div.select2-container.required` (the "red oval pill"
  bug). Icon-only `<a>`/`<button>` across helpers gain `aria-label`,
  inline `<i class="icon-…">` get `aria-hidden="true"`.
- B7 Live regions: shared `admin/tmpl_inc/announcer.php` (one polite
  + one assertive region + `flexicontent.announce()` JS helper),
  items list AJAX bind/fix-cat announcements, item edit per-field
  error bridge that mirrors browser `invalid` events into the B4
  `role=alert` regions.
- Tabber JS: `tabber-minimized.js` gains `role=tablist/tab`,
  `aria-selected/controls`, roving tabindex, Left/Right/Home/End
  keyboard navigation.
- Stats view: 6 layout-table title rows refactored to `<h2>`,
  remaining data tables get `scope=col`.

**Pro Templates frontend (alpha) — PT1+PT2+PT3+PT8:**
- New `admin/helpers/protemplate/Resolver.php` — picks the highest
  priority Pro Layout (item > menu > category > type > global).
- New `admin/helpers/protemplate/Renderer.php` — accessible HTML
  output: heading hierarchy enforced (one H1 in item view, clamped
  H2+ elsewhere), `<figure>`/`<figcaption>` + always-present `alt`,
  LCP-aware `loading=eager`/`fetchpriority=high` on the first image
  in item context, `<time datetime>` for dates, `<ul>` tags,
  `aria-label` on read-more links, InputFilter whitelist for
  user-authored HTML blocks (`<h1>`/scripts/event handlers blocked).
- Hooked into `admin/views/item/view.html.php :: _displayItem()`
  and `site/views/category/view.html.php` — falls back to legacy
  template on any renderer exception.
- 10 new PHPUnit tests in `ResolverPriorityTest` covering priority
  ordering, applicability strictness, and heading clamp logic.

**CSV import wizard (Batch 1 fixes):**
- 3-step single-page wizard (file → fields → import) with field
  mapping table.
- A11y: file input + content type labels, formError region with
  focus move, AJAX status announced via `aria-live=polite`, mapping
  selects get `aria-label`, mapping progress announced.
- Security: CSV header sanitisation (alphanum + underscore, max 64
  chars) for HTML name attribute safety, server mirrors regex with
  dual-lookup fallback; `getfieldsajax` invalid token now returns
  HTTP 403 + JSON instead of plain text exit; +7 PHPUnit tests.

**Build pipeline:**
- `tools/build-css.sh` — regenerates every `*.min.css` from its
  source, bumps `FLEXI_VHASH` via `touch defineconstants.php`.
- `tools/deploy-to.sh` — rsyncs `admin/` + `site/` into a Joomla
  install's flat `administrator/components/com_flexicontent/` and
  `components/com_flexicontent/` layout; `--update`/`--full`/
  `--delete`/`--dry-run` flags.

**Docs:**
- `docs/handoff-import-wizard.md` (224 lines)
- `docs/rfc/RFC-001..004.md` — modernization roadmap, Field SDK v2,
  REST API v1, design system & component library.

## v6.1.0 — 15 April 2026

### 🎉 Major: Joomla 5.x + 6.1 "Nyota" Compatibility

**Joomla compatibility:**
- Joomla 4.4.x (PHP 7.4–8.1) — backward compatible
- Joomla 5.0–5.4 (PHP 8.1–8.2) — fully supported  
- Joomla 6.0 (PHP 8.3) — fully supported
- Joomla 6.1 "Nyota" (PHP 8.3–8.4) — fully supported

**PHP 8.1+ fixes (156 files):**
- `each()` → `foreach` (PHP 8.0 removed)
- `FILTER_SANITIZE_STRING` → `FILTER_SANITIZE_SPECIAL_CHARS`
- ArrayAccess/Iterator return types added
- `JRequest` → `Factory::getApplication()->input`
- `jimport()` 355 calls → PSR-4 `use` statements

**Joomla 5/6 API migration (93 files):**
- `JText/JHtml/JFactory/JUri/JRoute` → namespaced classes
- `JError` → `enqueueMessage()` / `RuntimeException`
- `addScript()/addStyleSheet()` → WebAsset Manager

**PHP 8.2+ / Dynamic properties (96 files):**
- `#[AllowDynamicProperties]` added to all affected classes
- 605 class properties declared explicitly

**Manifest:**
- php_minimum: 8.1
- MySQL 8.0.13+ / MariaDB 10.4+ declared

---

## v4.2.1 — 15 July 2023
*(Previous release — see GitHub releases)*
