# RFC-002: Field SDK v2

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Author** | @pisan |
| **Created** | 2026-04-18 |
| **Target** | v8.0 (design phase begins v7.0) |
| **Depends on** | RFC-001 |

---

## 1. Summary

Field Plugin SDK v2 — interface-based, typed, storage-agnostic สำหรับ FLEXIContent Flux.  
**เป้าหมาย:** ลดโค้ดซ้ำในแต่ละ plugin จาก ~800 LOC เหลือ ~200 LOC, แยก concerns (domain/render/storage/API), รองรับ TypeScript generation, ยังคง backward compat ผ่าน adapter

## 2. Problem Statement

**Field plugin v6 (current):**
- Monolithic class: logic + rendering + SQL + JS ทั้งหมดใน `plugin.php` ตัวเดียว
- `onDisplayFieldValue($field, $item, $values, $prop)` — polymorphic, กำหนด contract ไม่ชัด
- Field config (XML) แยกกับ field schema (DB) — sync เองตลอด
- ไม่มี typed value object → ทุกอย่างเป็น array
- Template override ต้อง copy ทั้ง `tmpl/value.php` มาแก้
- ไม่มี API serializer → REST endpoint ต้อง re-invent per field
- JS/CSS assets hard-coded paths, ไม่ tree-shake

## 3. Design Principles

1. **Separation of concerns** — Domain / Storage / Render / API แยกชัด
2. **Strict types** — PHP 8.3 readonly + enums + typed properties
3. **Schema-first** — declare once, generate XML + TS + validation
4. **Compat-first** — v6 plugins ทำงานผ่าน adapter, ไม่ต้องแก้
5. **Testable** — ทุก class มี contract, mock ได้
6. **Framework-agnostic core** — ไม่ผูก Joomla ใน domain layer

---

## 4. Core Interfaces

### 4.1 FieldType (entry point)

```php
<?php
namespace FLEXIcontent\Fields\Contracts;

interface FieldType
{
    /** Unique identifier, e.g. "text", "image", "addressint" */
    public function key(): string;

    /** Human-readable label (localized) */
    public function label(): string;

    /** Schema for validation + form generation */
    public function schema(): FieldSchema;

    /** Storage adapter (JSON column, file table, etc.) */
    public function storage(): FieldStorage;

    /** Renderer for public display */
    public function renderer(): FieldRenderer;

    /** API serialization (REST/GraphQL) */
    public function serializer(): FieldSerializer;

    /** Admin input component (TS path, e.g. "fields/text/Input.vue") */
    public function adminComponent(): ?string;

    /** Public Web Component tag (e.g. "fc-text"), null = no WC */
    public function webComponent(): ?string;

    /** Lifecycle hooks (optional) */
    public function hooks(): FieldHooks;

    /** Asset bundle declaration */
    public function assets(): AssetBundle;
}
```

### 4.2 FieldSchema

```php
final readonly class FieldSchema
{
    public function __construct(
        public ValueType $valueType,           // enum: String|Number|Bool|Array|Object|Binary
        public Cardinality $cardinality,        // enum: One|Many|Indexed
        public array $properties,               // PropertyDef[]
        public array $validators,               // Validator[]
        public ?array $uiHints = null,          // for form generation
        public ?array $computedColumns = null,  // MySQL computed for indexing
    ) {}
}

final readonly class PropertyDef
{
    public function __construct(
        public string $name,
        public ValueType $type,
        public bool $required = false,
        public mixed $default = null,
        public ?string $label = null,
        public ?string $description = null,
        public array $constraints = [],  // min/max/pattern
    ) {}
}

enum ValueType: string {
    case String  = 'string';
    case Number  = 'number';
    case Integer = 'integer';
    case Bool    = 'boolean';
    case Date    = 'date';
    case DateTime = 'datetime';
    case Array   = 'array';
    case Object  = 'object';
    case Binary  = 'binary';   // file ref
}

enum Cardinality: string {
    case One     = 'one';
    case Many    = 'many';     // repeatable, unordered
    case Indexed = 'indexed';  // repeatable, ordered (valueorder)
}
```

### 4.3 FieldValue (runtime value object)

```php
final readonly class FieldValue
{
    public function __construct(
        public int $fieldId,
        public int $itemId,
        public int $valueOrder,
        public mixed $data,          // typed per schema
        public ?array $metadata = null,
    ) {}

    public function asArray(): array { /* ... */ }
    public function asJson(): string { /* ... */ }
}
```

### 4.4 FieldStorage

```php
interface FieldStorage
{
    /** Read values for items, optionally filtered */
    public function read(array $itemIds, ?QueryOptions $opts = null): ValueCollection;

    /** Write values (upsert) */
    public function write(int $itemId, array $values): void;

    /** Delete values for an item */
    public function delete(int $itemId): void;

    /** Return MySQL DDL for this storage (tables, indexes, computed columns) */
    public function ddl(): DDLStatements;

    /** Index for full-text/faceted search */
    public function indexForSearch(FieldValue $v): array;
}
```

**Built-in storages:**
- `JsonColumnStorage` — stores in `items.json_values->$.fieldname`
- `RelationTableStorage` — legacy `fields_item_relations` table (for compat)
- `ExternalTableStorage` — for files/images with their own table

### 4.5 FieldRenderer

```php
interface FieldRenderer
{
    public function render(FieldValue $value, RenderContext $ctx): RenderResult;
}

final readonly class RenderContext
{
    public function __construct(
        public string $view,         // 'item' | 'category' | 'module' | 'api'
        public string $layout,       // layout name
        public array $params,        // merged type + field params
        public Item $item,
        public ?User $user = null,
    ) {}
}

final readonly class RenderResult
{
    public function __construct(
        public string $html,
        public array $inlineAssets = [],    // css/js to inject
        public array $dependencies = [],    // asset bundle keys
        public ?string $ariaLabel = null,
        public ?array $microdata = null,
    ) {}
}
```

### 4.6 FieldSerializer

```php
interface FieldSerializer
{
    /** Convert FieldValue → API-safe array (REST/GraphQL) */
    public function serialize(FieldValue $v, SerializeContext $ctx): array;

    /** Parse API input → FieldValue (POST/PATCH) */
    public function deserialize(mixed $input, SerializeContext $ctx): FieldValue;

    /** JSON schema for this field (OpenAPI) */
    public function jsonSchema(): array;
}
```

### 4.7 FieldHooks

```php
interface FieldHooks
{
    public function beforeSave(FieldValue $v, Item $item): FieldValue;
    public function afterSave(FieldValue $v, Item $item): void;
    public function beforeDelete(int $itemId): void;
    public function onItemClone(FieldValue $v, Item $source, Item $target): FieldValue;
    public function onTrash(int $itemId): void;
    public function onRestore(int $itemId): void;
}
```

---

## 5. Example: TextField Rewrite

**v6 (current): ~600 LOC in `text.php`**

**v7/v8 (new):**

```php
// packages/field-sdk/src/Builtin/TextField.php
namespace FLEXIcontent\Fields\Builtin;

use FLEXIcontent\Fields\Contracts\FieldType;
use FLEXIcontent\Fields\Schema\{FieldSchema, ValueType, Cardinality, PropertyDef};

final class TextField implements FieldType
{
    public function key(): string { return 'text'; }

    public function label(): string { return \Joomla\CMS\Language\Text::_('FC_FIELD_TEXT'); }

    public function schema(): FieldSchema {
        return new FieldSchema(
            valueType: ValueType::String,
            cardinality: Cardinality::One,
            properties: [
                new PropertyDef('value', ValueType::String, required: true),
            ],
            validators: [
                new MaxLengthValidator(maxLength: 65535),
            ],
            uiHints: ['widget' => 'textarea', 'rows' => 6],
        );
    }

    public function storage(): FieldStorage {
        return new JsonColumnStorage(column: 'json_values', path: '$.text');
    }

    public function renderer(): FieldRenderer {
        return new TextRenderer();
    }

    public function serializer(): FieldSerializer {
        return new class implements FieldSerializer {
            public function serialize(FieldValue $v, SerializeContext $ctx): array {
                return ['value' => $v->data];
            }
            public function deserialize(mixed $in, SerializeContext $ctx): FieldValue {
                return new FieldValue(/* ... */, data: (string)($in['value'] ?? ''));
            }
            public function jsonSchema(): array {
                return ['type' => 'object', 'properties' => [
                    'value' => ['type' => 'string'],
                ]];
            }
        };
    }

    public function adminComponent(): ?string {
        return 'fields/text/TextInput.vue';
    }

    public function webComponent(): ?string {
        return null; // plain HTML is enough
    }

    public function hooks(): FieldHooks {
        return new NoopHooks();
    }

    public function assets(): AssetBundle {
        return AssetBundle::empty();
    }
}
```

**Result:** ~80 LOC (plus small `TextRenderer` class)

---

## 6. Compat Adapter (v6 → v7)

```php
namespace FLEXIcontent\Fields\Compat;

use FLEXIcontent\Fields\Contracts\FieldType;

/**
 * Wraps legacy FCField plugin class to new FieldType interface.
 * Auto-detected by PluginRegistry when legacy plugin found.
 */
final class LegacyFieldAdapter implements FieldType
{
    public function __construct(
        private readonly \FCField $legacy,
        private readonly LegacyFieldMeta $meta,
    ) {}

    public function key(): string {
        return $this->meta->fieldType;
    }

    public function schema(): FieldSchema {
        // Reflect legacy XML config into modern schema
        return LegacyXmlReflector::reflect($this->meta->xmlPath);
    }

    public function storage(): FieldStorage {
        return new RelationTableStorage(table: '#__flexicontent_fields_item_relations');
    }

    public function renderer(): FieldRenderer {
        return new LegacyRenderer($this->legacy);
        // ↓ internally calls:
        // $this->legacy->onDisplayFieldValue($field, $item, $values, $prop)
    }

    public function serializer(): FieldSerializer {
        return new DefaultSerializer();  // best-effort, override per plugin later
    }

    public function adminComponent(): ?string {
        return 'fields/compat/LegacyIframe.vue';  // renders legacy admin HTML in iframe
    }

    public function hooks(): FieldHooks {
        return new LegacyHooksAdapter($this->legacy);
        // ↓ proxies to onBeforeSaveField, onAfterSaveField, etc.
    }

    public function assets(): AssetBundle {
        return $this->meta->declaredAssets();
    }
}
```

**Auto-registration:**
```php
// In ServiceProvider
foreach (PluginHelper::getPlugins('flexicontent_fields') as $plugin) {
    $instance = PluginFactory::make($plugin);
    if ($instance instanceof \FCField) {
        $registry->register(new LegacyFieldAdapter($instance, LegacyFieldMeta::from($plugin)));
    } elseif ($instance instanceof FieldType) {
        $registry->register($instance);
    }
}
```

---

## 7. Storage Migration

### 7.1 Dual-read / Single-write Transition

```
Phase 7.0: Old storage only (relation table)
Phase 7.5: Dual-write (relation + JSON), read from relation
Phase 8.0: Dual-write, read from JSON (with fallback)
Phase 8.5: JSON only (migration complete)
Phase 9.0: DROP legacy columns (after 12 months)
```

### 7.2 Migration CLI

```bash
php cli/flexi fields:migrate-storage \
    --from=relation \
    --to=json \
    --batch=1000 \
    --field=text,image \
    --dry-run

# Output:
# → 15,234 items to migrate for field 'text'
# → Estimated time: 4m 20s
# → Conflicts: 0
# → Run without --dry-run to execute
```

### 7.3 Rollback
- Keep `fields_item_relations` table intact until v9.0
- `migration_history` table logs every batch
- CLI: `flexi fields:rollback-storage --migration-id=abc123`

---

## 8. Asset Bundle System

```php
final readonly class AssetBundle
{
    public function __construct(
        public array $scripts = [],       // relative to package/dist/
        public array $styles = [],
        public array $webComponents = [], // custom element names
        public array $dependencies = [],  // other bundle keys
    ) {}

    public static function empty(): self { return new self(); }
}

// Usage in FieldType:
public function assets(): AssetBundle {
    return new AssetBundle(
        scripts: ['fields/address/address.js'],
        styles:  ['fields/address/address.css'],
        webComponents: ['fc-address'],
        dependencies: ['maplibre'],
    );
}
```

**AssetResolver** at render time:
- Collects unique bundle keys across all rendered fields
- De-dupes + topologically sorts by dependency
- Emits `<link>` + `<script type="module">` via WebAssetManager
- Vite manifest → content-hashed URLs

---

## 9. Validation Framework

```php
interface Validator
{
    public function validate(mixed $value, ValidationContext $ctx): ValidationResult;
}

final readonly class ValidationResult
{
    public function __construct(
        public bool $valid,
        public array $errors = [],  // [['path' => 'addr1', 'code' => 'required', 'message' => '...']]
    ) {}
}

// Built-in validators
class RequiredValidator implements Validator { /* ... */ }
class MaxLengthValidator implements Validator { /* ... */ }
class PatternValidator implements Validator { /* ... */ }
class RangeValidator implements Validator { /* ... */ }
class CustomValidator implements Validator {
    public function __construct(private \Closure $fn) {}
}
```

**Integration:**
- Admin form → real-time validation (Vue, WebSocket optional)
- REST API → validate before insert
- CLI import → batch validate, report

---

## 10. TypeScript Type Generation

```bash
php cli/flexi fields:gen-types --out=packages/admin-ui/src/types/fields.d.ts
```

**Generated output:**
```typescript
// Auto-generated from PHP schemas
export interface TextFieldValue {
  value: string;
}

export interface ImageFieldValue {
  file: string;
  thumb?: string;
  alt?: string;
  width?: number;
  height?: number;
}

export interface AddressFieldValue {
  lat?: number;
  lon?: number;
  addr1?: string;
  city?: string;
  /* ... */
}

export type FieldValueMap = {
  text: TextFieldValue;
  image: ImageFieldValue;
  addressint: AddressFieldValue;
  /* ... */
};
```

Consumed by Vue 3 admin widgets + Next.js/Nuxt starters.

---

## 11. Bundled Field Migration Plan

| Field | v6 LOC | v7 Plan | v8 Target |
|-------|--------|---------|-----------|
| text | ~620 | Compat adapter | Native |
| textarea | ~580 | Compat | Native |
| image | ~1420 | Compat | Native |
| file | ~1100 | Compat | Native |
| date | ~480 | Compat | Native |
| select | ~540 | Compat | Native |
| radio | ~380 | Compat | Native |
| checkbox | ~360 | Compat | Native |
| address / addressint | ~1280 / ~1800 | Compat | Native + Web Component |
| relation | ~980 | Compat | Native |
| weblink | ~440 | Compat | Native |
| sharedmedia | ~720 | Compat | Native + Web Component |
| email | ~220 | Compat | Native |
| phone | ~240 | Compat | Native |
| *(all 30+)* | | | |

**Order of native migration (v8.0-v8.5):**
1. text, textarea (simplest)
2. select, radio, checkbox
3. date, email, phone, weblink
4. image, file
5. relation
6. addressint, sharedmedia (complex, Web Components)

---

## 12. Testing Strategy

```
tests/
├── unit/
│   └── fields/
│       ├── TextFieldTest.php          (schema, storage, render, serialize)
│       ├── ImageFieldTest.php
│       └── LegacyAdapterTest.php      (v6 plugin → adapter contract)
├── integration/
│   ├── storage/
│   │   ├── JsonColumnStorageTest.php  (real MySQL, transactions)
│   │   └── DualWriteTest.php          (migration correctness)
│   └── api/
│       └── FieldSerializerTest.php    (roundtrip)
└── e2e/
    └── admin-field-widgets.spec.ts    (Playwright)
```

**Contract tests:**
```php
// Every FieldType must pass these
abstract class FieldTypeContractTest extends TestCase {
    abstract protected function makeField(): FieldType;

    public function testSchemaIsComplete(): void { /* ... */ }
    public function testRoundtripViaSerializer(): void { /* ... */ }
    public function testStorageReadWrite(): void { /* ... */ }
    public function testRendererReturnsValidHtml(): void { /* ... */ }
    public function testHooksAreIdempotent(): void { /* ... */ }
}
```

---

## 13. Success Criteria

- [ ] Compat adapter: 100% of bundled v6 fields work unmodified
- [ ] Native migration: ≥ 50% of bundled fields ported to SDK v2 by v8.0 stable
- [ ] Performance: P95 field render latency < 5ms (new SDK) vs < 20ms (legacy)
- [ ] LOC reduction: average native field < 250 LOC (vs ~700 LOC legacy)
- [ ] Test coverage: ≥ 90% on SDK core, ≥ 70% per bundled field
- [ ] TS types: auto-generated for 100% of native fields

---

## 14. Open Questions

1. **Value storage format** — JSON column single-document vs per-field JSON columns?
2. **Computed columns** — MySQL generated columns สำหรับ full-text index? (requires 5.7+)
3. **Field inheritance** — รองรับ "extends" เช่น `PhoneField extends TextField`?
4. **i18n in schema** — label/description multilingual ใน schema หรือ separate?
5. **Custom storage plugins** — ยอมให้ 3rd party extend `FieldStorage` ไหม?
6. **Cardinality::Many** ใช้อะไรเก็บ order — array index หรือ explicit valueorder?
7. **Breaking event hooks** — Joomla plugin events vs internal FieldHooks?

---

## 15. References

- RFC-001 §5.4 (Field Plugin Compat)
- [JSON Schema spec](https://json-schema.org/)
- [Symfony Validator](https://symfony.com/doc/current/validation.html)
- [Drupal Field API](https://www.drupal.org/docs/drupal-apis/entity-api/field-api)
- [Directus Fields](https://directus.io/docs/guides/data-model/fields)
