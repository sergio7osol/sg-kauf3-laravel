# Shop Addresses API

Base URL: `/api/shops/{shopId}/addresses`

All endpoints require authentication via Sanctum (`Authorization: Bearer {token}`).

---

## Endpoints

### List Addresses

```
GET /api/shops/{shopId}/addresses
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `activeOnly` | boolean | No | If `true`, returns only active addresses |

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "shopId": 5,
      "country": "DE",
      "postalCode": "10115",
      "city": "Berlin",
      "street": "Friedrichstraße",
      "houseNumber": "123",
      "isPrimary": true,
      "displayOrder": 1,
      "isActive": true
    }
  ],
  "meta": {
    "count": 1,
    "shopId": 5
  }
}
```

---

### Create Address

```
POST /api/shops/{shopId}/addresses
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `country` | string | No | ISO country code (default: `DE`) |
| `postalCode` | string | Yes | Postal/ZIP code (max 20 chars) |
| `city` | string | Yes | City name (max 120 chars) |
| `street` | string | Yes | Street name (max 150 chars) |
| `houseNumber` | string | Yes | House number (max 25 chars) |
| `isPrimary` | boolean | No | Set as primary address (default: `false`) |
| `displayOrder` | integer | No | Display order (auto-assigned if omitted) |
| `isActive` | boolean | No | Active status (default: `true`) |

**Example Request:**
```json
{
  "postalCode": "10115",
  "city": "Berlin",
  "street": "Friedrichstraße",
  "houseNumber": "123",
  "isPrimary": true
}
```

**Response:** `201 Created`
```json
{
  "data": {
    "id": 1,
    "shopId": 5,
    "country": "DE",
    "postalCode": "10115",
    "city": "Berlin",
    "street": "Friedrichstraße",
    "houseNumber": "123",
    "isPrimary": true,
    "displayOrder": 1,
    "isActive": true
  },
  "message": "Address created successfully."
}
```

**Validation Errors:** `422 Unprocessable Entity`
- Required fields missing
- Duplicate address (same `postalCode` + `street` + `houseNumber` for the shop)

---

### Show Address

```
GET /api/shops/{shopId}/addresses/{addressId}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "shopId": 5,
    "country": "DE",
    "postalCode": "10115",
    "city": "Berlin",
    "street": "Friedrichstraße",
    "houseNumber": "123",
    "isPrimary": true,
    "displayOrder": 1,
    "isActive": true
  }
}
```

**Errors:**
- `404 Not Found` – Address doesn't exist or doesn't belong to the shop

---

### Update Address

```
PUT /api/shops/{shopId}/addresses/{addressId}
PATCH /api/shops/{shopId}/addresses/{addressId}
```

**Request Body:** (all fields optional, only send what needs updating)
| Field | Type | Description |
|-------|------|-------------|
| `country` | string | ISO country code |
| `postalCode` | string | Postal/ZIP code |
| `city` | string | City name |
| `street` | string | Street name |
| `houseNumber` | string | House number |
| `isPrimary` | boolean | Set as primary (will unset others) |
| `displayOrder` | integer | Display order |
| `isActive` | boolean | Active status |

**Example Request:**
```json
{
  "city": "Munich",
  "isPrimary": true
}
```

**Response:** `200 OK`
```json
{
  "data": { ... },
  "message": "Address updated successfully."
}
```

---

### Set as Primary

```
PATCH /api/shops/{shopId}/addresses/{addressId}/set-primary
```

Sets the specified address as the primary address for the shop. **Automatically unsets any existing primary address.**

**Request Body:** None

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "shopId": 5,
    "isPrimary": true,
    ...
  },
  "message": "Address set as primary successfully."
}
```

---

### Toggle Active Status

```
PATCH /api/shops/{shopId}/addresses/{addressId}/toggle-active
```

Toggles the `isActive` flag. Use this for soft-deactivation instead of deleting addresses.

**Request Body:** None

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "isActive": false,
    ...
  },
  "message": "Address deactivated successfully."
}
```

---

## Business Rules

1. **One Primary Per Shop:** Only one address can be `isPrimary = true` per shop. Setting a new primary automatically unsets the previous one.

2. **Auto Display Order:** If `displayOrder` is omitted during creation, it defaults to `max(existing) + 1`.

3. **Soft Deactivation:** No hard delete endpoint. Use `toggle-active` to deactivate addresses. Deactivated addresses remain in the database for historical purchases.

4. **Uniqueness Constraint:** Each shop can only have one address with the same combination of `postalCode` + `street` + `houseNumber`.

---

## Response Keys

All responses use **camelCase** keys to match existing API conventions:

| Database Column | API Response Key |
|-----------------|------------------|
| `shop_id` | `shopId` |
| `postal_code` | `postalCode` |
| `house_number` | `houseNumber` |
| `is_primary` | `isPrimary` |
| `display_order` | `displayOrder` |
| `is_active` | `isActive` |
