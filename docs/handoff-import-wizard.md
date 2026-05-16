# Handoff: CSV Import Single-Page Wizard

**Branch:** `feature/j5-j6-compatibility`  
**Date:** 2026-05-08  
**Status:** ⚠️ Code written — needs browser testing + commit

---

## สรุปสิ่งที่ทำในรอบนี้

เปลี่ยนระบบ Import จาก 3-step (Upload → Map → Process) เป็น **single-page wizard** ที่:

1. เลือกไฟล์ CSV → FileReader parse columns client-side ทันที (ไม่ต้อง upload ก่อน)
2. เลือก Content Type → AJAX โหลด FC fields (`getfieldsajax` task)
3. Mapping table โผล่อัตโนมัติ + auto-map ตามชื่อ
4. กด "Upload & Start Import" → submit ไปที่ `mapinitcsv` พร้อมไฟล์ + col_map ในครั้งเดียว

**รอบก่อนหน้า (commit `e530aecdc`)** → 3-step wizard + 24 PHPUnit tests ✅

---

## ไฟล์ที่แก้ไข (ยัง uncommitted)

### 1. `admin/controllers/import.php`

**เพิ่ม method ใหม่ `getfieldsajax()`** (หลัง `previewcsv()`):
- URL: `?option=com_flexicontent&controller=import&task=getfieldsajax&type_id=N&TOKEN=1`
- ตรวจ CSRF token → query `#__flexicontent_fields` JOIN `#__flexicontent_fields_type_relations`
- ยกเว้น field_type `separator`, `coreprops`
- คืน JSON `{"fields": [{id, name, label, field_type}, ...]}`
- ใช้ `$app->close()` หยุดหลัง echo

**แก้ `mapinitcsv` case ใน `importcsv()`** (~line 480):
- **เดิม:** ถ้าไม่มี session preview → error redirect
- **ใหม่:** ลอง session preview ก่อน (old Step-2 flow) → ถ้าไม่มีหรือ tmpfile หายไป → ใช้ `$_FILES['csvfile']['tmp_name']` แทน (new single-page flow)
- ทั้งสองทาง: col_map มาจาก POST เหมือนกัน
- Backward compatible: Step-2 flow ยังทำงานปกติ

### 2. `admin/views/import/tmpl/import.php`

**Rewrite ทั้งไฟล์** เป็น Alpine.js single-page wizard:

```
Header: Step progress bar (1→2→3→4) แสดง state realtime

Section 1: [📁 CSV File card]  [📋 Content Type card]
           สีเขียวเมื่อ ready ทั้งคู่

Section 2a: Placeholder "Select file + type to begin" (x-show !isReady)

Section 2b: Mapping table (x-show isReady)
           - x-for each csvColumn → select :name="col_map[colName]"
           - options: เส้นแบ่ง + core props + custom fields
           - แถวสีเทา = skipped

Section 3: Import Settings card
           - maincat (always visible, ไม่ใน accordion แล้ว)
           - <input type="hidden" name="maincat_col" :value="hasCatidMapping ? 1 : 0">
           - id_col, state, access, language, items_per_step
           - <details> สำหรับ advanced (tags, dates, meta, CSV format)

Buttons:
   [📤 Upload & Start Import]  ← submit to mapinitcsv (PRIMARY)
   [⚡ Quick Import]           ← submit to initcsv
   [🔍 Test Format]            ← submit to testcsv
   [🔀 Preview & Map (Step-2)] ← submit to previewcsv (compat)
```

**Alpine.js component `csvImporter()`:**

| Property/Method | ทำอะไร |
|---|---|
| `csvColumns[]` | `[{name, sample}]` จาก FileReader |
| `fcFields[]` | `[{id,name,label,field_type}]` จาก AJAX |
| `mappings{}` | `{colName → fcFieldName\|'__skip__'}` |
| `fieldOptions` (computed) | flat list ที่มี disabled "headers" แทน `<optgroup>` |
| `isReady` | `csvColumns.length > 0 && typeId > 0 && fcLoaded` |
| `hasCatidMapping` | `Object.values(mappings).includes('catid')` |
| `mappedCount` | จำนวน col ที่ไม่ใช่ `__skip__` |
| `onFileChange()` | FileReader → `parseCsv()` → `autoMap(false)` |
| `onTypeChange()` | fetch AJAX → `fcFields` → `autoMap(false)` |
| `parseCsv()` | อ่าน line 0 เป็น header, lines 1-5 เป็น sample |
| `parseCsvLine()` | handle quoted fields + escaped quotes |
| `autoMap(force)` | exact match → alias map → default `__skip__` |
| `handleSubmit()` | validate file/type/maincat → submit |

**Token สำหรับ AJAX:**
```php
$fc_token_name = \Joomla\CMS\Session\Session::getFormToken();
// ...
const fcToken = <?php echo json_encode($fc_token_name); ?>;
```

URL AJAX: `` `index.php?option=com_flexicontent&controller=import&task=getfieldsajax&type_id=${this.typeId}&${encodeURIComponent(fcToken)}=1` ``

---

## สิ่งที่ต้องทำก่อน commit

### ✅ ต้องทดสอบในบราวเซอร์

```
http://localhost:8888/joomla54/administrator/index.php
?option=com_flexicontent&view=import
```

**Test checklist:**

- [ ] **เลือกไฟล์ CSV** → columns badge โผล่ใต้ input, step-1 card เปลี่ยนเป็นสีเขียว
- [ ] **เลือก Content Type** → spinner หมุน → "N custom fields" โผล่
- [ ] **Mapping table โผล่** → auto-map ชื่อตรงกัน
- [ ] **Auto-map button** → re-map เมื่อกด
- [ ] **Select ใน mapping** → ค่าเปลี่ยนได้, row สีเทาเมื่อ skip
- [ ] **maincat hidden input** → `:value="hasCatidMapping ? 1 : 0"` → inspect DOM
- [ ] **"Upload & Start Import"** → submit → processcsv แสดงผล IMPORT FINISHED
- [ ] **"Preview & Map (Step-2)"** → ยังทำงานได้ปกติ (backward compat)
- [ ] **getfieldsajax URL** → เปิดในบราวเซอร์ตรง ๆ → คืน JSON

### ⚠️ จุดที่อาจมีปัญหา

1. **Alpine + Joomla select2**: `type_id` อาจ render เป็น Select2 widget → Alpine `@change` อาจไม่ fire → มี workaround ใน `DOMContentLoaded` แต่ยังไม่ได้ทดสอบ

2. **`$db->qn()` shorthand**: `getfieldsajax()` ใช้ `$db->qn(['fi.id','fi.name',...])` → ต้องตรวจว่า Joomla version รองรับ array input (`qn()` รับ string หรือ array ขึ้นกับเวอร์ชัน)

3. **`hasCatidMapping` + hidden input conflict**: มี 2 ที่ที่ submit `maincat_col` ใน form:
   - hidden input จาก Alpine (dynamic)
   - checkbox จาก accordion "Override with catid column" (ถ้ายังเหลืออยู่)
   → ต้องตรวจว่า accordion ไม่มี `maincat_col` checkbox แล้ว (ลบออกใน rewrite แล้ว แต่ verify อีกครั้ง)

4. **`$_FILES['csvfile']['tmp_name']` empty**: ถ้า PHP `upload_max_filesize` น้อยเกิน → error ไม่ชัดเจน → อาจต้อง add check

5. **Token encode ใน URL**: `encodeURIComponent(fcToken)` → token name มัก hash ธรรมดา ไม่ต้อง encode แต่ไม่เสียหาย

---

## Architecture ปัจจุบัน (หลัง rewrite)

```
User Action                Server Task           Session
──────────────────────────────────────────────────────────
[File + Type + col_map]
    ↓ submit to mapinitcsv
                          importcsv()
                          case 'mapinitcsv':
                            1. col_map from POST
                            2. Try session preview (Step-2 path)
                               └─ yes → use tmpfile
                               └─ no  → use $_FILES (NEW single-page path)
                            3. Apply col_map → rename columns
                            4. Sync flags (catid→maincat_col=1 etc.)
                            5. Store csvimport_config to session
                            6. redirect to processcsv
                          ↓
                          processcsv → AJAX batch import
```

**Step-2 legacy path ยังทำงาน:**
```
[File + config]
    ↓ submit to previewcsv
                          previewcsv()
                            → save file to tmp
                            → parse headers
                            → store csvimport_preview to session
                            → redirect to layout=map
                          ↓
import_map.php (Step 2)
    ↓ submit to mapinitcsv (col_map from Step-2 form)
                          importcsv() → uses session tmpfile
```

---

## Files ที่ยังไม่ได้ touch (อาจต้องแก้ทีหลัง)

| File | Issue |
|---|---|
| `admin/views/import/tmpl/import_map.php` | Step-2 ยังดีอยู่ ไม่ต้องแก้ |
| `admin/views/import/view.html.php` | ไม่ต้องแก้ ยกเว้นถ้าต้องการ embed FC fields ใน PHP แทน AJAX |
| `tests/Unit/Import/CsvColumnMappingTest.php` | 24 tests pass → ไม่ต้องแก้ |

---

## PHPUnit tests ที่ต้องเพิ่ม (หลัง browser test ผ่าน)

ควรเพิ่ม test สำหรับ logic ใหม่:

```php
// tests/Unit/Import/SinglePageWizardTest.php

// 1. mapinitcsv: ถ้าไม่มี session preview → ต้องไม่ error (ใช้ $_FILES แทน)
// 2. getfieldsajax: คืน JSON ที่ถูกต้องสำหรับ type_id ที่มีอยู่
// 3. getfieldsajax: คืน {"fields":[]} สำหรับ type_id=0
// 4. hasCatidMapping: maincat_col hidden input = 1 เมื่อ catid mapped
```

---

## Commit message (เมื่อพร้อม)

```
feat(import): single-page CSV import wizard with live field mapping

- Client-side CSV parsing via FileReader (no server round-trip for preview)
- AJAX endpoint getfieldsajax() returns FC fields as JSON per content type
- Alpine.js mapping table: auto-map by name + alias, re-mappable
- mapinitcsv now accepts direct file upload (no previewcsv session required)
- maincat_col auto-set via Alpine :value binding when catid is mapped
- Legacy Step-2 (previewcsv → import_map.php) preserved for back-compat
- Import Settings always visible; advanced options in <details> collapsible
```

---

## Priority Tasks (ยังค้างอยู่)

ดู `project_pending_tasks.md` → สรุปหลักที่ยังรอ:

| Priority | Task |
|---|---|
| 1.1 | ทดสอบ 7 plugin fields (image, file, mediafile, relation, sharedmedia, weblink, addressint) |
| 1.2 | JS fallback for CSS `:has()` ใน `fc-card-anim.js` |
| 2.1 | Responsive column params สำหรับ grid template |
| 2.2 | Dark mode support |
