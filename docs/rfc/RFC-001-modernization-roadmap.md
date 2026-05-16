# RFC-001: FLEXIContent Modernization Roadmap

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Author** | @pisan (maintainer) |
| **Created** | 2026-04-18 |
| **Target** | v7.0 – v8.0 |
| **Supersedes** | – |

---

## 1. Summary

แผนอัพเกรด FLEXIContent จาก v6.x (stable, Joomla 3/4/5 compat) ไปสู่ **v7.x (modern stack)** และ **v8.x (headless-ready)** โดย **คงความสามารถในการอัพเกรดจาก installation เดิมได้** ห้ามสร้าง breaking change โดยไม่มี migration path

## 2. Motivation

**ปัญหาที่พบใน v6.x (audit ล่าสุด):**
- Legacy jQuery plugins (bootstrapToggle, jQuery UI sortable) → แตกใน J5
- CSS กระจาย 50+ ไฟล์ ไม่มี design tokens → inject inline styles ในทุก template
- Field plugins แต่ละตัวมีโค้ด PHP/JS/CSS/XML ซ้ำซ้อน (800+ บรรทัด/ตัว)
- Security smell: `eval()` ใน addressint widget, `getimagesize()` remote URL
- ไม่มี REST/GraphQL API → integration ยาก
- Admin UI ยังเป็น Joomla 3 era
- `renderPositions()` + `getFieldsByPositions()` มี magic string conversion
- SQL concatenation mixed ใน view → ยาก test/cache/secure

**เป้าหมาย:**
- Security & performance fixes
- Modern DX (TS, Vite, PHPStan, Playwright)
- Headless-ready architecture
- UI/UX ทันสมัย
- **ไม่ทิ้ง users v6.x**

## 3. Goals & Non-Goals

### Goals
- ✅ In-place upgrade จาก v6.x ผ่าน Joomla Installer
- ✅ DB schema backward-compatible (ADD-only, ไม่ DROP/RENAME)
- ✅ Old field plugins ทำงานได้ผ่าน compat adapter
- ✅ REST API + JSON schema (additive, ไม่กระทบ rendering เดิม)
- ✅ Admin UI modernization แบบ opt-in (user เลือกเปิดปิด)
- ✅ PHP 8.3 + Joomla 5 minimum (v7.0)
- ✅ CSS design tokens + component library

### Non-Goals
- ❌ ไม่ rewrite from scratch
- ❌ ไม่เปลี่ยน DB table names ก่อน v9.0
- ❌ ไม่ตัด field plugin เดิมโดยไม่ deprecation cycle 2 major versions
- ❌ ไม่ย้าย repo ออกจาก GitHub org เดิม
- ❌ ไม่ rebrand ก่อน v8.0

---

## 4. Versioning & Naming

### 4.1 Semantic Versioning Policy
```
v6.x    stable, J3/4/5 compat, bug fix only
v7.x    modern foundation, J5+ only, additive features
v8.x    Field SDK v2, headless mode, opt-in modern admin stable
v9.x    (tentative) rebrand consideration
```

### 4.2 Branding
**Proposal: keep `FLEXIContent` + sub-brand edition**

| Edition | Target User |
|---------|------------|
| **FLEXIContent Classic** | v6.x LTS (security patches 2 ปี) |
| **FLEXIContent Flux** *(NEW)* | v7.x+ modern edition |

- Keep brand equity ของ FLEXIContent (SEO, docs, JED listing)
- `Flux` เป็น edition marker → สื่อถึง dynamic/modern
- Package XML identifier: `com_flexicontent` (unchanged)

### 4.3 Repository Layout
**Same repo, branch-based:**
```
github.com/lungkao/flexicontent-cck
├── main          → v6.x (current stable)
├── v7-dev        → active development (this RFC)
├── v8-next       → future (after v7.0 stable)
└── gh-pages      → docs site
```

---

## 5. Compatibility & Migration Policy

### 5.1 Breaking Change Rules
- **NO breaking change** within a major (x.y.z → x.y.z+n, x.y → x.y+n)
- Major bumps (x → x+1) ต้องมี:
  - Migration script in `admin/sql/upgrade/`
  - Upgrade notes (`docs/upgrade/v{N}.md`)
  - Deprecation warnings ใน version ก่อนหน้า (อย่างน้อย 1 minor)
  - Automated test สำหรับ upgrade path

### 5.2 Deprecation Cycle
```
v7.0  → mark as @deprecated, ยังทำงานปกติ, PHP error_log warning
v7.5  → ขึ้น admin notice ใน backend
v8.0  → ยังทำงาน แต่ log warning ทุก call
v9.0  → ลบจริง (ถ้า adoption rate < 5%)
```

### 5.3 DB Migration Policy
- ✅ `ALTER TABLE ADD COLUMN` — ได้
- ✅ `CREATE TABLE` ใหม่ — ได้
- ✅ `CREATE INDEX` — ได้
- ❌ `DROP COLUMN` — ไม่ได้ก่อน v9.0
- ❌ `RENAME TABLE` — ไม่ได้ก่อน v9.0
- ⚠️ Data migration ต้อง idempotent + resumable

### 5.4 Field Plugin Compat
Old field plugins (v6 API) ทำงานต่อผ่าน adapter:
```php
namespace FLEXIcontent\Fields\Compat;

/**
 * Wraps legacy v6 plugin class to implement new v7 FieldType interface.
 * Auto-registered by the extension manager for any plugin extending
 * \FCField (old base class).
 */
class LegacyFieldAdapter implements FieldType {
    public function __construct(private \FCField $legacyPlugin) {}

    public function schema(): FieldSchema { /* reflect legacy XML */ }
    public function render(Context $ctx): View { /* call onDisplayFieldValue */ }
    public function apiSerialize(Value $v): array { /* default normalizer */ }
}
```

---

## 6. Roadmap & Milestones

### Phase 1 — Foundation (v6.2 → v7.0-alpha)
**Timeline: 2-3 เดือน | Risk: LOW | Breaking: NONE**

- [ ] Remove dead jQuery plugins (bootstrapToggle → native, jQuery UI sortable → @dnd-kit)
- [ ] Security fixes:
  - [ ] ลบ `eval()` ใน `addressint` widget (ใช้ data-attributes + JSON.parse)
  - [ ] Guard `getimagesize()` remote URL
  - [ ] CSRF token refresh ใน AJAX endpoints
- [ ] Build pipeline:
  - [ ] Add `pnpm` + Vite config
  - [ ] Auto-generate `.min.css` / `.min.js` จาก source
  - [ ] Source maps สำหรับ JDEBUG
- [ ] CSS design tokens:
  ```css
  :root {
    --fc-color-primary: #6366f1;
    --fc-color-success: #10b981;
    --fc-radius-card: 12px;
    --fc-shadow-sm: 0 1px 6px rgba(0,0,0,.07);
    --fc-shadow-md: 0 2px 14px rgba(0,0,0,.08);
    --fc-space-1 ... --fc-space-8;
  }
  ```
  ใช้ `@layer base, components, utilities` จัดการ override
- [ ] PHP 8.3 strict types in new code (additive)
- [ ] PHPStan level 5 baseline

### Phase 2 — REST API v1 (v7.0)
**Timeline: 2-3 เดือน | Risk: LOW | Breaking: NONE (additive)**

- [ ] Joomla Web Services ext: `com_flexicontent_api`
- [ ] Endpoints:
  ```
  GET    /api/index.php/v1/fcitems
  GET    /api/index.php/v1/fcitems/{id}
  POST   /api/index.php/v1/fcitems
  PATCH  /api/index.php/v1/fcitems/{id}
  DELETE /api/index.php/v1/fcitems/{id}
  GET    /api/index.php/v1/fctypes
  GET    /api/index.php/v1/fcfields
  POST   /api/index.php/v1/fcfields/{id}/render
  ```
- [ ] Auth: Joomla tokens + API keys + OAuth2 (optional)
- [ ] JSON schema endpoint (`/api/schema/{type}`)
- [ ] OpenAPI 3 spec auto-generated
- [ ] Rate limiting + CORS config

### Phase 3 — Modern Admin (Opt-in) (v7.5)
**Timeline: 4-6 เดือน | Risk: MEDIUM | Breaking: LOW**

- [ ] Config toggle: `admin_ui_version = legacy | modern`
- [ ] Vue 3 + TypeScript admin UI:
  - [ ] Field widget components (text, image, file, address, etc.)
  - [ ] Form builder (JSON-schema → UI)
  - [ ] Drag-drop field ordering (@dnd-kit/vue)
  - [ ] Item edit view (chunked field loading)
- [ ] Keep legacy admin ทำงานได้ 100%
- [ ] Storybook สำหรับ component catalog

### Phase 4 — Field SDK v2 (v8.0)
**Timeline: 6+ เดือน | Risk: MEDIUM | Breaking: MEDIUM (via adapter)**

- [ ] New `FieldType` interface (PHP 8.3 typed)
- [ ] Field value storage: MySQL JSON columns (additive column, old column ยังเก็บ)
- [ ] TS type generation จาก PHP schema
- [ ] Web Components สำหรับ public rendering (`<fc-file>`, `<fc-address>`, `<fc-gallery>`)
- [ ] Compat adapter สำหรับ v6/v7 plugins
- [ ] Migration CLI:
  ```bash
  php cli/flexi fields:migrate-to-json --dry-run
  ```

### Phase 5 — Headless Mode (v8.x)
**Timeline: 3-6 เดือน | Risk: LOW | Breaking: NONE**

- [ ] GraphQL endpoint (optional package `com_flexicontent_graphql`)
- [ ] Webhook system (item published/updated/deleted)
- [ ] Next.js/Nuxt starter kits in `examples/`
- [ ] CDN-friendly cache headers + ETag

### Phase 6 — AI-native Features (v8.x+)
**Timeline: ongoing | Risk: LOW | Breaking: NONE**

- [ ] Semantic search (pgvector via MariaDB 11 vector / Meilisearch)
- [ ] Auto-tagging via embeddings
- [ ] Translation API integration
- [ ] Schema copilot (describe → AI generates Field config)

---

## 7. Architecture Changes

### 7.1 Current (v6.x)
```
┌─────────────────────────────────────────┐
│  Joomla MVC                             │
│  ┌────────────────────────────────────┐ │
│  │ FLEXIContent Component             │ │
│  │  - Model (direct SQL)              │ │
│  │  - View (PHP includes)             │ │
│  │  - Controller                      │ │
│  │  - Field Plugins (800 LOC each)    │ │
│  └────────────────────────────────────┘ │
│       ↕                                  │
│  ┌────────────────────────────────────┐ │
│  │ jQuery 1.x + jQuery UI             │ │
│  │ Bootstrap 2/3/5 (mixed)            │ │
│  │ Inline <style> injection           │ │
│  └────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### 7.2 Target (v8.0)
```
┌───────────────────────────────────────────────────────┐
│  Joomla MVC (Extension Host)                          │
│                                                        │
│  ┌─────────────────────────────────────────────────┐  │
│  │ FLEXIContent Core (PHP 8.3)                     │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────────┐  │  │
│  │  │ Domain   │  │ Repo     │  │ API Layer    │  │  │
│  │  │ (typed)  │◄─┤ (Doctrine│◄─┤ REST/GraphQL │  │  │
│  │  │          │  │  DBAL)   │  │              │  │  │
│  │  └──────────┘  └──────────┘  └──────────────┘  │  │
│  │       ▲                             ▲           │  │
│  │       │                             │           │  │
│  │  ┌────┴─────┐                  ┌────┴──────┐    │  │
│  │  │ Field    │                  │ Renderer  │    │  │
│  │  │ SDK v2   │                  │ (Twig?)   │    │  │
│  │  │ + Compat │                  │           │    │  │
│  │  └──────────┘                  └───────────┘    │  │
│  └─────────────────────────────────────────────────┘  │
│       ▲                                                │
│       │ HTTP/JSON                                      │
│  ┌────┴─────────────────────────────────────────────┐ │
│  │ Admin UI (Vue 3 + TS, Vite build)                │ │
│  │ Public Widgets (Web Components, Alpine/HTMX)     │ │
│  └──────────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────────┘
       ▲                        ▲
       │                        │
  Joomla Frontend          External Clients
  (templates)              (Next, Nuxt, mobile)
```

### 7.3 Package Split (v8.0 Monorepo-in-repo)
```
/
├── administrator/components/com_flexicontent/   (Joomla admin)
├── components/com_flexicontent/                  (Joomla frontend)
├── plugins/flexicontent_fields/                  (field plugins)
├── packages/                                     (NEW)
│   ├── core/          PHP domain + repo
│   ├── api/           REST + GraphQL
│   ├── field-sdk/     interfaces + adapters
│   ├── admin-ui/      Vue 3 + TS
│   └── widgets/       Web Components
├── docs/
│   ├── rfc/
│   ├── upgrade/
│   └── api/
├── tests/
│   ├── unit/          (PHPUnit)
│   ├── integration/   (DB fixtures)
│   └── e2e/           (Playwright)
├── composer.json
├── package.json       (pnpm workspace)
├── pnpm-workspace.yaml
└── vite.config.ts
```

---

## 8. Migration Spec

### 8.1 Upgrade Detection
ใน `script.flexicontent.php` (Joomla install script):
```php
public function preflight($type, $parent) {
    if ($type === 'update') {
        $from = $this->getInstalledVersion();
        if (version_compare($from, '6.0.0', '<')) {
            throw new \RuntimeException(
                'Direct upgrade from pre-6.0 not supported. '.
                'Please upgrade to 6.x first.'
            );
        }
        $this->detectLegacyPlugins();
    }
}
```

### 8.2 DB Migration Scripts
```
admin/sql/upgrade/
├── 6.1.0.sql            (existing)
├── 7.0.0.sql            (new)
│   -- ADD COLUMN fields.json_schema JSON NULL
│   -- ADD COLUMN items.json_values JSON NULL
│   -- CREATE INDEX ft_items_search ON items(title, introtext, fulltext) FULLTEXT
├── 7.0.0-data.php       (PHP data migration, idempotent)
│   // backfill json_schema from existing attribs
└── 8.0.0.sql            (future)
```

### 8.3 Upgrade Wizard UI
`Components → FLEXIContent → Upgrade Wizard`:
1. Pre-flight check (PHP, J version, extensions)
2. Backup reminder (link to Akeeba)
3. Impact analysis (fields, templates, plugins ที่อาจ break)
4. Preview diff
5. Apply migrations
6. Verify + rollback option

### 8.4 Rollback Strategy
- ทุก migration script มี `down.sql` คู่กัน
- Admin UI: "Rollback to v6.1.x" (within 7 days after upgrade)
- Audit log: บันทึก migration history ใน `h4cb0_flexicontent_migrations`

---

## 9. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Legacy field plugins break | HIGH | Compat adapter + extensive test matrix |
| CSS tokens conflict กับ user overrides | MEDIUM | CSS layers, `!important` budget, docs |
| PHP 8.3 requirement ตัด user | MEDIUM | v6.x LTS continues, migration guide |
| Admin UI regression | HIGH | Opt-in toggle, dual-UI period 2+ majors |
| DB migration corrupts data | CRITICAL | Dry-run mode, audit log, rollback, backup reminder |
| Contributor churn | MEDIUM | Clear RFC process, good-first-issue labels |
| Joomla core API changes | HIGH | Follow Joomla LTS, quarterly compat checks |

---

## 10. Success Metrics

**v7.0 ship criteria:**
- [ ] In-place upgrade จาก v6.1.x success rate ≥ 99% (tested on top 20 JED templates)
- [ ] PHPStan level 5 on new code, level 3 on legacy
- [ ] Unit test coverage ≥ 60% on core, 80% on new packages
- [ ] Lighthouse score ≥ 90 บน default templates
- [ ] Zero P0 security issues open > 30 days
- [ ] REST API docs + 3+ SDK examples (curl, JS, PHP)

**v8.0 ship criteria:**
- [ ] Field SDK v2 adoption: ≥ 50% of bundled fields migrated
- [ ] Compat adapter: 100% backward compat with v7.x field plugins
- [ ] Headless example: Next.js + Nuxt starters live
- [ ] Community: ≥ 10 external contributors in 12 months

---

## 11. Open Questions

1. **Twig vs PHP templates** — v8 renderer จะใช้อะไร? (Twig = cleaner, PHP = no new dep)
2. **Composer autoload** — จะ ship vendor/ ใน package หรือให้ user `composer install`?
3. **Doctrine DBAL vs Joomla DB** — migrate ทั้งหมดหรือ coexist?
4. **Storage: single JSON column vs normalized** — trade-off query performance vs schema flex
5. **MapLibre vs Leaflet** — MapLibre ดีกว่าแต่ bundle ใหญ่กว่า
6. **Vue 3 vs Lit/Preact** สำหรับ admin — Joomla core ใช้ Vue แต่ Lit เบากว่า
7. **GraphQL**: own resolver หรือ integrate com_api?
8. **LTS policy v6.x**: 2 ปี หรือ 3 ปี security patches?

---

## 12. Decision Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-04-18 | Keep same repo + branch strategy | Preserve git history, JED listing, SEO |
| 2026-04-18 | Brand: `FLEXIContent Flux` edition | Keep brand equity + signal modern |
| 2026-04-18 | Must support upgrade from v6.x | User requirement |
| 2026-04-18 | No DROP/RENAME tables until v9.0 | Safe upgrade path |

---

## 13. References

- [Joomla 5 Development](https://docs.joomla.org/Portal:Joomla_5.x)
- [Directus architecture](https://directus.io/docs/self-hosted/architecture)
- [Strapi field types](https://docs.strapi.io/user-docs/content-manager)
- [WordPress Gutenberg block protocol](https://developer.wordpress.org/block-editor/)
- [Current v6.x codebase audit](../../README.md) *(TODO: link)*

---

## 14. Changelog

- **2026-04-18**: Initial draft (v0.1)

---

## 15. Next Steps

1. 🟡 **Review period** — 2 สัปดาห์ สำหรับ community feedback
2. 🟡 Create RFC-002: Field SDK v2 detailed spec
3. 🟡 Create RFC-003: REST API v1 endpoint spec
4. 🟡 Create RFC-004: CSS design tokens + component library spec
5. 🟢 Spike: Vite + pnpm integration (1-week timebox)
6. 🟢 Spike: REST API prototype (1 endpoint end-to-end)
7. 🟢 Create `v7-dev` branch หลัง RFC approve

---

*Comments, objections, alternatives welcome — ขอเชิญ review ที่ GitHub Discussions*
