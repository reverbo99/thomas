# Special Hire Customer API Documentation

**Base URL:** `/api/special-hire/customer`

**Authentication:** Bearer Token (Laravel Sanctum)

**Required Role:** `customer`

---

## Quick Guide (UI & AI Agents)

- **Happy path (UI flow):** Register/Login → Load coasters for the map → Let user pick pickup/drop-off → Call price calculator → Create booking → Poll tracking once status = `in_progress` → Allow cancel when status ∈ `pending|confirmed|in_progress`.
- **Map essentials:** Use `latitude`/`longitude` plus `availability_status` to color markers (`available` = green, `busy` = red). Show `name`, `capacity`, `features`, and `pricing.price_per_km` in a tooltip/card. Hide/disable booking CTA when `is_available` is false.
- **Forms:** Use the same payloads shown below. Require date/time inputs in `YYYY-MM-DD` and `HH:MM` (24h). If coordinates are available from the map, pass all four lat/lng values; otherwise, allow manual `distance_km`.
- **Prices:** All amounts are in TZS. There is NO base price - price is calculated as distance × price per km + surcharges. The calculator returns a breakdown with surcharges; show both the raw and final totals to the user.
- **Status to UI mapping:** `available` (green), `busy` (red); bookings: `pending` → `confirmed` → `in_progress` → `completed` (or `cancelled`).
- **Tracking:** Poll `/bookings/{id}/track` every 15–30s while `order_status` is `in_progress` and update the map marker.

---

## Table of Contents

0. [Quick Guide (UI & AI Agents)](#quick-guide-ui--ai-agents)
1. [Authentication](#authentication)
2. [Profile Management](#profile-management)
3. [Browse Coasters](#browse-coasters)
4. [Price Calculation](#price-calculation)
5. [Bookings Management](#bookings-management)
6. [Tracking](#tracking)
7. [Error Responses](#error-responses)

---

## Authentication

### Customer Registration

Customers can register themselves to book coasters.

```http
POST /api/special-hire/customer/register
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Customer's full name |
| `email` | string | Yes | Email address (must be unique) |
| `phone` | string | Yes | Phone number |
| `password` | string | Yes | Password (min 6 chars) |
| `password_confirmation` | string | Yes | Password confirmation |

**Example Request:**
```json
{
    "name": "Jane Customer",
    "email": "jane@example.com",
    "phone": "+255755123456",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 15,
            "name": "Jane Customer",
            "email": "jane@example.com",
            "phone": "+255755123456",
            "role": "customer"
        },
        "token": "3|abc123xyz..."
    }
}
```

---

### Customer Login

```http
POST /api/special-hire/customer/login
```

**Request Body:**
```json
{
    "email": "jane@example.com",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 15,
            "name": "Jane Customer",
            "email": "jane@example.com",
            "phone": "+255755123456",
            "role": "customer"
        },
        "token": "3|abc123xyz..."
    }
}
```

**Error Response (401 Unauthorized):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

**Error Response (403 Forbidden):**
```json
{
    "success": false,
    "message": "Unauthorized. Customer access only."
}
```

### Headers Required for Protected Routes

```
Authorization: Bearer {your_access_token}
Accept: application/json
Content-Type: application/json
```

---

## Profile Management

### Get Customer Profile

```http
GET /api/special-hire/customer/profile
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "name": "Jane Customer",
        "email": "jane@example.com",
        "phone": "+255755123456",
        "role": "customer"
    }
}
```

---

### Update Customer Profile

```http
PUT /api/special-hire/customer/profile
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | No | Customer's name |
| `phone` | string | No | Phone number |
| `password` | string | No | New password (min 6 chars) |
| `password_confirmation` | string | Conditional | Required if password is provided |

**Example Request:**
```json
{
    "name": "Jane Updated",
    "phone": "+255755999888"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 15,
        "name": "Jane Updated",
        "email": "jane@example.com",
        "phone": "+255755999888"
    }
}
```

---

## Browse Coasters

### Get All Coasters (Map View)

Retrieve all coasters with their locations and availability status.

```http
GET /api/special-hire/customer/coasters
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `date` | date | No | Check availability for specific date (YYYY-MM-DD) |
| `time` | time | No | Check availability for specific time (HH:MM) |

**Example Request:**
```http
GET /api/special-hire/customer/coasters?date=2024-12-25&time=08:00
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Luxury Coaster A",
            "plate_number": "T 123 ABC",
            "capacity": 30,
            "model": "Toyota Coaster 2022",
            "color": "White",
            "features": "AC, WiFi, Reclining Seats",
            "image_url": "http://example.com/storage/coasters/coaster1.jpg",
            "latitude": -6.7924,
            "longitude": 39.2083,
            "is_available": true,
            "availability_status": "available",
            "status": "available",
            "pricing": {
                "id": 1,
                "coaster_id": 1,
                "base_price": 0,
                "price_per_km": 2500,
                "min_km": 20,
                "weekend_surcharge_percent": 15,
                "night_surcharge_percent": 20
            },
            "driver": {
                "id": 10,
                "name": "John Driver",
                "phone": "+255712345678",
                "email": "john.driver@example.com"
            }
        },
        {
            "id": 2,
            "name": "Premium Bus Alpha",
            "plate_number": "T 456 XYZ",
            "capacity": 45,
            "model": "Mercedes Sprinter 2023",
            "color": "Silver",
            "features": "AC, WiFi, USB Charging",
            "image_url": "http://example.com/storage/coasters/coaster2.jpg",
            "latitude": -6.8125,
            "longitude": 39.2890,
            "is_available": false,
            "availability_status": "busy",
            "status": "on_hire",
            "pricing": {
                "id": 2,
                "coaster_id": 2,
                "base_price": 0,
                "price_per_km": 3000,
                "min_km": 25,
                "weekend_surcharge_percent": 20,
                "night_surcharge_percent": 25
            },
            "driver": {
                "id": 11,
                "name": "Jane Driver",
                "phone": "+255713456789",
                "email": "jane.driver@example.com"
            }
        }
    ]
}
```

**Availability Status:**
- `available` (🟢 Green) - Coaster is free and can be booked
- `busy` (🔴 Red) - Coaster has an order at that time or is on hire

**Driver Information:**
- If a driver is assigned to the coaster, their information will be included in the response
- Driver object contains: `id`, `name`, `phone`, `email`
- If no driver account exists but legacy driver info is available, only `name` and `phone` will be returned
- Some coasters may not have driver information assigned yet

**UI tips (Map):**
- Plot markers using `latitude`/`longitude`; color by `availability_status`.
- Use `pricing.base_price` + `pricing.price_per_km` as the short price summary on the card/tooltip.
- Display driver name and contact if available for customer confidence.
- Disable or gray out "Book" CTAs for `busy` items to avoid failed submissions.
- If `date`/`time` filters are set, surface them in the UI so users know availability is time-bound.

---

### Get Single Coaster

Retrieve details of a specific coaster.

```http
GET /api/special-hire/customer/coasters/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Luxury Coaster A",
        "plate_number": "T 123 ABC",
        "capacity": 30,
        "model": "Toyota Coaster 2022",
        "color": "White",
        "features": "AC, WiFi, Reclining Seats",
        "image_url": "http://example.com/storage/coasters/coaster1.jpg",
        "status": "available",
        "pricing": {
            "id": 1,
            "coaster_id": 1,
            "base_price": 0,
            "price_per_km": 2500,
            "min_km": 20,
            "weekend_surcharge_percent": 15,
            "night_surcharge_percent": 20
        },
        "driver": {
            "id": 10,
            "name": "John Driver",
            "phone": "+255712345678",
            "email": "john.driver@example.com"
        }
    }
}
```

---

## Price Calculation

### Calculate Trip Price

Calculate the price for a trip before booking.

```http
POST /api/special-hire/customer/calculate-price
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `coaster_id` | integer | Yes | ID of the coaster |
| `pickup_latitude` | numeric | Conditional* | Pickup latitude |
| `pickup_longitude` | numeric | Conditional* | Pickup longitude |
| `dropoff_latitude` | numeric | Conditional* | Drop-off latitude |
| `dropoff_longitude` | numeric | Conditional* | Drop-off longitude |
| `distance_km` | numeric | Conditional* | Manual distance in km |
| `hire_date` | date | Yes | Date of hire (YYYY-MM-DD) |
| `hire_time` | time | Yes | Time of hire (HH:MM) |

*Either provide coordinates (all 4) OR distance_km

**Example Request (with coordinates):**
```json
{
    "coaster_id": 1,
    "pickup_latitude": -6.7724,
    "pickup_longitude": 39.2283,
    "dropoff_latitude": -6.8167,
    "dropoff_longitude": 39.2833,
    "hire_date": "2024-12-28",
    "hire_time": "14:00"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "coaster": {
            "id": 1,
            "name": "Luxury Coaster A",
            "capacity": 30
        },
        "distance_km": 25,
        "billable_km": 25,
        "breakdown": {
            "base_price": 0,
            "price_per_km": 2500,
            "km_amount": 62500,
            "surcharge_percent": 0,
            "surcharge_labels": [],
            "surcharge_amount": 0
        },
        "total_amount": 62500,
        "currency": "TZS"
    }
}
```

**Response with Surcharges (Weekend/Night):**
```json
{
    "success": true,
    "data": {
        "coaster": {
            "id": 1,
            "name": "Luxury Coaster A",
            "capacity": 30
        },
        "distance_km": 50,
        "billable_km": 50,
        "breakdown": {
            "base_price": 0,
            "price_per_km": 2500,
            "km_amount": 125000,
            "surcharge_percent": 35,
            "surcharge_labels": ["Weekend (+15%)", "Night (+20%)"],
            "surcharge_amount": 43750
        },
        "total_amount": 168750,
        "currency": "TZS"
    }
}
```

**UI tips (Pricing):**
- If the user selects pickup/drop-off on the map, pass all four coordinates so distance is computed automatically; otherwise, show a manual `distance_km` field.
- Display both `distance_km` and `billable_km` to explain minimums.
- Surface `breakdown.surcharge_labels` so users understand why the price changed (e.g., weekend or night).
- Remember: There is NO base price. Total = (distance × price_per_km) + surcharges.

---

## Bookings Management

### Create Booking

Create a new booking request for a coaster.

```http
POST /api/special-hire/customer/bookings
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `coaster_id` | integer | Yes | ID of the coaster to book |
| `pickup_location` | string | Yes | Pickup address |
| `pickup_latitude` | numeric | No | Pickup latitude |
| `pickup_longitude` | numeric | No | Pickup longitude |
| `dropoff_location` | string | Yes | Drop-off address |
| `dropoff_latitude` | numeric | No | Drop-off latitude |
| `dropoff_longitude` | numeric | No | Drop-off longitude |
| `hire_date` | date | Yes | Date of hire (YYYY-MM-DD, today or future) |
| `hire_time` | time | Yes | Pickup time (HH:MM) |
| `return_date` | date | No | Return date (YYYY-MM-DD) |
| `return_time` | time | No | Return time (HH:MM) |
| `passengers_count` | integer | Yes | Number of passengers (min: 1) |
| `purpose` | string | No | Purpose of hire |
| `notes` | string | No | Additional notes |
| `distance_km` | numeric | Yes | Distance in kilometers calculated by app |
| `total_amount` | numeric | Yes | Total amount calculated by app (distance × price_per_km + surcharges) |

**Example Request:**
```json
{
    "coaster_id": 1,
    "pickup_location": "Dar es Salaam, Mlimani City",
    "pickup_latitude": -6.7724,
    "pickup_longitude": 39.2283,
    "dropoff_location": "Zanzibar Ferry Terminal",
    "dropoff_latitude": -6.8167,
    "dropoff_longitude": 39.2833,
    "hire_date": "2024-12-28",
    "hire_time": "06:00",
    "return_date": "2024-12-28",
    "return_time": "10:00",
    "passengers_count": 20,
    "purpose": "Airport Transfer",
    "notes": "Please arrive 10 minutes early",
    "distance_km": 25.5,
    "total_amount": 63750
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Booking request created successfully",
    "data": {
        "id": 25,
        "order_code": "SH-20241219-025",
        "coaster_id": 1,
        "customer_name": "Jane Customer",
        "customer_phone": "+255755123456",
        "customer_email": "jane@example.com",
        "pickup_location": "Dar es Salaam, Mlimani City",
        "dropoff_location": "Zanzibar Ferry Terminal",
        "hire_date": "2024-12-28",
        "hire_time": "06:00:00",
        "passengers_count": 20,
        "distance_km": 25.5,
        "total_amount": 63750,
        "order_status": "pending",
        "payment_status": "pending",
        "coaster": {
            "id": 1,
            "name": "Luxury Coaster A",
            "plate_number": "T 123 ABC"
        }
    }
}
```

---

### Get Customer Bookings

Retrieve all bookings made by the customer.

```http
GET /api/special-hire/customer/bookings
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | Filter by status: `pending`, `confirmed`, `in_progress`, `completed`, `cancelled` |
| `per_page` | integer | No | Items per page (default: 15) |

**Example Request:**
```http
GET /api/special-hire/customer/bookings?status=confirmed
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 25,
                "order_code": "SH-20241219-025",
                "coaster_id": 1,
                "customer_name": "Jane Customer",
                "pickup_location": "Dar es Salaam, Mlimani City",
                "dropoff_location": "Zanzibar Ferry Terminal",
                "hire_date": "2024-12-28",
                "hire_time": "06:00:00",
                "passengers_count": 20,
                "total_amount": 62500,
                "order_status": "confirmed",
                "payment_status": "paid",
                "coaster": {
                    "id": 1,
                    "name": "Luxury Coaster A",
                    "plate_number": "T 123 ABC",
                    "capacity": 30
                }
            }
        ],
        "per_page": 15,
        "total": 8
    }
}
```

---

### Get Single Booking

Retrieve details of a specific booking.

```http
GET /api/special-hire/customer/bookings/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 25,
        "order_code": "SH-20241219-025",
        "customer_name": "Jane Customer",
        "customer_phone": "+255755123456",
        "customer_email": "jane@example.com",
        "pickup_location": "Dar es Salaam, Mlimani City",
        "pickup_latitude": -6.7724,
        "pickup_longitude": 39.2283,
        "dropoff_location": "Zanzibar Ferry Terminal",
        "dropoff_latitude": -6.8167,
        "dropoff_longitude": 39.2833,
        "hire_date": "2024-12-28",
        "hire_time": "06:00:00",
        "return_date": "2024-12-28",
        "return_time": "10:00:00",
        "passengers_count": 20,
        "purpose": "Airport Transfer",
        "notes": "Please arrive 10 minutes early",
        "distance_km": 25,
        "base_price": 0,
        "price_per_km": 2500,
        "km_amount": 62500,
        "surcharge_percent": 0,
        "surcharge_amount": 0,
        "total_amount": 62500,
        "order_status": "confirmed",
        "payment_status": "paid",
        "payment_method": "M-Pesa",
        "coaster": {
            "id": 1,
            "name": "Luxury Coaster A",
            "plate_number": "T 123 ABC",
            "capacity": 30
        }
    }
}
```

---

### Cancel Booking

Cancel a booking request.

```http
POST /api/special-hire/customer/bookings/{id}/cancel
```

**Response:**
```json
{
    "success": true,
    "message": "Booking cancelled successfully",
    "data": {
        "id": 25,
        "order_code": "SH-20241219-025",
        "order_status": "cancelled",
        "...": "..."
    }
}
```

**Error Response (400):**
```json
{
    "success": false,
    "message": "Cannot cancel completed booking"
}
```

**Note:** Only bookings with status `pending`, `confirmed`, or `in_progress` can be cancelled. Full payment (`balance_paid_at`) blocks cancel.

**UI tips (Bookings):**
- When showing a booking card, include `order_status`, `payment_status`, and `total_amount` together to reduce confusion.
- Gate the “Cancel” button to statuses `pending|confirmed|in_progress`; hide/disable otherwise.
- If coordinates are present, render pickup/drop-off pins and draw a line between them for quick visual context.

---

### Reorder (prefill)

Return fields to start a **new** booking from an existing one. Does **not** create a payment or a new order.

```http
POST /api/special-hire/customer/bookings/{id}/reorder
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "source_order_id": 25,
        "source_order_code": "SH-20241219-025",
        "coaster_id": 1,
        "pickup_location": "Dar es Salaam",
        "dropoff_location": "Arusha",
        "hire_date": "2024-12-20",
        "hire_time": "08:00",
        "passengers_count": 20,
        "notes": null,
        "...": "coords, purpose, distance_km, customer contact"
    }
}
```

---

### Transfer booking

Reassign the hire to another **available** coaster. Pricing and payment records are unchanged; `coaster_id` and operator `user_id` update. Blocked when `order_status` is `completed` or `cancelled`.

```http
POST /api/special-hire/customer/bookings/{id}/transfer
```

**Request Body:**
```json
{
    "coaster_id": 4
}
```

**Response (200):** updated booking payload (same shape as get booking).

---

### Refund request

For paid (or deposit-paid) orders that are not already `refunded` / `refund_pending`. Sets `payment_status` to `refund_pending` and appends reason/contact notes.

```http
POST /api/special-hire/customer/bookings/{id}/refund-request
```

**Request Body (all optional):**
```json
{
    "reason": "Trip cancelled by customer",
    "phone": "+2557...",
    "bank": "CRDB 0123456789"
}
```

**Response (200):** updated booking with `payment_status: "refund_pending"`.

---

### Receipt PDF

Download or inline-print the customer hire receipt (same template as admin). Allowed when `payment_status` is `paid` / `refund_pending`, or deposit/balance has been paid.

```http
GET /api/special-hire/customer/bookings/{id}/receipt.pdf
```

Returns PDF attachment (`Content-Disposition: attachment`).

```http
GET /api/special-hire/customer/bookings/{id}/receipt?disposition=inline
```

Returns PDF for browser print (`disposition=attachment` forces download).

---

## Tracking

### Track Booking

Get real-time location of the coaster for an active booking.

```http
GET /api/special-hire/customer/bookings/{id}/track
```

**Response:**
```json
{
    "success": true,
    "data": {
        "order_id": 25,
        "order_code": "SH-20241219-025",
        "order_status": "in_progress",
        "coaster": {
            "id": 1,
            "name": "Luxury Coaster A",
            "plate_number": "T 123 ABC",
            "latitude": -6.7935,
            "longitude": 39.2095,
            "last_location_update": "2024-12-19T10:35:00.000000Z"
        }
    }
}
```

**UI tips (Tracking & Map):**
- Start polling every 15–30 seconds when `order_status` is `in_progress`; stop when it becomes `completed` or `cancelled`.
- Update the existing map marker instead of adding new ones to avoid clutter; show `last_location_update` as “last seen” text.
- If location is temporarily missing, keep the last known point but indicate it is stale.

---

## Logout

### Customer Logout

Logout and invalidate the current access token.

```http
POST /api/special-hire/customer/logout
```

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

## Error Responses

### Standard Error Format

```json
{
    "success": false,
    "message": "Error description"
}
```

### Validation Errors (422)

```json
{
    "success": false,
    "errors": {
        "field_name": [
            "The field_name is required.",
            "The field_name must be a valid email address."
        ]
    }
}
```

### Common HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success |
| `201` | Created successfully |
| `400` | Bad Request (business logic error) |
| `401` | Unauthorized (missing or invalid token) |
| `403` | Forbidden (not a customer or wrong role) |
| `404` | Resource not found |
| `422` | Validation error |
| `500` | Server error |

---

## Pricing Calculation Logic

### Formula

```
Total = (Billable KM × Price per KM) + Surcharges

Where:
- Billable KM = max(Actual KM, Min KM)
- Surcharges = KM Amount × Surcharge Percentage
- NO BASE PRICE is included in the calculation
```

### Surcharge Rules

| Condition | Surcharge |
|-----------|-----------|
| Weekend (Saturday, Sunday) | +weekend_surcharge_percent |
| Night (18:00 - 06:00) | +night_surcharge_percent |
| Both conditions | Surcharges are cumulative |

---

## Usage Notes

1. **Registration**: Customers must register before making bookings.

2. **Map View**: Use the `/coasters` endpoint with date and time parameters to see real-time availability on a map.

3. **Availability Colors**:
   - 🟢 **Green (available)**: Coaster is free and can be booked
   - 🔴 **Red (busy)**: Coaster has an order or is on hire

4. **Booking Flow**:
   - Customer uses `/calculate-price` to get estimated price
   - Customer app calculates: `distance × price_per_km + surcharges`
   - Customer app sends booking request with `distance_km` and `total_amount`
   - Server accepts the calculated amount from the customer app
   - Status: `pending` → Admin confirms → `confirmed` → Driver starts → `in_progress` → `completed`

5. **Price Calculation**: 
   - Customer app should call `/calculate-price` endpoint first
   - Customer app calculates final amount based on distance × price_per_km
   - Customer app includes both `distance_km` and `total_amount` in booking request
   - No base price is included in calculations

6. **Payment**: Payment processing is handled separately. Contact admin for payment methods.

7. **Tracking**: Real-time tracking is available for bookings with status `in_progress`.

---

**Version:** 1.0.0  
**Last Updated:** December 19, 2024

For support, contact: api-support@example.com


