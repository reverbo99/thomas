# Customer App Integration Guide - Special Hire Booking

## Overview
The customer app is responsible for calculating the total booking amount based on distance and price per km, then sending it to the server.

## Booking Flow

### Step 1: Get Available Coasters
```http
GET /api/special-hire/customer/coasters?date=2024-12-28&time=14:00
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Luxury Coaster A",
      "pricing": {
        "price_per_km": 2500,
        "min_km": 20,
        "weekend_surcharge_percent": 15,
        "night_surcharge_percent": 20
      },
      "latitude": -6.7924,
      "longitude": 39.2083,
      "is_available": true,
      "driver": {
        "id": 10,
        "name": "John Driver",
        "phone": "+255712345678",
        "email": "john.driver@example.com"
      }
    }
  ]
}
```

**Note:** The `driver` object is included if a driver is assigned to the coaster. Use this information to display driver details to build customer confidence.

### Step 2: Calculate Distance
Use GPS coordinates to calculate distance between pickup and dropoff:
- Use Haversine formula or Google Distance Matrix API
- Example: From (-6.7924, 39.2083) to (-6.8150, 39.2800) = 25.5 km

### Step 3: Calculate Total Amount (Customer App Logic)

```javascript
// Pricing data from coaster
const pricePerKm = 2500;
const minKm = 20;
const weekendSurcharge = 15; // percentage
const nightSurcharge = 20;   // percentage

// Distance calculated by app
const actualDistance = 25.5; // km
const billableKm = Math.max(actualDistance, minKm);

// Base calculation (NO BASE PRICE)
let totalAmount = billableKm * pricePerKm;

// Check for surcharges
let surchargePercent = 0;

// Weekend check (Saturday = 6, Sunday = 0)
const dayOfWeek = new Date(hireDate).getDay();
if (dayOfWeek === 0 || dayOfWeek === 6) {
  surchargePercent += weekendSurcharge;
}

// Night check (6PM to 6AM)
const hour = parseInt(hireTime.split(':')[0]);
if (hour >= 18 || hour < 6) {
  surchargePercent += nightSurcharge;
}

// Apply surcharges
if (surchargePercent > 0) {
  const surchargeAmount = totalAmount * (surchargePercent / 100);
  totalAmount += surchargeAmount;
}

// Example results:
// 25.5 km × 2500 = 63,750 TZS (no surcharges)
// 25.5 km × 2500 = 63,750 + 35% = 86,062.50 TZS (weekend + night)
```

### Step 4: (Optional) Verify with Server
You can optionally call the price calculator to verify your calculation:

```http
POST /api/special-hire/customer/calculate-price
Authorization: Bearer {token}
Content-Type: application/json

{
  "coaster_id": 1,
  "pickup_latitude": -6.7924,
  "pickup_longitude": 39.2083,
  "dropoff_latitude": -6.8150,
  "dropoff_longitude": 39.2800,
  "hire_date": "2024-12-28",
  "hire_time": "14:00"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "distance_km": 25.5,
    "billable_km": 25.5,
    "breakdown": {
      "base_price": 0,
      "price_per_km": 2500,
      "km_amount": 63750,
      "surcharge_percent": 0,
      "surcharge_amount": 0
    },
    "total_amount": 63750
  }
}
```

### Step 5: Create Booking with Calculated Amount

**IMPORTANT:** Send both `distance_km` and `total_amount` in the request.

```http
POST /api/special-hire/customer/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
  "coaster_id": 1,
  "pickup_location": "Dar es Salaam Airport",
  "pickup_latitude": -6.7924,
  "pickup_longitude": 39.2083,
  "dropoff_location": "City Center",
  "dropoff_latitude": -6.8150,
  "dropoff_longitude": 39.2800,
  "hire_date": "2024-12-28",
  "hire_time": "14:00",
  "passengers_count": 5,
  "distance_km": 25.5,
  "total_amount": 63750,
  "purpose": "Airport Transfer",
  "notes": "Please arrive 10 minutes early"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Booking request created successfully",
  "data": {
    "id": 25,
    "order_code": "SH-20241228-025",
    "coaster_id": 1,
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

## Required Fields for Booking

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `coaster_id` | integer | ✅ Yes | ID of the coaster |
| `pickup_location` | string | ✅ Yes | Pickup address/location name |
| `dropoff_location` | string | ✅ Yes | Dropoff address/location name |
| `hire_date` | date | ✅ Yes | Date in YYYY-MM-DD format |
| `hire_time` | time | ✅ Yes | Time in HH:MM format (24-hour) |
| `passengers_count` | integer | ✅ Yes | Number of passengers |
| `distance_km` | numeric | ✅ Yes | Distance calculated by app |
| `total_amount` | numeric | ✅ Yes | Total amount calculated by app |
| `pickup_latitude` | numeric | No | GPS latitude of pickup |
| `pickup_longitude` | numeric | No | GPS longitude of pickup |
| `dropoff_latitude` | numeric | No | GPS latitude of dropoff |
| `dropoff_longitude` | numeric | No | GPS longitude of dropoff |
| `return_date` | date | No | Return date if round trip |
| `return_time` | time | No | Return time if round trip |
| `purpose` | string | No | Purpose of hire |
| `notes` | string | No | Additional instructions |

## Price Calculation Formula

```
Base Amount = distance_km × price_per_km

Surcharge = Base Amount × (surcharge_percent / 100)

Total Amount = Base Amount + Surcharge

Where surcharge_percent can be:
- 0% (normal hours, weekday)
- 15% (weekend: Saturday or Sunday)
- 20% (night: 6PM to 6AM)
- 35% (weekend + night)
```

## Examples

### Example 1: Weekday Morning (No Surcharge)
- Distance: 25.5 km
- Price per km: 2,500 TZS
- Date: Monday
- Time: 10:00 AM
- **Calculation:** 25.5 × 2,500 = **63,750 TZS**

### Example 2: Saturday Afternoon (Weekend Surcharge)
- Distance: 25.5 km
- Price per km: 2,500 TZS
- Date: Saturday
- Time: 2:00 PM
- Base: 63,750 TZS
- Surcharge: 63,750 × 0.15 = 9,562.50 TZS
- **Total: 73,312.50 TZS**

### Example 3: Tuesday Night (Night Surcharge)
- Distance: 25.5 km
- Price per km: 2,500 TZS
- Date: Tuesday
- Time: 10:00 PM
- Base: 63,750 TZS
- Surcharge: 63,750 × 0.20 = 12,750 TZS
- **Total: 76,500 TZS**

### Example 4: Saturday Night (Both Surcharges)
- Distance: 25.5 km
- Price per km: 2,500 TZS
- Date: Saturday
- Time: 8:00 PM
- Base: 63,750 TZS
- Surcharge: 63,750 × 0.35 = 22,312.50 TZS
- **Total: 86,062.50 TZS**

## Error Handling

### Common Errors

**Missing required field:**
```json
{
  "success": false,
  "errors": {
    "distance_km": ["The distance_km field is required."],
    "total_amount": ["The total_amount field is required."]
  }
}
```

**Invalid date (past date):**
```json
{
  "success": false,
  "errors": {
    "hire_date": ["The hire_date must be a date after or equal to today."]
  }
}
```

**Coaster not available:**
```json
{
  "success": false,
  "message": "Coaster not found or not available"
}
```

## Testing Checklist

- [ ] Calculate distance correctly using GPS coordinates
- [ ] Apply weekend surcharge (Saturday/Sunday)
- [ ] Apply night surcharge (6PM-6AM)
- [ ] Apply both surcharges when applicable
- [ ] Handle minimum distance requirement
- [ ] Display breakdown to user before booking
- [ ] Send both `distance_km` and `total_amount` in request
- [ ] Handle validation errors gracefully
- [ ] Show booking confirmation with order code

## Best Practices

1. **Always show price breakdown to user** before they confirm booking
2. **Highlight surcharges** with clear labels (Weekend +15%, Night +20%)
3. **Use server calculator** to verify calculations during testing
4. **Round amounts** appropriately for display (TZS doesn't use decimals typically)
5. **Cache pricing data** from coaster endpoint to avoid repeated calls
6. **Validate inputs** before sending to server
7. **Handle edge cases** like midnight bookings (consider as night rate)

---

**Last Updated:** December 20, 2025  
**API Version:** 1.0  
**Base URL:** `https://ticket.hisgc.co.tz`

