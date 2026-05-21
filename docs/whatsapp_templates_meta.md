# WhatsApp Cloud API Templates

These templates must be submitted to Meta Business Manager for approval before they can be sent via the WhatsApp Cloud API.

### 1. `booking_confirmed`
**Category:** Utility
**Language:** English (en)
**Body:**
Hi {{1}}, your parcel {{2}} from {{3}} to {{4}} has been booked with Colombo Cargo Connect.

Trip: {{5}}
Estimated delivery: {{6}}
Track here: {{7}}

Thank you for choosing CCC!

---

### 2. `parcel_picked_up`
**Category:** Utility
**Language:** English (en)
**Body:**
📦 Parcel {{1}} has been picked up.

From: {{2}}
To: {{3}}
Status: Picked up at {{4}}

Track live: {{5}}

---

### 3. `arrived_at_origin_hub`
**Category:** Utility
**Language:** English (en)
**Body:**
✅ Parcel {{1}} arrived at our {{2}} hub.

It will be loaded on the next outbound lorry.

Track: {{3}}

---

### 4. `in_transit`
**Category:** Utility
**Language:** English (en)
**Body:**
🚛 Parcel {{1}} is on its way!

Lorry departed {{2}} at {{3}}.
Expected arrival at {{4}}: {{5}}.

Track live: {{6}}

---

### 5. `arrived_at_destination_hub`
**Category:** Utility
**Language:** English (en)
**Body:**
🏢 Parcel {{1}} has arrived at our {{2}} hub.

You can collect it from {{3}} or wait for delivery.

Track: {{4}}

---

### 6. `out_for_delivery`
**Category:** Utility
**Language:** English (en)
**Body:**
🛵 Parcel {{1}} is out for delivery.

Driver: {{2}} ({{3}})
ETA: {{4}}

Please keep your NIC ready for verification.

Track: {{5}}

---

### 7. `delivered`
**Category:** Utility
**Language:** English (en)
**Body:**
✅ Parcel {{1}} has been delivered.

Received by: {{2}} (NIC: {{3}})
Delivered at: {{4}}

Thank you for using Colombo Cargo Connect!

---

### 8. `delivery_failed`
**Category:** Utility
**Language:** English (en)
**Body:**
⚠️ Delivery attempt failed for parcel {{1}}.

Reason: {{2}}
Next attempt: {{3}}

Reply to this message to update delivery instructions.

---

### 9. `cancelled`
**Category:** Utility
**Language:** English (en)
**Body:**
Parcel {{1}} has been cancelled.

Refund of LKR {{2}} will be processed within {{3}} business days.

Reference: {{4}}
