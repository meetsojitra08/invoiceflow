# REST API (v1)

> The InvoiceFlow web app does **not** use this API; it uses Inertia. This API is for external integrations and reuses the same Policies, services, and API Resources.

Base URL: `/api/v1`. Auth: `Authorization: Bearer <token>` (Sanctum personal access token).

## Conventions
- JSON only; `Accept: application/json`
- Money in minor units (integer) plus `currency`
- Dates in ISO 8601
- Pagination: `?page=1&per_page=15` (max 100)
- Rate limit: 60 requests/minute per token
- Tenant is derived from the token owner's active company

## Error Format

```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["The email field must be a valid email address."] }
}
```

| Status | Meaning |
|--------|---------|
| 200 / 201 | Success / created |
| 401 | Missing or invalid token |
| 403 | Role not allowed |
| 404 | Not found (also for other tenants' records) |
| 422 | Validation failed |
| 429 | Rate limit exceeded |

## Endpoints

### Auth
| Method | Path | Description |
|--------|------|-------------|
| POST | `/auth/token` | Exchange email + password for a token |
| DELETE | `/auth/token` | Revoke current token |

### Clients
| Method | Path | Roles |
|--------|------|-------|
| GET | `/clients` | all |
| POST | `/clients` | all |
| GET | `/clients/{id}` | all |
| PUT | `/clients/{id}` | all |
| DELETE | `/clients/{id}` | owner |

### Products
| Method | Path | Roles |
|--------|------|-------|
| GET | `/products` | all |
| POST / PUT / DELETE | `/products[/{id}]` | owner, accountant |

### Invoices
| Method | Path | Description |
|--------|------|-------------|
| GET | `/invoices` | Filter: `status`, `client_id`, `from`, `to` |
| POST | `/invoices` | Create draft |
| GET | `/invoices/{id}` | With items and payments |
| PUT | `/invoices/{id}` | Draft only |
| POST | `/invoices/{id}/send` | Email to client |
| POST | `/invoices/{id}/void` | Body: `reason` |
| GET | `/invoices/{id}/pdf` | Download PDF |

### Payments
| Method | Path | Description |
|--------|------|-------------|
| GET | `/invoices/{id}/payments` | List |
| POST | `/invoices/{id}/payments` | Record manual payment |

## Example

**POST /api/v1/invoices**

```json
{
  "client_id": 12,
  "issue_date": "2026-11-01",
  "due_date": "2026-11-15",
  "items": [
    { "description": "Design work", "quantity": 10, "unit_price": 200000, "tax_rate": 18 }
  ]
}
```

**Response 201**

```json
{
  "data": {
    "id": 101,
    "number": "INV-0101",
    "status": "draft",
    "currency": "INR",
    "subtotal": 2000000,
    "tax_total": 360000,
    "total": 2360000,
    "amount_paid": 0,
    "client": { "id": 12, "name": "Acme Corp" },
    "items": [
      { "description": "Design work", "quantity": 10, "unit_price": 200000, "line_total": 2000000 }
    ]
  }
}
```

## Webhooks (inbound)

`POST /webhooks/stripe`: public route, no auth, CSRF-excluded, signature verified with `STRIPE_WEBHOOK_SECRET`.
