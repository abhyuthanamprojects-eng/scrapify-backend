# Scrap Selling API Changes (Frontend Handover)

Date: 2026-05-06  
Scope: **Scrap Selling flow only** (Customer scrap booking)  
Excluded: **Donation** and **Corporate** flows (no change required there)

---

## 1) Summary of Biggest Changes

1. Catalog flow now supports **Category -> Subcategory** directly (2-level flow).  
2. `/api/home-appliances/estimate` is now the primary backend-driven estimator for configurable categories.  
3. Dynamic estimation attributes (Material Type, Pickup Size, Condition, etc.) are served from backend and validated server-side.  
4. Pricing is now backend-manageable using:
   - base price at category/subcategory level
   - option-level adjustment rules (including percentage-based adjustments)
5. `POST /api/pickup-requests` is supported for scrap submit (in addition to legacy singular route).

---

## 2) Flow Changes (Scrap Selling)

### Old practical behavior
- UI often depended on deeper item-level assumptions.
- Estimation behavior could be inconsistent when option-level rules were missing.

### New practical behavior
- Frontend can run with **2 levels**: category -> subcategory.
- Subcategory can be treated as sellable unit with base price and dynamic options.
- Estimate is computed backend-side from selected options.

---

## 3) Endpoint Changes

## 3.1 `GET /api/categories`

### New mode for 2-level catalog
Use:
```http
GET /api/categories?use_tree=1
```

### Response shape
```json
{
  "success": true,
  "code": 200,
  "message": "categories.fetched",
  "data": [
    {
      "id": 101,
      "name": "Electrical Appliances",
      "image": "https://...",
      "base_price": 4500,
      "pricing_type": "per_piece"
    }
  ],
  "errors": null
}
```

Notes:
- `pricing_type` is dynamic per category base rule (`per_piece` / `per_kg` / `per_capacity`).

---

## 3.2 `GET /api/subcategories?category_id={id}`

### Updated behavior
- Supports both:
  1. **new flow**: `category_id` as parent category id
  2. legacy fallback: `category_id` as old category type id

### Response shape
```json
{
  "success": true,
  "code": 200,
  "message": "subcategories.fetched",
  "data": {
    "id": 101,
    "items": [
      {
        "id": 201,
        "name": "Air Conditioner",
        "image": "https://...",
        "base_price": 4500,
        "pricing_type": "per_piece"
      }
    ]
  },
  "errors": null
}
```

---

## 3.3 `GET /api/items?subcategory_id={id}`

### Backward compatibility behavior
- If child items exist under subcategory: returns children as before.
- If no child items exist: backend returns the **subcategory itself** as one item-like record.

Why this matters:
- Frontend does not break if third-level items are removed.

---

## 3.4 `GET /api/home-appliances/details?category_id={id}`

### Important additions
- Each section now includes `id` (attribute id) so frontend can send valid `attribute_id`.
- Response includes `pricing_type`.

### Response (example)
```json
{
  "success": true,
  "code": 200,
  "message": "home_appliance_details.fetched",
  "data": {
    "id": 201,
    "name": "Air Conditioner",
    "estimated_price": 4500,
    "pricing_type": "per_piece",
    "sections": [
      {
        "id": 11,
        "title": "Material Type",
        "slug": "material-type",
        "options": [
          { "id": 1, "value": "Metal" }
        ]
      }
    ]
  },
  "errors": null
}
```

---

## 3.5 `POST /api/home-appliances/estimate`

### Purpose
Returns live estimate based on selected options for scrap selling categories.

### Request
```json
{
  "category_id": 201,
  "attributes": [
    {
      "attribute_id": 11,
      "attribute_name": "Material Type",
      "option_id": 1,
      "value": "Metal"
    },
    {
      "attribute_id": 12,
      "attribute_name": "Pickup Size",
      "option_id": 6,
      "value": "Small"
    },
    {
      "attribute_id": 13,
      "attribute_name": "Condition",
      "option_id": 11,
      "value": "Scrap"
    }
  ]
}
```

### Success response
```json
{
  "success": true,
  "code": 200,
  "message": "home_appliance.estimate_calculated",
  "data": {
    "estimated_price": 4725,
    "price": 4725,
    "pricing_type": "per_piece"
  },
  "errors": null
}
```

### Validation error response
```json
{
  "success": false,
  "code": 422,
  "message": "home_appliance.invalid_attribute_selection",
  "data": null,
  "errors": {
    "attributes": [
      "Option does not belong to category"
    ]
  }
}
```

### Validation notes
- `attribute_id` should come from `details.sections[].id`.
- Legacy tolerance exists for payloads where `attribute_id` is missing/0 but `option_id` is valid.

---

## 3.6 Scrap submission route compatibility

### Supported routes
- `POST /api/pickup-request` (legacy)
- `POST /api/pickup-requests` (**new preferred**)

Coupon validation errors now return structured field errors (e.g. `errors.coupon_code`).

---

## 4) Pricing Engine Behavior (Scrap)

Priority used in estimate:

1. Base price from category/subcategory default rule (`attribute_option_id = null`)  
2. Option-level rule application for selected options  
3. Adjustment types:
   - `fixed` delta
   - `percentage` delta (increase/decrease from base)
4. Legacy fallback kept for older pricing rows if needed

---

## 5) Admin Management Changes (for backend ops)

Admin category form now supports:
- top-level category or subcategory (`parent_id`)
- base price + pricing type
- option-specific pricing rules
- percentage adjustments per option (Material Type / Pickup Size / Condition etc.)

DB support added in pricing rules for:
- `adjustment_type` (`fixed` / `percentage`)
- `adjustment_value`

---

## 6) Frontend Action Checklist

1. Prefer `GET /api/categories?use_tree=1` for scrap catalog.
2. Use `GET /api/subcategories?category_id={parentCategoryId}`.
3. Use `GET /api/home-appliances/details?category_id={subcategoryId}` for dynamic options.
4. Always send `attribute_id` from `sections[].id`.
5. Read estimate from `data.estimated_price` (or fallback `data.price`).
6. Respect `data.pricing_type` to display `per_piece` / `per_kg` correctly.
7. Use `POST /api/pickup-requests` for scrap submit.

---

## 7) Explicit Non-Changes

The following flows are intentionally not changed in this document scope:
- Donation flow APIs
- Corporate flow APIs

---

## 8) Versioning / Rollout Note

If app versions are mixed in production, keep backward-compatible parsing for:
- categories response shape (`data` list vs wrapper)
- estimate result (`estimated_price` and `price`)
- legacy `pickup-request` route

