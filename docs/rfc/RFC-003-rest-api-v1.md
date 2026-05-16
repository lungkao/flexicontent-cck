# RFC-003: REST API v1

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Author** | @pisan |
| **Created** | 2026-04-18 |
| **Target** | v7.0 |
| **Depends on** | RFC-001 |

---

## 1. Summary

RESTful API v1 สำหรับ FLEXIContent Flux — เปิดทาง headless use case, mobile apps, integrations, Next.js/Nuxt frontends โดยไม่กระทบ rendering เดิม

**Mount point:** Joomla Web Services API (`/api/index.php/v1/*`)  
**Auth:** Joomla Super User Tokens + API Keys + OAuth2 (optional plugin)  
**Format:** JSON:API v1.1 (with optional plain JSON mode)

## 2. Design Principles

1. **Additive only** — เพิ่ม endpoints ใหม่, ไม่กระทบ `index.php?option=com_flexicontent&...`
2. **Spec-first** — OpenAPI 3.1 document เป็น source of truth
3. **Field-aware** — ทุก field type มี serializer (RFC-002 §4.6)
4. **Joomla-native** — ใช้ Joomla Web Services infrastructure, ACL, tokens
5. **Versioned** — `/v1/` ไม่ breaking ภายใน major; `/v2/` เริ่มใหม่ได้
6. **Pagination consistent** — cursor-based (not offset) สำหรับ large datasets
7. **CORS-aware** — strict default, config opt-in

---

## 3. Base URL & Conventions

```
Base:        https://site.tld/api/index.php/v1
Content-Type: application/vnd.api+json  (JSON:API)
             application/json            (plain mode, ?format=plain)

Headers:
  X-Joomla-Token: <token>              (or Authorization: Bearer)
  Accept: application/vnd.api+json
  Accept-Language: en-GB, th-TH;q=0.9
```

### HTTP Status Codes
| Code | Use |
|------|-----|
| 200 | OK (GET, PATCH, PUT) |
| 201 | Created (POST) |
| 204 | No content (DELETE) |
| 400 | Validation error (with error body) |
| 401 | Unauthorized (missing/invalid token) |
| 403 | Forbidden (ACL denied) |
| 404 | Not found |
| 409 | Conflict (version mismatch on PATCH) |
| 422 | Unprocessable entity (schema violation) |
| 429 | Rate limited |
| 500 | Server error |

### Error Body (JSON:API)
```json
{
  "errors": [
    {
      "id": "err-abc123",
      "status": "422",
      "code": "FIELD_VALIDATION_FAILED",
      "title": "Field validation failed",
      "detail": "Field 'email' must be a valid email address",
      "source": { "pointer": "/data/attributes/fields/email" },
      "meta": { "field_id": 42, "validator": "EmailValidator" }
    }
  ]
}
```

---

## 4. Endpoint Catalog

### 4.1 Items

```
GET    /v1/fcitems                     List items (paginated, filterable)
GET    /v1/fcitems/{id}                Get single item with fields
POST   /v1/fcitems                     Create item
PATCH  /v1/fcitems/{id}                Partial update
PUT    /v1/fcitems/{id}                Full replace
DELETE /v1/fcitems/{id}                Trash (soft delete)
POST   /v1/fcitems/{id}:publish        Publish
POST   /v1/fcitems/{id}:unpublish      Unpublish
POST   /v1/fcitems/{id}:restore        Restore from trash
DELETE /v1/fcitems/{id}:purge          Permanent delete (admin only)
POST   /v1/fcitems/{id}:clone          Clone item
GET    /v1/fcitems/{id}/versions       Version history
GET    /v1/fcitems/{id}/versions/{v}   Specific version
POST   /v1/fcitems/{id}:revert         Revert to version
```

### 4.2 Types

```
GET    /v1/fctypes                     List content types
GET    /v1/fctypes/{id}                Get type with field bindings
GET    /v1/fctypes/{id}/schema         JSON Schema for items of this type
POST   /v1/fctypes                     Create type (admin)
PATCH  /v1/fctypes/{id}                Update type (admin)
```

### 4.3 Fields

```
GET    /v1/fcfields                    List fields
GET    /v1/fcfields/{id}               Get field config
GET    /v1/fcfields/{id}/schema        JSON Schema for values
POST   /v1/fcfields/{id}:render        Render a value (admin preview)
POST   /v1/fcfields                    Create field (admin)
PATCH  /v1/fcfields/{id}               Update field (admin)
DELETE /v1/fcfields/{id}               Delete field (admin)
```

### 4.4 Categories

```
GET    /v1/fccategories                List categories (tree optional)
GET    /v1/fccategories/{id}           Get category
GET    /v1/fccategories/{id}/items     Items in category
POST   /v1/fccategories                Create
PATCH  /v1/fccategories/{id}           Update
DELETE /v1/fccategories/{id}           Delete
```

### 4.5 Tags

```
GET    /v1/fctags                      List tags
POST   /v1/fctags                      Create
DELETE /v1/fctags/{id}                 Delete
GET    /v1/fctags/{id}/items           Items with tag
```

### 4.6 Search

```
GET    /v1/fcsearch?q=...              Full-text search
POST   /v1/fcsearch                    Advanced search with facets
GET    /v1/fcsearch/suggestions?q=...  Autocomplete
```

### 4.7 Media

```
POST   /v1/fcmedia:upload              Multipart file upload
GET    /v1/fcmedia/{id}                Media metadata
DELETE /v1/fcmedia/{id}                Delete media
POST   /v1/fcmedia/{id}:thumbnail      Generate thumbnail
```

### 4.8 Schema & Meta

```
GET    /v1/openapi.json                OpenAPI 3.1 spec
GET    /v1/meta/server                 Server info (version, caps)
GET    /v1/meta/permissions            Current user's ACL summary
```

---

## 5. Request/Response Examples

### 5.1 List Items

```http
GET /api/index.php/v1/fcitems?filter[type]=article&filter[state]=1&sort=-created&page[cursor]=eyJpIjoxMDB9&page[size]=20&include=fields,category,tags
```

**Response (200):**
```json
{
  "data": [
    {
      "type": "fcitem",
      "id": "32",
      "attributes": {
        "title": "Khawsar 2",
        "alias": "32-copy-copy-copy-copy-khawsar-2",
        "state": 1,
        "created": "2026-04-10T08:23:45Z",
        "modified": "2026-04-18T14:01:12Z",
        "language": "*",
        "type_id": 1,
        "category_id": 8
      },
      "relationships": {
        "fields": {
          "data": [
            { "type": "fieldvalue", "id": "32-6" },
            { "type": "fieldvalue", "id": "32-46" }
          ]
        },
        "category": { "data": { "type": "fccategory", "id": "8" } },
        "tags": { "data": [] }
      }
    }
  ],
  "included": [
    {
      "type": "fieldvalue",
      "id": "32-46",
      "attributes": {
        "field_name": "test-address",
        "field_type": "addressint",
        "value": {
          "lat": 13.9057852,
          "lon": 100.540862,
          "addr1": null,
          "city": null
        }
      }
    }
  ],
  "links": {
    "self":  "/api/.../v1/fcitems?page[cursor]=...",
    "next":  "/api/.../v1/fcitems?page[cursor]=eyJpIjoxMjB9"
  },
  "meta": {
    "count": 20,
    "total": 847,
    "took_ms": 34
  }
}
```

### 5.2 Create Item

```http
POST /api/index.php/v1/fcitems
Content-Type: application/vnd.api+json
X-Joomla-Token: <token>

{
  "data": {
    "type": "fcitem",
    "attributes": {
      "title": "New Article",
      "type_id": 1,
      "category_id": 8,
      "state": 1,
      "language": "*",
      "fields": {
        "text":         { "value": "Hello world" },
        "test-address": { "lat": 13.9, "lon": 100.5 }
      }
    }
  }
}
```

**Response (201):**
```http
HTTP/1.1 201 Created
Location: /api/index.php/v1/fcitems/128
```
```json
{ "data": { "type": "fcitem", "id": "128", /* ... */ } }
```

### 5.3 PATCH Item

```http
PATCH /api/index.php/v1/fcitems/128
If-Match: "v3"

{
  "data": {
    "type": "fcitem",
    "id": "128",
    "attributes": {
      "fields": {
        "test-address": { "lat": 14.0, "lon": 100.5 }
      }
    }
  }
}
```

**Conflict response (409):** when `If-Match` version doesn't match current `etag`.

### 5.4 Upload Media

```http
POST /api/index.php/v1/fcmedia:upload
Content-Type: multipart/form-data

----boundary
Content-Disposition: form-data; name="file"; filename="photo.jpg"
Content-Type: image/jpeg

<binary>
----boundary
Content-Disposition: form-data; name="field_id"
41
----boundary
Content-Disposition: form-data; name="alt"
Sunset at the park
```

**Response (201):**
```json
{
  "data": {
    "type": "fcmedia",
    "id": "77",
    "attributes": {
      "url":       "/media/com_flexicontent/files/photo.jpg",
      "thumb_url": "/media/com_flexicontent/files/thumb_photo.jpg",
      "size":      842104,
      "mime":      "image/jpeg",
      "width":     1920,
      "height":    1080,
      "alt":       "Sunset at the park"
    }
  }
}
```

---

## 6. Filtering, Sorting, Pagination

### 6.1 Filter Syntax

```
?filter[type]=article
?filter[state]=1
?filter[created:gte]=2026-01-01
?filter[category]=8,9,10                    (multi value, OR)
?filter[tags:any]=featured,hot              (has any of)
?filter[fields.text:contains]=joomla        (field value match)
?filter[fields.test-address.city]=Bangkok
```

### 6.2 Sorting

```
?sort=-created,title                        (descending created, then ascending title)
?sort=fields.rating:desc
```

### 6.3 Pagination (Cursor-based)

```
?page[size]=20
?page[cursor]=<opaque-token>
```

**Why cursor, not offset?** — stable across inserts, better for large datasets, no "missing items" when new content appears during pagination.

### 6.4 Sparse Fieldsets

```
?fields[fcitem]=title,state,created
?fields[fieldvalue]=field_name,value
```

### 6.5 Includes (avoid N+1)

```
?include=category,tags,fields,author
```

---

## 7. Authentication & Authorization

### 7.1 Token Types
| Type | Use Case | Storage |
|------|----------|---------|
| **Joomla User Token** | End-user API access | `#__users` profile |
| **API Key** | Server-to-server, headless frontend | `#__flexicontent_api_keys` (new) |
| **OAuth2 Bearer** | Mobile apps, 3rd party | via plugin `com_flexicontent_oauth` |

### 7.2 Header Formats

```
X-Joomla-Token: abc123...                  (legacy compat)
Authorization: Bearer abc123...            (preferred)
Authorization: FCKey <key-id>:<secret>     (API key)
```

### 7.3 Scopes (API Keys)

```
read:items
write:items
publish:items
read:fields
write:fields       (admin)
read:types
write:types        (admin)
upload:media
admin:all          (super user only)
```

### 7.4 ACL Integration
- Wrap all queries with Joomla's `JAccess::check()`
- Respect view-level ACL (category access, item access_level)
- Field-level ACL from `show_in_views`, `required_permission`
- Return `403` + details when partial access denied

---

## 8. Rate Limiting

### 8.1 Default Limits
| Scope | Limit |
|-------|-------|
| Anonymous (public items) | 60 req/min per IP |
| Authenticated | 600 req/min per user |
| Server API key | 10,000 req/min |
| Admin | Unlimited |

### 8.2 Headers
```
X-RateLimit-Limit: 600
X-RateLimit-Remaining: 542
X-RateLimit-Reset: 1713448800
Retry-After: 30                (on 429)
```

### 8.3 Storage
- Sliding window via Joomla cache (apcu/redis/file)
- Config: `rate_limit_driver`, `rate_limit_anonymous`, etc.

---

## 9. CORS & CSRF

### 9.1 CORS Config
```php
// com_flexicontent config
'cors_enabled' => true,
'cors_allowed_origins' => ['https://app.example.com'],
'cors_allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS'],
'cors_allowed_headers' => ['Authorization', 'Content-Type', 'X-Joomla-Token'],
'cors_max_age' => 86400,
'cors_allow_credentials' => false,
```

### 9.2 CSRF
- Bearer token = no CSRF needed (Authorization header not auto-sent by browsers)
- Cookie-based sessions require `X-CSRF-Token` for unsafe methods

---

## 10. Webhooks (v7.5+)

```
Events:
  fcitem.created
  fcitem.updated
  fcitem.published
  fcitem.unpublished
  fcitem.deleted
  fcfield.updated

Delivery: POST with HMAC-SHA256 signature
Retry: 3 attempts, exponential backoff
```

### 10.1 Webhook Payload
```json
{
  "event": "fcitem.updated",
  "id": "evt-xyz789",
  "created_at": "2026-04-18T14:30:00Z",
  "data": {
    "item": { /* full item object */ },
    "changes": ["fields.text", "state"]
  }
}
```

### 10.2 Signature Verification
```
X-FC-Signature: sha256=<hex digest>
X-FC-Delivery-ID: <uuid>
```

---

## 11. OpenAPI Spec Generation

```
GET /api/index.php/v1/openapi.json
```

- Auto-generated from:
  - Endpoint annotations (PHP 8 attributes)
  - Field JSON schemas (from `FieldSerializer::jsonSchema()`)
  - Type configurations
- Served at `/v1/docs` = Swagger UI (optional plugin)
- Downloadable for Postman / client code gen

**Example annotation:**
```php
#[ApiRoute(method: 'GET', path: '/v1/fcitems/{id}')]
#[ApiResponse(200, schema: 'FcItem')]
#[ApiResponse(404, schema: 'Error')]
public function show(int $id): Response { /* ... */ }
```

---

## 12. Caching Strategy

### 12.1 ETag / Last-Modified
```http
GET /v1/fcitems/128
→ 200 OK
  ETag: "v3-abc123"
  Last-Modified: Sat, 18 Apr 2026 14:01:12 GMT
  Cache-Control: private, max-age=60

GET /v1/fcitems/128
If-None-Match: "v3-abc123"
→ 304 Not Modified
```

### 12.2 CDN-friendly Public Endpoints
- Public item GETs: `Cache-Control: public, s-maxage=300, stale-while-revalidate=86400`
- Purge on write via webhook to CDN API

### 12.3 Query Result Cache
- Hash of `(query, user_id, ACL_version)` → response
- Backend: Joomla cache adapter (apcu/redis)
- Invalidate on item/field/ACL write

---

## 13. SDK Examples

### 13.1 PHP SDK
```php
use FLEXIcontent\Sdk\Client;

$client = new Client('https://site.tld', apiKey: '...');
$items = $client->items()->list(filter: ['type' => 'article'], include: ['fields']);
$client->items()->create(['title' => 'New', 'type_id' => 1, 'fields' => [...]]);
```

### 13.2 TypeScript SDK
```typescript
import { FCClient } from '@flexicontent/sdk';

const client = new FCClient({ baseUrl: '...', apiKey: '...' });
const { data } = await client.items.list({
  filter: { type: 'article' },
  include: ['fields', 'category'],
});
```

### 13.3 Raw curl
```bash
curl -H "Authorization: Bearer $TOKEN" \
     "https://site.tld/api/index.php/v1/fcitems?filter[type]=article"
```

---

## 14. Security Considerations

1. **Input sanitization** — all field values pass through `FieldValidator` before storage
2. **Output escaping** — fields render with proper escaping per context (HTML/JSON/URL)
3. **Mass assignment** — whitelist attributes per endpoint
4. **SQL injection** — parameterized queries via Doctrine DBAL only
5. **File upload** — MIME sniffing, extension whitelist, size limit, virus scan hook
6. **XXE** — disabled in any XML parsing
7. **Token rotation** — API keys rotatable with revocation
8. **Audit log** — every write logged to `#__flexicontent_api_audit`
9. **Rate limit** — per-key + per-IP
10. **HTTPS only** — reject plain HTTP in production (configurable)

---

## 15. Success Criteria

- [ ] OpenAPI spec covers 100% of endpoints
- [ ] JSON:API compliance (all List/CRUD)
- [ ] P95 latency < 150ms for item GET with 20 fields
- [ ] SDKs for TS + PHP with test coverage ≥ 80%
- [ ] Swagger UI + Postman collection published
- [ ] 3+ reference integrations (Next.js, Nuxt, mobile)
- [ ] Rate limit + CORS + audit log production-ready
- [ ] Security audit by external party before v7.0 stable

---

## 16. Open Questions

1. **JSON:API vs plain JSON** — default format? (JSON:API = richer, plain = simpler)
2. **GraphQL** — ใน v7.x หรือ v8.0? เป็น endpoint เสริมหรือ first-class?
3. **Bulk operations** — JSON:API bulk extension vs custom `:batch` endpoints?
4. **Versioning** — URL (`/v1/`) vs header (`Accept: application/vnd.fc.v1+json`)?
5. **Deprecation** — แจ้ง client ผ่าน `Sunset` + `Deprecation` headers?
6. **Multi-tenancy** — ต้องรองรับหลาย site ใน install เดียวไหม?
7. **GraphQL subscriptions / SSE** — real-time updates?
8. **Admin API vs Public API** — แยก mount point หรือไม่?

---

## 17. References

- [JSON:API v1.1](https://jsonapi.org/format/)
- [OpenAPI 3.1](https://spec.openapis.org/oas/v3.1.0)
- [Joomla Web Services API](https://docs.joomla.org/J4.x:Web_Services_API)
- [JSON Schema 2020-12](https://json-schema.org/specification)
- [RFC 7234 HTTP Caching](https://httpwg.org/specs/rfc7234.html)
- [RFC 7235 HTTP Authentication](https://httpwg.org/specs/rfc7235.html)
- RFC-002 §4.6 FieldSerializer
