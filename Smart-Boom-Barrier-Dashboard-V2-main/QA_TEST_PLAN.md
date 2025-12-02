# QA Test Plan - ESP32 RFID Customer Access System

**Test Date:** 2025-12-02
**Version:** v2.0 - Vehicle Plate Feature
**Tester:** _____________

---

## Pre-Test Setup

### 1. Start the Server

Open PowerShell/Command Prompt and run:
```bash
cd "Smart-Boom-Barrier-Dashboard-V2-main\data"
php -S localhost:8000
```

Keep this terminal open. You should see:
```
PHP 8.2.12 Development Server (http://localhost:8000) started
```

### 2. Open Browser

Navigate to: **http://localhost:8000/login.html**

---

## Test Suite 1: Authentication & Admin Access

### Test 1.1: Admin Login ✅
**Steps:**
1. Open http://localhost:8000/login.html
2. Enter credentials:
   - Username: `admin`
   - Password: `admin`
3. Click "Login"

**Expected Result:**
- ✅ Redirects to index.html (main dashboard)
- ✅ Shows admin interface

**Status:** [ ] PASS [ ] FAIL
**Notes:** _________________

---

## Test Suite 2: Customer Registration with Vehicle Plate

### Test 2.1: Access Registration Page ✅
**Steps:**
1. From main dashboard, click "Admin Dashboard"
2. Click "+ Register New Card"

**Expected Result:**
- ✅ Opens register_rfid.html
- ✅ Form displays all fields including **"Vehicle Plate Number"**

**Status:** [ ] PASS [ ] FAIL

### Test 2.2: Register New Customer ✅
**Steps:**
1. In registration form, enter:
   - Card UID: `TEST-001`
   - Card Holder Name: `John Doe`
   - Email: `john@test.com`
   - Phone: `+1234567890`
   - **Vehicle Plate Number: `ABC-1234`** ← NEW FIELD
   - Department: `Engineering`
   - Notes: `Test customer`

2. Watch the **Preview** section update

**Expected Result:**
- ✅ Preview shows:
  - UID: TEST-001
  - Name: John Doe
  - **Vehicle: ABC-1234** ← NEW
  - Department: Engineering

**Status:** [ ] PASS [ ] FAIL

### Test 2.3: Submit Registration ✅
**Steps:**
1. Click "Register Card"

**Expected Result:**
- ✅ Success message: "✅ Card registered: TEST-001"
- ✅ Form clears after 2 seconds

**Status:** [ ] PASS [ ] FAIL

### Test 2.4: Verify Data Storage ✅
**Steps:**
1. Open file: `Smart-Boom-Barrier-Dashboard-V2-main\data\data\customers.json`
2. Check contents

**Expected Result:**
- ✅ File exists
- ✅ Contains entry for TEST-001
- ✅ **vehicle_plate field present with value "ABC-1234"**

**Example:**
```json
{
  "TEST-001": {
    "card_uid": "TEST-001",
    "name": "John Doe",
    "email": "john@test.com",
    "phone": "+1234567890",
    "department": "Engineering",
    "vehicle_plate": "ABC-1234",
    "notes": "Test customer",
    "registered_at": "2025-12-02T...",
    "registered_by": "admin"
  }
}
```

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 3: Check-In/Out Functionality

### Test 3.1: Simulate Check-In ✅
**Steps:**
1. Navigate to http://localhost:8000/index.html
2. Find the registry/check-in section
3. Enter UID: `TEST-001`
4. Click toggle/check-in button

**Expected Result:**
- ✅ registry.json created/updated with 'in' timestamp
- ✅ Success message displayed

**Status:** [ ] PASS [ ] FAIL

### Test 3.2: Verify registry.json ✅
**Steps:**
1. Open file: `Smart-Boom-Barrier-Dashboard-V2-main\data\data\registry.json`

**Expected Result:**
```json
{
  "TEST-001": {
    "in": "2025-12-02T10:30:00+00:00",
    "out": null
  }
}
```

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 4: Real-Time Vehicles Display (NEW FEATURE)

### Test 4.1: Access Vehicles Display ✅
**Steps:**
1. Go to Admin Dashboard (admin.html)
2. Click the green **"🚗 Vehicles Inside"** button

**Expected Result:**
- ✅ Opens vehicles_inside.html
- ✅ Page loads without errors

**Status:** [ ] PASS [ ] FAIL

### Test 4.2: Verify Vehicle Display ✅
**Steps:**
1. On vehicles_inside.html, observe the page

**Expected Result:**
- ✅ Vehicle count badge shows: **1**
- ✅ Vehicle card displays:
  - **Name:** John Doe
  - **Vehicle Plate:** ABC-1234 (in blue badge)
  - **Card UID:** TEST-001
  - **Department:** Engineering
  - **Check-in Time:** (timestamp)
  - **Duration:** (e.g., "5m" in green)

**Status:** [ ] PASS [ ] FAIL

### Test 4.3: Auto-Refresh Countdown ✅
**Steps:**
1. Watch the top of the page for 5+ seconds

**Expected Result:**
- ✅ Countdown displays: "Auto-refresh: 5s" → "4s" → "3s" → "2s" → "1s" → "5s"
- ✅ Duration updates every 5 seconds

**Status:** [ ] PASS [ ] FAIL

### Test 4.4: Test Check-Out ✅
**Steps:**
1. Open index.html in another tab
2. Enter UID: `TEST-001` and toggle check-out
3. Return to vehicles_inside.html
4. Wait for auto-refresh (5s)

**Expected Result:**
- ✅ Vehicle card disappears
- ✅ Count badge shows: **0**
- ✅ Empty state displays: "No Vehicles Inside"

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 5: Excel Export Functionality (NEW FEATURE)

### Test 5.1: Export Registry to Excel ✅
**Steps:**
1. Navigate to http://localhost:8000/admin_audit.php
2. Verify export buttons are visible
3. Click **"📊 Export Registry to Excel"**

**Expected Result:**
- ✅ Two export buttons visible:
  - Green: "📥 Export Audit Log to Excel"
  - Blue: "📊 Export Registry to Excel"
- ✅ CSV file downloads: `registry_2025-12-02_HHMMSS.csv`
- ✅ Open in Excel - verify columns:
  - Card UID
  - Name
  - **Vehicle Plate** ← NEW COLUMN
  - Department
  - Check In
  - Check Out
  - Duration

**Status:** [ ] PASS [ ] FAIL

### Test 5.2: Verify Excel Data ✅
**Steps:**
1. Open the downloaded CSV in Excel
2. Check TEST-001 row

**Expected Result:**
- ✅ Row contains:
  - UID: TEST-001
  - Name: John Doe
  - **Vehicle Plate: ABC-1234**
  - Department: Engineering
  - Check In: (timestamp)
  - Check Out: (timestamp or empty)
  - Duration: (calculated or "Still inside")

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 6: Navigation & UI

### Test 6.1: Navigation Links ✅
**Steps:**
Test all navigation links:
- [ ] Login → Main Dashboard
- [ ] Main Dashboard → Admin Dashboard
- [ ] Admin Dashboard → Register New Card
- [ ] Admin Dashboard → **Vehicles Inside** (NEW)
- [ ] Admin Dashboard → Back to Dashboard
- [ ] Register Card → Admin Dashboard
- [ ] Vehicles Inside → Admin Dashboard
- [ ] Vehicles Inside → Main Dashboard

**Expected Result:**
- ✅ All links work
- ✅ No 404 errors

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 7: Edge Cases & Error Handling

### Test 7.1: Duplicate Registration ✅
**Steps:**
1. Try to register same UID again: `TEST-001`

**Expected Result:**
- ✅ Error message: "uid_already_registered"

**Status:** [ ] PASS [ ] FAIL

### Test 7.2: Empty Vehicle Plate ✅
**Steps:**
1. Register card with all fields except vehicle plate

**Expected Result:**
- ✅ Registration succeeds
- ✅ vehicle_plate stored as empty string
- ✅ Display shows "N/A" for missing plate

**Status:** [ ] PASS [ ] FAIL

### Test 7.3: Special Characters in Plate ✅
**Steps:**
1. Register card with plate: `AB-12!@#`

**Expected Result:**
- ✅ Registration succeeds
- ✅ Special characters stored correctly

**Status:** [ ] PASS [ ] FAIL

### Test 7.4: Empty Vehicles Display ✅
**Steps:**
1. Ensure all vehicles are checked out
2. Visit vehicles_inside.html

**Expected Result:**
- ✅ Count shows: **0**
- ✅ Empty state displays:
  - "No Vehicles Inside"
  - "All vehicles have checked out."

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 8: Performance & Reliability

### Test 8.1: Multiple Vehicles ✅
**Steps:**
1. Register 3 more test vehicles:
   - TEST-002, Jane Smith, XYZ-5678
   - TEST-003, Bob Wilson, DEF-9012
   - TEST-004, Alice Brown, GHI-3456
2. Check all 4 vehicles in
3. View vehicles_inside.html

**Expected Result:**
- ✅ Count badge shows: **4**
- ✅ All 4 vehicles display correctly
- ✅ Grid layout responsive
- ✅ All vehicle plates visible

**Status:** [ ] PASS [ ] FAIL

### Test 8.2: Excel Export with Multiple Records ✅
**Steps:**
1. Export registry with multiple records

**Expected Result:**
- ✅ All 4 vehicles in CSV
- ✅ **All vehicle plates present**
- ✅ Opens correctly in Excel

**Status:** [ ] PASS [ ] FAIL

---

## Test Suite 9: Mobile Responsiveness

### Test 9.1: Responsive Layout ✅
**Steps:**
1. Open browser DevTools (F12)
2. Toggle device toolbar
3. Test on:
   - [ ] iPhone SE (375px)
   - [ ] iPad (768px)
   - [ ] Desktop (1920px)

**Expected Result:**
- ✅ Registration form readable
- ✅ Vehicle cards stack properly
- ✅ Buttons accessible
- ✅ No horizontal scroll

**Status:** [ ] PASS [ ] FAIL

---

## Final Verification Checklist

- [ ] All new features implemented correctly
- [ ] Vehicle plate field present in registration
- [ ] Vehicle plate visible in real-time display
- [ ] Vehicle plate included in Excel exports
- [ ] Navigation links work
- [ ] Auto-refresh functions properly
- [ ] Error handling works
- [ ] No console errors
- [ ] Performance acceptable
- [ ] Mobile responsive

---

## Test Summary

**Total Tests:** 25
**Passed:** ___
**Failed:** ___
**Blocked:** ___

**Critical Issues Found:**
1. _________________
2. _________________

**Minor Issues Found:**
1. _________________
2. _________________

**Recommendations:**
_________________

**Overall Status:** [ ] READY FOR PRODUCTION [ ] NEEDS FIXES

**Tester Signature:** _____________ **Date:** _____________
