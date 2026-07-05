# API Guidelines — Competition Management System

## Scope

The web application communicates with the backend via **Inertia.js**, not a REST/JSON API — controllers return `Inertia::render(...)` with typed props, and forms post through Inertia's client. There is **no public HTTP API yet**.

This document defines the conventions to follow **when** a JSON API is introduced (e.g. for mobile clients, integrations — see [ROADMAP.md](ROADMAP.md) "Future"). It doubles as the contract for the current Inertia layer where relevant.

---

## 1. Inertia Conventions (current)

### Controller responses

- Return `Inertia::render('module/Page', [...props])` — never raw arrays or `response()->json()` for web routes.
- Page component names mirror the module path: `identity/users/Index`.
- Pass authorization hints as a `can` prop so the UI can conditionally render actions:

```php
return Inertia::render('identity/users/Edit', [
    'user' => $user,
    'roles' => $this->roleOptions(),
    'can' => [
        'deactivate' => $request->user()->can('deactivate', $user),
        'reactivate' => $request->user()->can('reactivate', $user),
        'delete' => $request->user()->can('delete', $user),
    ],
]);
```

### Redirects & flashes

- Mutations redirect to a named route (`to_route('users.index')`), following Post/Redirect/Get.
- Validation errors are returned by Form Requests and surfaced automatically by Inertia.

### Pagination

- Paginate with Eloquent's paginator; Inertia receives `{ data, links, meta }`. The frontend consumes `links` for pagination controls.

---

## 2. Future JSON API Conventions

When a versioned API is added, follow the rules below.

### Versioning & namespacing

- Prefix routes with a version: `/api/v1/...`.
- Controllers live under `App\Http\Controllers\Api\V1\{Module}`.
- Never mix versions within one controller.

### Authentication

- Use **Laravel Sanctum** tokens (or SPA cookie auth for first-party clients).
- Every request must resolve a tenant: derive `organization_id` from the authenticated user, never from a client-supplied field.

### Standard response envelope

All API responses share a consistent shape:

```json
{
  "success": true,
  "message": "Human-readable summary",
  "errors": [],
  "data": { }
}
```

- `success`: boolean.
- `message`: short description (empty string allowed).
- `errors`: array/object of field errors (empty when `success` is `true`).
- `data`: the payload — an object, array, or `null`.

Do not return bare models or raw `response()->json($model)`. Wrap through a shared responder trait/helper so the envelope stays uniform.

### HTTP status codes

| Scenario | Status |
|---|---|
| Success (read/update) | `200 OK` |
| Resource created | `201 Created` |
| Accepted for async processing | `202 Accepted` |
| No content (delete) | `204 No Content` |
| Validation failed | `422 Unprocessable Entity` |
| Unauthenticated | `401 Unauthorized` |
| Authenticated but forbidden | `403 Forbidden` |
| Not found (or hidden by tenant scope) | `404 Not Found` |
| Rate limited | `429 Too Many Requests` |
| Server error | `500 Internal Server Error` |

### Validation

- Use Form Requests for all input, mirroring the web module structure.
- Return field-level errors in `errors`; use a `422` status.

### Authorization

- Enforce Policies exactly as the web layer does. A cross-tenant resource should return `404` (not `403`) to avoid leaking existence.

### Pagination, filtering, sorting

- Paginate list endpoints by default; include pagination metadata in `data` or a top-level `meta`.
- Accept `page`, `per_page` (capped), and explicit `sort`/`filter` params — never allow arbitrary column injection.

### Serialization

- Use **API Resources** (`JsonResource`) to shape output; never expose internal columns like `password`, `remember_token`, or raw foreign keys the client shouldn't see.
- Timestamps in ISO-8601 (UTC).

### Rate limiting

- Apply throttling to authentication and other sensitive endpoints (`throttle` middleware).

### Naming

- Resource routes are plural nouns: `/api/v1/competitions`.
- Route names use dot notation: `api.v1.competitions.index`.

---

## 3. Security Checklist (applies to both layers)

- Authorize before any data access (Policy).
- Never trust a client-supplied `organization_id`; derive tenancy from the authenticated user.
- Mass-assignment protection via strict `$fillable`.
- Validate and constrain file uploads (type, size, dimensions).
- Rate-limit auth and other abuse-prone endpoints.
- Return `404` for cross-tenant resources to avoid existence disclosure.

## Related Documents

- [ARCHITECTURE.md](ARCHITECTURE.md) — request lifecycle and layering
- [DATABASE.md](DATABASE.md) — what data exists to expose
- [DECISIONS.md](DECISIONS.md) — why Inertia now, API later
