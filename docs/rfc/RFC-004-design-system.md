# RFC-004: Design System & Component Library

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Author** | @pisan |
| **Created** | 2026-04-18 |
| **Target** | v7.0 (tokens) → v8.0 (full system) |
| **Depends on** | RFC-001 |

---

## 1. Summary

สร้าง **Design System** ของ FLEXIContent Flux — design tokens (CSS variables) + component library + dark mode + theming.  
แก้ปัญหาปัจจุบัน: CSS กระจาย 50+ ไฟล์, inline styles ใน templates, ไม่มี consistency, dark mode ไม่รองรับ

## 2. Motivation

**Audit ปัญหา CSS ใน v6.x:**
- `admin/assets/css/flexi_form_fields.css` — 1,770 บรรทัด mixed concerns
- Templates inject `<style>` inline (พบใน yootheme_grid/item.php, category_items.php)
- Hardcoded colors (`#6366f1`, `#10b981`, etc.) กระจายทั่ว
- ไม่มี spacing/radius/shadow scale
- No dark mode
- `!important` budget = unlimited
- User override ยาก → มัก override ทั้งก้อน
- Responsive breakpoints ไม่ consistent (บางที่ 640px, บางที่ 768px, 800px, 900px)

---

## 3. Design Principles

1. **Token-first** — ทุก value ผ่าน CSS variable
2. **Layer-based** — `@layer base, components, utilities, overrides`
3. **No-`!important` budget** — ห้ามใช้ยกเว้น user-override layer
4. **A11y default** — contrast ratio AA, focus rings, reduced motion
5. **Theme-able** — light/dark/custom via token overrides
6. **Framework-free public CSS** — Web Component + vanilla CSS
7. **Vite-friendly** — tree-shakeable, content-hashed, module imports

---

## 4. Token Categories

### 4.1 Colors

```css
:root {
  /* Brand */
  --fc-color-brand-50:  #eef2ff;
  --fc-color-brand-100: #e0e7ff;
  --fc-color-brand-200: #c7d2fe;
  --fc-color-brand-300: #a5b4fc;
  --fc-color-brand-400: #818cf8;
  --fc-color-brand-500: #6366f1;  /* primary */
  --fc-color-brand-600: #4f46e5;
  --fc-color-brand-700: #4338ca;
  --fc-color-brand-800: #3730a3;
  --fc-color-brand-900: #312e81;

  /* Semantic */
  --fc-color-success:   #10b981;
  --fc-color-warning:   #f59e0b;
  --fc-color-danger:    #ef4444;
  --fc-color-info:      #0ea5e9;

  /* Neutrals */
  --fc-color-text:         #111827;
  --fc-color-text-muted:   #6b7280;
  --fc-color-text-subtle:  #9ca3af;
  --fc-color-bg:           #ffffff;
  --fc-color-bg-subtle:    #f8fafc;
  --fc-color-bg-muted:     #f3f4f6;
  --fc-color-border:       #e5e7eb;
  --fc-color-border-strong: #d1d5db;

  /* Surfaces */
  --fc-color-surface:       #ffffff;
  --fc-color-surface-alt:   #f9fafb;
  --fc-color-surface-raise: #ffffff;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  :root {
    --fc-color-text:         #f9fafb;
    --fc-color-text-muted:   #9ca3af;
    --fc-color-bg:           #0f172a;
    --fc-color-bg-subtle:    #1e293b;
    --fc-color-bg-muted:     #334155;
    --fc-color-border:       #334155;
    --fc-color-surface:      #1e293b;
    --fc-color-surface-alt:  #0f172a;
  }
}

/* Manual toggle */
[data-fc-theme="dark"] { /* same vars as prefers-color-scheme */ }
```

### 4.2 Typography

```css
:root {
  --fc-font-family-sans: -apple-system, BlinkMacSystemFont, "Segoe UI",
                          Roboto, "Noto Sans Thai", sans-serif;
  --fc-font-family-mono: ui-monospace, "SF Mono", "Menlo", monospace;
  --fc-font-family-serif: Georgia, serif;

  /* Fluid type scale */
  --fc-text-xs:   clamp(0.72rem, 0.7rem + 0.1vw, 0.78rem);
  --fc-text-sm:   clamp(0.85rem, 0.83rem + 0.1vw, 0.9rem);
  --fc-text-base: clamp(0.95rem, 0.93rem + 0.1vw, 1rem);
  --fc-text-lg:   clamp(1.1rem, 1.05rem + 0.25vw, 1.2rem);
  --fc-text-xl:   clamp(1.25rem, 1.2rem + 0.25vw, 1.5rem);
  --fc-text-2xl:  clamp(1.5rem, 1.4rem + 0.5vw, 1.875rem);
  --fc-text-3xl:  clamp(1.875rem, 1.7rem + 0.9vw, 2.5rem);
  --fc-text-display: clamp(2.5rem, 2rem + 2vw, 4rem);

  --fc-font-weight-regular:  400;
  --fc-font-weight-medium:   500;
  --fc-font-weight-semibold: 600;
  --fc-font-weight-bold:     700;

  --fc-leading-tight:  1.25;
  --fc-leading-normal: 1.5;
  --fc-leading-relaxed: 1.7;

  --fc-tracking-tight:  -0.01em;
  --fc-tracking-normal:  0;
  --fc-tracking-wide:    0.04em;
  --fc-tracking-wider:   0.08em;
}
```

### 4.3 Spacing

```css
:root {
  --fc-space-0:   0;
  --fc-space-1:   0.25rem;    /* 4px */
  --fc-space-2:   0.5rem;     /* 8px */
  --fc-space-3:   0.75rem;    /* 12px */
  --fc-space-4:   1rem;       /* 16px */
  --fc-space-5:   1.25rem;    /* 20px */
  --fc-space-6:   1.5rem;     /* 24px */
  --fc-space-8:   2rem;       /* 32px */
  --fc-space-10:  2.5rem;     /* 40px */
  --fc-space-12:  3rem;       /* 48px */
  --fc-space-16:  4rem;
  --fc-space-20:  5rem;
  --fc-space-24:  6rem;
}
```

### 4.4 Radius, Shadow, Motion

```css
:root {
  /* Radius */
  --fc-radius-sm:   4px;
  --fc-radius:      6px;
  --fc-radius-md:   8px;
  --fc-radius-lg:   12px;
  --fc-radius-xl:   16px;
  --fc-radius-2xl:  24px;
  --fc-radius-full: 9999px;

  /* Shadow */
  --fc-shadow-xs:   0 1px 2px rgba(0, 0, 0, 0.04);
  --fc-shadow-sm:   0 1px 6px rgba(0, 0, 0, 0.07);
  --fc-shadow:      0 2px 14px rgba(0, 0, 0, 0.08);
  --fc-shadow-md:   0 4px 18px rgba(0, 0, 0, 0.09);
  --fc-shadow-lg:   0 8px 32px rgba(0, 0, 0, 0.12);
  --fc-shadow-focus: 0 0 0 3px rgba(99, 102, 241, 0.3);

  /* Motion */
  --fc-duration-fast:   150ms;
  --fc-duration:        200ms;
  --fc-duration-slow:   300ms;
  --fc-easing-standard: cubic-bezier(0.2, 0, 0, 1);
  --fc-easing-emphasize: cubic-bezier(0.2, 0, 0, 1);
  --fc-easing-decelerate: cubic-bezier(0, 0, 0, 1);
}

@media (prefers-reduced-motion: reduce) {
  :root {
    --fc-duration-fast: 0ms;
    --fc-duration:      0ms;
    --fc-duration-slow: 0ms;
  }
}
```

### 4.5 Layout

```css
:root {
  /* Container max widths */
  --fc-container-sm:   640px;
  --fc-container-md:   768px;
  --fc-container-lg:   1024px;
  --fc-container-xl:   1280px;

  /* Breakpoints (for JS/MQ consistency) */
  --fc-bp-sm: 640px;
  --fc-bp-md: 768px;
  --fc-bp-lg: 1024px;
  --fc-bp-xl: 1280px;

  /* Z-index scale */
  --fc-z-dropdown:    1000;
  --fc-z-sticky:      1020;
  --fc-z-fixed:       1030;
  --fc-z-modal-bg:    1040;
  --fc-z-modal:       1050;
  --fc-z-popover:     1060;
  --fc-z-tooltip:     1070;
}
```

---

## 5. CSS Architecture: Layers

```css
/* packages/widgets/src/styles/index.css */

@layer reset, base, components, utilities, overrides;

@import "./reset.css"       layer(reset);
@import "./base.css"        layer(base);        /* tokens + :root */
@import "./components/*"    layer(components);  /* card, button, tabs */
@import "./utilities.css"   layer(utilities);   /* helpers */
/* overrides = user theme, empty by default */
```

**Import order guarantees:**
- Tokens available to all components
- Components override base
- Utilities override components
- User overrides win everything without `!important`

---

## 6. Component Library

### 6.1 Catalog (v8.0 target)

| Component | Token classes | WC? | Vue? |
|-----------|---|-----|------|
| `fc-card` | `--fc-radius-lg`, `--fc-shadow` | no | yes |
| `fc-button` | `--fc-color-brand-500` | no | yes |
| `fc-input` | borders, focus ring | no | yes |
| `fc-textarea` | | no | yes |
| `fc-select` | | no | yes |
| `fc-checkbox` | | no | yes |
| `fc-radio` | | no | yes |
| `fc-switch` | | no | yes (replaces bootstrapToggle) |
| `fc-tabs` | indigo active style | no | yes |
| `fc-tab-panel` | | | |
| `fc-badge` | | no | yes |
| `fc-chip` | removable | no | yes |
| `fc-dialog` | modal | no | yes |
| `fc-drawer` | slide-over | no | yes |
| `fc-dropdown` | | no | yes |
| `fc-tooltip` | | | |
| `fc-toast` | notification | no | yes |
| **fc-file-download** | filename, size, lang, download btn | **yes** (WC) | uses WC |
| **fc-address** | map + directions | **yes** (WC) | uses WC |
| **fc-image-gallery** | lightbox | **yes** (WC) | uses WC |
| **fc-weblink** | | **yes** (WC) | uses WC |
| **fc-sharedmedia** | embed player | **yes** (WC) | uses WC |
| **fc-item-card** | featured preview | **yes** (WC) | |
| **fc-grid** | responsive item grid | **yes** (WC) | |

### 6.2 Component CSS Template

```css
/* packages/widgets/src/components/card.css */
@layer components {
  .fc-card {
    background: var(--fc-color-surface);
    border-radius: var(--fc-radius-lg);
    box-shadow: var(--fc-shadow);
    overflow: hidden;
    transition: box-shadow var(--fc-duration) var(--fc-easing-standard),
                transform var(--fc-duration) var(--fc-easing-standard);
  }
  .fc-card[data-interactive="true"]:hover {
    box-shadow: var(--fc-shadow-md);
    transform: translateY(-2px);
  }
  .fc-card__header {
    padding: var(--fc-space-4) var(--fc-space-6);
    border-bottom: 1px solid var(--fc-color-border);
  }
  .fc-card__body { padding: var(--fc-space-6); }
  .fc-card__footer {
    padding: var(--fc-space-4) var(--fc-space-6);
    background: var(--fc-color-bg-subtle);
    border-top: 1px solid var(--fc-color-border);
  }
}
```

### 6.3 Web Component Example

```typescript
// packages/widgets/src/components/fc-file-download.ts
export class FcFileDownload extends HTMLElement {
  static observedAttributes = ['name', 'size', 'url', 'lang', 'mime'];

  connectedCallback() {
    this.attachShadow({ mode: 'open' });
    this.render();
  }

  attributeChangedCallback() { this.render(); }

  private render() {
    const name = this.getAttribute('name') ?? '';
    const size = this.getAttribute('size') ?? '';
    const url  = this.getAttribute('url')  ?? '#';
    const lang = this.getAttribute('lang') ?? '';

    this.shadowRoot!.innerHTML = `
      <style>
        :host { display: block; }
        .wrap {
          display: grid;
          grid-template-columns: auto 1fr auto auto;
          gap: var(--fc-space-3);
          align-items: center;
          padding: var(--fc-space-3) var(--fc-space-4);
          background: var(--fc-color-bg-subtle);
          border: 1px solid var(--fc-color-border);
          border-left: 4px solid var(--fc-color-brand-500);
          border-radius: var(--fc-radius-md);
        }
        .icon {
          width: 36px; height: 36px;
          background: var(--fc-color-brand-500);
          border-radius: var(--fc-radius);
        }
        .name {
          font-weight: var(--fc-font-weight-semibold);
          color: var(--fc-color-text);
        }
        .size {
          font-size: var(--fc-text-sm);
          color: var(--fc-color-text-muted);
        }
        .lang-badge {
          padding: var(--fc-space-1) var(--fc-space-2);
          background: var(--fc-color-brand-100);
          color: var(--fc-color-brand-700);
          border-radius: var(--fc-radius-full);
          font-size: var(--fc-text-xs);
          font-weight: var(--fc-font-weight-bold);
          text-transform: uppercase;
        }
        .download {
          background: var(--fc-color-success);
          color: #fff;
          padding: var(--fc-space-2) var(--fc-space-4);
          border: 0;
          border-radius: var(--fc-radius-md);
          font-weight: var(--fc-font-weight-semibold);
          cursor: pointer;
        }
      </style>
      <div class="wrap">
        <div class="icon" aria-hidden="true"></div>
        <div>
          <div class="name">${this.escape(name)}</div>
          ${size ? `<div class="size">${this.escape(size)}</div>` : ''}
        </div>
        ${lang ? `<span class="lang-badge">${this.escape(lang)}</span>` : '<span></span>'}
        <a class="download" href="${this.escape(url)}" download>Download</a>
      </div>
    `;
  }

  private escape(s: string): string {
    return s.replace(/[<>"&]/g, c => ({
      '<': '&lt;', '>': '&gt;', '"': '&quot;', '&': '&amp;'
    }[c]!));
  }
}

customElements.define('fc-file-download', FcFileDownload);
```

**Usage in template:**
```html
<fc-file-download
  name="photo.png"
  size="842 KB"
  url="/download/32/41"
  lang="EN"
></fc-file-download>
```

---

## 7. Theming

### 7.1 Theme Tokens File

```css
/* packages/widgets/src/themes/default.css */
[data-fc-theme="default"] {
  --fc-color-brand-500: #6366f1;
  --fc-radius-card:     12px;
}

/* packages/widgets/src/themes/corporate.css */
[data-fc-theme="corporate"] {
  --fc-color-brand-500: #1e40af;
  --fc-radius-card:     4px;
  --fc-font-family-sans: "Inter", sans-serif;
}

/* packages/widgets/src/themes/playful.css */
[data-fc-theme="playful"] {
  --fc-color-brand-500: #ec4899;
  --fc-radius-card:     24px;
  --fc-shadow:          0 8px 32px rgba(236,72,153,0.15);
}
```

### 7.2 Theme Switcher
```html
<html data-fc-theme="corporate">
```

**Admin UI control:**
```vue
<FcSelect v-model="theme" :options="['default', 'corporate', 'playful']" />
```

### 7.3 Template-level Override
Template ทุกตัวสามารถ override ได้ผ่าน:
```css
/* my-template/css/brand.css */
@layer overrides {
  :root {
    --fc-color-brand-500: #your-brand;
    --fc-font-family-sans: "Your Font", sans-serif;
  }
}
```

---

## 8. Migration Strategy (v6 → v7)

### 8.1 Phase 1: Tokens Only (v7.0)
- Ship `fc-tokens.css` as first-load asset
- Templates can optionally use vars
- Existing CSS files unchanged → backward compat

### 8.2 Phase 2: Component Library (v7.5)
- Ship `fc-components.css` + Web Components
- Templates can use new components alongside legacy HTML
- Deprecation notice on legacy CSS files

### 8.3 Phase 3: Template Refresh (v8.0)
- Bundled templates rewritten to use component library
- Legacy templates still work (CSS fallback)
- Migration guide for template authors

### 8.4 Legacy CSS Mapping
Create `fc-legacy-shim.css` — maps legacy classes to tokens:
```css
/* legacy → token mapping for gradual migration */
.fcitems       { background: var(--fc-color-surface); border-radius: var(--fc-radius-lg); }
.fc_item_title { color: var(--fc-color-text); font-size: var(--fc-text-2xl); }
.flexi.label   { color: var(--fc-color-text-muted); text-transform: uppercase; }
/* ... */
```

User can opt-in via config: `use_design_system_shim = 1`

---

## 9. Tooling

### 9.1 Build Pipeline
```
packages/widgets/
├── src/
│   ├── tokens/       → compiled to fc-tokens.css
│   ├── components/   → fc-components.css + ESM
│   └── themes/       → fc-theme-*.css
├── vite.config.ts    (lib mode)
└── package.json

Output:
dist/
├── fc-tokens.css        (~3 KB)
├── fc-components.css    (~12 KB)
├── fc-components.js     (Web Components bundle, ~18 KB gzip)
├── fc-theme-default.css
└── manifest.json        (hash map for cache busting)
```

### 9.2 Token Pipeline
Source: JSON tokens (design tool friendly) → CSS / TS / JSON
```json
// design-tokens.json
{
  "color": {
    "brand": {
      "500": { "value": "#6366f1" }
    }
  }
}
```
Tool: **Style Dictionary** (Amazon) → generates CSS vars + TS constants + Figma tokens

### 9.3 Linting
- **Stylelint** + `stylelint-config-standard` + custom rule: require `var(--fc-*)` for colors/spacing
- **PostCSS** + `postcss-preset-env` for future CSS features
- **Lightning CSS** for minification

### 9.4 Visual Regression
- **Storybook** with Chromatic (or Percy)
- Snapshot per component per theme per viewport
- Gate on PR

---

## 10. Accessibility

### 10.1 Defaults
- All color combinations AA (4.5:1 text, 3:1 UI)
- Focus rings: `outline: 2px solid var(--fc-color-brand-500); outline-offset: 2px;`
- Keyboard navigation on all interactive components
- `aria-*` attributes per ARIA APG patterns
- Reduced motion respect

### 10.2 Testing
- **axe-core** in every Storybook story
- Playwright a11y tests per major component
- Color contrast verified by Lightning CSS plugin

---

## 11. Dark Mode

### 11.1 Strategy
1. **Auto** — via `prefers-color-scheme` (default)
2. **Manual** — `<html data-fc-color-scheme="dark">`
3. **Per-template** — some templates may force one mode

### 11.2 Implementation
All color tokens have light + dark values:
```css
:root {
  color-scheme: light;
  --fc-color-bg: #fff;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-fc-color-scheme="light"]) {
    color-scheme: dark;
    --fc-color-bg: #0f172a;
  }
}
[data-fc-color-scheme="dark"] {
  color-scheme: dark;
  --fc-color-bg: #0f172a;
}
```

### 11.3 Image Handling
- `<img>` with `<picture>` + `prefers-color-scheme` media source
- Logo variants in assets
- Map tiles: switch tile server per mode (OSM light/dark)

---

## 12. Performance Budget

| Asset | Budget (gzip) |
|-------|---------------|
| `fc-tokens.css` | < 3 KB |
| `fc-components.css` (all) | < 15 KB |
| `fc-components.js` (all WC) | < 25 KB |
| Critical CSS (inline) | < 10 KB |
| Total first paint | < 50 KB |

Enforced by **size-limit** in CI.

---

## 13. Success Criteria

- [ ] 100% of bundled templates use design tokens (no hardcoded colors)
- [ ] 0 `!important` outside `overrides` layer
- [ ] Dark mode works on all bundled templates
- [ ] Token docs site (Storybook + Storybook Docs)
- [ ] Migration guide for template authors
- [ ] Lighthouse ≥ 95 on all scores (default template)
- [ ] axe-core: 0 violations per component
- [ ] Bundle size within budget

---

## 14. Open Questions

1. **Tailwind vs plain CSS** — ใน admin UI ใช้ Tailwind ได้ไหม? (dev UX ดีกว่า, bundle ใหญ่กว่า)
2. **Emotion / Styled / CSS Modules** — กับ Vue admin? หรือ plain CSS vars?
3. **Shoelace / Radix** — ใช้ library เท่าที่ใช้ได้หรือ build เองหมด?
4. **Icon system** — Lucide / Heroicons / custom SVG sprite?
5. **Font loading** — self-host หรือ CDN? Thai fonts?
6. **RTL support** — scope ของ v7 หรือเลื่อน v8?
7. **CSS-in-JS** สำหรับ component-scoped styles — Shadow DOM เพียงพอไหม?

---

## 15. References

- [Design Tokens Community Group](https://www.designtokens.org/)
- [Style Dictionary](https://amzn.github.io/style-dictionary/)
- [Cascade Layers (MDN)](https://developer.mozilla.org/en-US/docs/Web/CSS/@layer)
- [Open Props](https://open-props.style/)
- [Radix Colors](https://www.radix-ui.com/colors)
- [Tailwind Design System](https://tailwindcss.com/docs/theme)
- [ARIA APG](https://www.w3.org/WAI/ARIA/apg/)
- RFC-001 §6 Phase 1 (tokens roadmap)
- RFC-002 §8 (Asset Bundle integration)
