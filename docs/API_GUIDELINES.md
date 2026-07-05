# API Guidelines

Conventions for how the app talks to the backend — both what exists today (Inertia) and what will apply when a JSON API is added.

> **Status:** The web app uses Inertia only. There's no public JSON API yet. Section 2 is the plan for when mobile or third-party clients need one — this doc is updated when that sprint starts, same as everything else in `docs/`.

---

## 1. Inertia (current)

This is how every web route works today.

### Responses

Controllers return `Inertia::render('module/Page', [...props])`. No raw JSON on web routes.

Page paths mirror the module: `identity/users/Index`, `identity/users/Edit`.

For actions that depend on authorization, pass a `can` prop from the controller:

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

The frontend uses `can` to show/hide buttons. The policy is still enforced on the backend regardless.

### Mutations

POST/PUT/PATCH/DELETE → redirect to a named route (`to_route('users.index')`). Validation errors come back through Form Requests automatically.

### Pagination

Eloquent paginator → Inertia gets `{ data, links, meta }`. Frontend renders pagination from `links`.

---

## 2. JSON API (planned)

When a versioned API is added (likely Sanctum + `/api/v1/...`), these are the conventions to follow. Subject to change during implementation — the shape is decided upfront rather than under deadline pressure.

### Structure

- Routes: `/api/v1/{resource}`
- Controllers: `App\Http\Controllers\Api\V1\{Module}`
- One version per controller namespace — no mixing

### Auth

Sanctum tokens (or SPA cookie for first-party). Tenant comes from the authenticated user, never from a request body field. If someone sends `organization_id` in the payload, ignore it.

### Response envelope

Every response:

```json
{
  "success": true,
  "message": "",
  "errors": [],
  "data": {}
}
```

No bare model dumps. Wrap through a shared helper/trait.

### Status codes

| When | Code |
|---|---|
| Read / update OK | 200 |
| Created | 201 |
| Accepted (async) | 202 |
| Deleted | 204 |
| Validation failed | 422 |
| Not logged in | 401 |
| Logged in, not allowed | 403 |
| Not found / hidden by tenant scope | 404 |
| Rate limited | 429 |

Cross-tenant resource that doesn't exist for you → **404**, not 403. Don't leak that it exists in another org.

### Input / output

- Form Requests for validation, same module structure as web
- API Resources (`JsonResource`) for output — no passwords, tokens, or internal FKs the client doesn't need
- Timestamps in ISO-8601 UTC
- Lists paginated by default; cap `per_page`
- Explicit `sort` / `filter` params — no arbitrary column injection

### Naming

Plural resources: `/api/v1/competitions`. Route names: `api.v1.competitions.index`.

---

## 3. Security (both layers)

Same rules regardless of Inertia or JSON:

- Policy check before data access
- Never trust client-supplied `organization_id`
- Strict `$fillable` on models
- Validate uploads (type, size, dimensions)
- Throttle auth endpoints
- 404 for cross-tenant misses

---

*Section 2 will be expanded with concrete examples (routes, resource classes, test patterns) once the API sprint ships.*
