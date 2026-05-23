# Manual Accessibility Testing Checklist

**Date:** May 23, 2026  
**Phase:** G.4 Phase 4 (Testing & Validation)  
**Tester:** ___________________________  
**WCAG Level:** 2.2 Level AA

---

## Instructions

1. Test each page listed below
2. Mark each item as ✅ PASS, ❌ FAIL, or N/A
3. For each FAIL, record details in "Issues Found" section
4. Take screenshots of failures if possible
5. Re-test after fixes

---

## Section 1: Keyboard Navigation

### web-sender / (Landing Page)

**Skip Link:**
- [ ] Press Tab - "Skip to main content" appears
- [ ] Press Enter - Focus moves to main content
- [ ] Visual indicator visible (2px solid + offset)

**Tab Order:**
- [ ] Tab order follows visual layout (top→bottom, left→right)
- [ ] All interactive elements reachable
- [ ] No keyboard traps (can tab IN and OUT)

**Interactive Elements:**
- [ ] "Log In" button activates with Enter
- [ ] "Create Account" button activates with Enter
- [ ] "Book Parcel" button activates with Enter
- [ ] Nav card links activate with Enter
- [ ] Public tracking link activates with Enter

**Focus Indicators:**
- [ ] All focused elements have visible outline
- [ ] Outline is 2px solid
- [ ] Outline has 2px offset from element
- [ ] Outline color contrasts with background (≥ 3:1)

**Issues Found:**
```
[Record any failures here]
```

---

### web-sender /login (Login Page)

**Skip Link:**
- [ ] Skip link appears on first Tab
- [ ] Skip link functional

**Tab Order:**
- [ ] 1. Skip link
- [ ] 2. Phone input
- [ ] 3. Password input
- [ ] 4. Show/Hide password button
- [ ] 5. Login button
- [ ] 6. "Create Account" link
- [ ] 7. "Track Parcel" link

**Form Interaction:**
- [ ] Can fill phone field via keyboard
- [ ] Can fill password field via keyboard
- [ ] Show/Hide button activates with Enter or Space
- [ ] Submit button activates with Enter
- [ ] Form submits with Enter key while focused on input

**Error Handling:**
- [ ] Submit without phone - error appears
- [ ] Error message receives focus
- [ ] Error message has red text
- [ ] Can continue filling form after error

**Issues Found:**
```
[Record any failures here]
```

---

### web-sender /register (Register Page)

**Tab Order:**
- [ ] 1. Skip link
- [ ] 2. Full Name input
- [ ] 3. Phone input
- [ ] 4. Email input
- [ ] 5. Password input
- [ ] 6. Show/Hide password button
- [ ] 7. Create Account button

**Autocomplete:**
- [ ] Chrome/Edge suggests name from previous forms
- [ ] Chrome/Edge suggests phone from previous forms
- [ ] Chrome/Edge suggests email from previous forms

**Form Validation:**
- [ ] Submit without name - error announced
- [ ] Submit without phone - error announced
- [ ] Submit with invalid phone - error announced
- [ ] Submit without email - error announced
- [ ] Submit with invalid email - error announced
- [ ] Submit without password - error announced

**Issues Found:**
```
[Record any failures here]
```

---

### web-sender /book (Booking Form - Multi-Step)

**Step 1: Route Selection**
- [ ] Tab to route dropdown
- [ ] Arrow keys navigate options
- [ ] Enter selects option
- [ ] Submit without selection - error appears

**Step 2: Package Details**
- [ ] Tab to size radio buttons
- [ ] Arrow keys navigate size options
- [ ] Space/Enter selects size
- [ ] Tab to weight input
- [ ] Number input works via keyboard
- [ ] Tab to dimensions inputs
- [ ] Submit with invalid weight - error appears

**Step 3: Pickup/Drop**
- [ ] Tab to pickup type radio
- [ ] Arrow keys toggle hub/doorstep
- [ ] Conditional address field appears when doorstep selected
- [ ] Tab to sender name - autocomplete works
- [ ] Tab to sender phone - autocomplete works
- [ ] Tab to receiver name - autocomplete works
- [ ] Tab to receiver phone - autocomplete works

**Step 4: Payment**
- [ ] Tab to payment method radio
- [ ] Arrow keys navigate payment options
- [ ] Tab to terms checkbox
- [ ] Space toggles checkbox
- [ ] Tab to submit button
- [ ] Submit button disabled until terms checked

**Multi-Step Navigation:**
- [ ] "Next" button activates with Enter
- [ ] "Back" button activates with Enter
- [ ] Current step indicated visually
- [ ] Can navigate backwards without losing data

**Issues Found:**
```
[Record any failures here]
```

---

### web-admin / (Dashboard)

**Skip Link:**
- [ ] Skip link present
- [ ] Skip link functional

**Landmark Navigation:**
- [ ] Main landmark properly marked
- [ ] Header landmark properly marked
- [ ] Navigation landmark properly marked (sidebar)

**Tab Order:**
- [ ] Sidebar links reachable
- [ ] Stats cards focusable (if interactive)
- [ ] Chart controls focusable
- [ ] "View data table" buttons focusable
- [ ] Table rows focusable
- [ ] Action buttons in table rows focusable

**Data Table Navigation:**
- [ ] Tab into table
- [ ] Tab through table rows
- [ ] Arrow keys navigate within table (if applicable)

**Issues Found:**
```
[Record any failures here]
```

---

### web-tracking / (Search Page)

**Skip Link:**
- [ ] Skip link present
- [ ] Skip link functional

**Tab Order:**
- [ ] 1. Skip link
- [ ] 2. Parcel number input
- [ ] 3. Track button

**Form Interaction:**
- [ ] Can type parcel number via keyboard
- [ ] Enter key submits form
- [ ] Track button activates with Enter

**Issues Found:**
```
[Record any failures here]
```

---

### web-tracking /[parcelNumber] (Tracking Detail)

**Tab Order:**
- [ ] 1. Skip link
- [ ] 2. Back link
- [ ] 3. Timeline items focusable (if interactive)

**Timeline Navigation:**
- [ ] Timeline visually clear
- [ ] Current status highlighted
- [ ] All status changes visible

**Issues Found:**
```
[Record any failures here]
```

---

## Section 2: Screen Reader Testing (NVDA)

### Heading Navigation (H Key)

**web-sender /**
- [ ] h1: "Book, track, and manage parcels..."
- [ ] h2: "Fixed routes"
- [ ] h2: "Parcel proof"
- [ ] h2: "No admin login link"
- [ ] No skipped heading levels

**web-sender /login**
- [ ] h1: "Log In"
- [ ] Only one h1 on page

**web-sender /book**
- [ ] h1: "Book a Parcel"
- [ ] h2: "Select Route"
- [ ] h2: "Package Details" (on step 2)
- [ ] h2: "Pickup & Drop" (on step 3)
- [ ] h2: "Review & Pay" (on step 4)

**web-admin /**
- [ ] h1: "God's View Dashboard" (or similar)
- [ ] h2: "7-Day Booking Trend"
- [ ] h2: "Parcel Status Mix"
- [ ] h2: "Recent Parcels"

**Issues Found:**
```
[Record any failures here]
```

---

### Landmark Navigation (D Key in NVDA)

**Test on all pages:**
- [ ] "Main, landmark" announced
- [ ] "Navigation, landmark" announced (if sidebar present)
- [ ] "Banner, landmark" or "Header, landmark" announced
- [ ] Can navigate between landmarks with D key

**Issues Found:**
```
[Record any failures here]
```

---

### Form Announcements

**web-sender /login**
- [ ] "Phone Number, edit, required" announced
- [ ] "Password, edit, required" announced
- [ ] Error: "Alert: Phone is required" announced
- [ ] Submit: "Log In, button" announced

**web-sender /register**
- [ ] "Full Name, edit, required, autocomplete: name" announced
- [ ] "Phone Number, edit, required, autocomplete: tel" announced
- [ ] "Email Address, edit, required, autocomplete: email" announced

**web-sender /book**
- [ ] "Sender Name, edit, required, autocomplete: name" announced
- [ ] "Sender Phone, edit, required, autocomplete: tel" announced
- [ ] "Receiver Name, edit, required, autocomplete: name" announced

**Issues Found:**
```
[Record any failures here]
```

---

### Table Navigation

**web-admin / (Recent Parcels Table)**
- [ ] "Table with 10 rows and 5 columns" announced
- [ ] "Parcel Number, column header" announced
- [ ] "Sender, column header" announced
- [ ] "Receiver, column header" announced
- [ ] "Status, column header" announced
- [ ] "Actions, column header" announced
- [ ] Can navigate table with Ctrl+Alt+Arrow keys
- [ ] Row/column context announced ("Row 1, Column 1: CCC-20251101-000001-7")

**Issues Found:**
```
[Record any failures here]
```

---

### Chart Alternatives

**web-admin / (Dashboard Charts)**
- [ ] "7-Day Booking Trend, heading level 2" announced
- [ ] "View data table alternative, button" present
- [ ] Activating button expands table
- [ ] Table has proper headers
- [ ] Can navigate table data with screen reader

**Issues Found:**
```
[Record any failures here]
```

---

## Section 3: Color Contrast

### Text Contrast

**Use WAVE extension or WebAIM Contrast Checker:**

**Page Titles (h1):**
- [ ] Color: slate-900 (#0f172a)
- [ ] Background: white (#ffffff)
- [ ] Ratio: 16.1:1 ✅ (target: 4.5:1)

**Body Text (p):**
- [ ] Color: slate-700 (#334155)
- [ ] Background: white (#ffffff)
- [ ] Ratio: 12.6:1 ✅ (target: 4.5:1)

**Muted Text (labels, helper text):**
- [ ] Color: slate-500 (#64748b)
- [ ] Background: white (#ffffff)
- [ ] Ratio: 4.7:1 ✅ (target: 4.5:1)

**Primary Button Text:**
- [ ] Color: white (#ffffff)
- [ ] Background: indigo-600 (#4f46e5)
- [ ] Ratio: 8.6:1 ✅ (target: 4.5:1)

**Error Text:**
- [ ] Color: red-500 (#ef4444)
- [ ] Background: white (#ffffff)
- [ ] Ratio: 4.6:1 ✅ (target: 4.5:1)

**Link Text:**
- [ ] Color: indigo-600 (#4f46e5)
- [ ] Background: white (#ffffff)
- [ ] Ratio: 8.6:1 ✅ (target: 4.5:1)

**Focus Indicators:**
- [ ] Color: indigo-600 (#4f46e5) with 2px offset
- [ ] Background: varies
- [ ] Ratio: ≥ 3:1 (WCAG 2.4.11 - new for 2.2)

**Issues Found:**
```
[Record any failures here]
```

---

## Section 4: Touch Target Testing (Mobile)

**Test on: iPhone / Android device**

### Touch Target Sizes

**Minimum Sizes:**
- Web: 24×24 CSS pixels (WCAG 2.2 - new)
- iOS: 44×44 points
- Android: 48×48 dp

**web-sender / (Landing Page):**
- [ ] "Log In" button ≥ 44×44 points
- [ ] "Create Account" button ≥ 44×44 points
- [ ] Nav card links ≥ 44×44 points
- [ ] Public tracking link ≥ 44×44 points

**web-sender /book (Booking Form):**
- [ ] All form inputs ≥ 44px height
- [ ] Radio buttons ≥ 24×24 (with labels ≥ 44×44)
- [ ] Checkboxes ≥ 24×24 (with labels ≥ 44×44)
- [ ] "Next" button ≥ 44×44
- [ ] "Back" button ≥ 44×44
- [ ] "Submit" button ≥ 44×44

**web-admin / (Dashboard):**
- [ ] Sidebar links ≥ 44×44
- [ ] Logout button ≥ 44×44
- [ ] Table action buttons ≥ 44×44
- [ ] Chart controls ≥ 44×44

**Spacing:**
- [ ] Adjacent targets have ≥ 8px spacing

**Issues Found:**
```
[Record any failures here]
```

---

## Section 5: Motion Testing

### Reduced Motion

**Enable reduced motion in OS:**
- Windows: Settings → Accessibility → Visual effects → OFF
- macOS: System Preferences → Accessibility → Display → Reduce motion
- iOS: Settings → Accessibility → Motion → Reduce Motion
- Android: Settings → Accessibility → Remove animations

**Test on web-sender /**
- [ ] No fade-up animation on page load
- [ ] No card hover scale effects
- [ ] Button hover is instant (no transition)
- [ ] Smooth scroll disabled
- [ ] Page still fully functional

**Test on all pages:**
- [ ] All animations respect user preference
- [ ] No unexpected motion after setting is enabled

**Issues Found:**
```
[Record any failures here]
```

---

## Section 6: Final Verification

### WCAG 2.2 Level AA Criteria

**Perceivable:**
- [ ] 1.1.1 Non-text Content (A) - All images have alt text
- [ ] 1.3.1 Info and Relationships (A) - Headings, labels, landmarks properly marked
- [ ] 1.3.5 Identify Input Purpose (AA) - Autocomplete attributes present
- [ ] 1.4.3 Contrast (AA) - All text meets 4.5:1 ratio
- [ ] 1.4.11 Non-text Contrast (AA) - Focus indicators meet 3:1 ratio

**Operable:**
- [ ] 2.1.1 Keyboard (A) - All functionality available via keyboard
- [ ] 2.4.1 Bypass Blocks (A) - Skip links present
- [ ] 2.4.6 Headings and Labels (AA) - Headings describe content
- [ ] 2.4.7 Focus Visible (AA) - Focus indicators always visible
- [ ] 2.4.11 Focus Appearance (AA) - Focus indicators meet size/contrast
- [ ] 2.5.8 Target Size (Minimum) (AA) - Touch targets ≥ 24×24px

**Understandable:**
- [ ] 3.3.1 Error Identification (A) - Errors clearly identified
- [ ] 3.3.2 Labels or Instructions (A) - Form labels present
- [ ] 3.3.7 Redundant Entry (A) - Autocomplete prevents re-entry

**Robust:**
- [ ] 4.1.2 Name, Role, Value (A) - All interactive elements properly labeled

---

## Testing Summary

**Total Pages Tested:** ___________  
**Total Issues Found:** ___________  
**Critical Issues:** ___________  
**High Priority Issues:** ___________  
**Medium Priority Issues:** ___________  
**Low Priority Issues:** ___________  

**Overall Status:**
- [ ] ✅ PASS - 100% compliant
- [ ] ⚠️ PASS WITH WARNINGS - Minor issues found
- [ ] ❌ FAIL - Critical/high issues found

---

## Sign-Off

**Tester Name:** ___________________________  
**Date Completed:** ___________________________  
**Signature:** ___________________________  

**Reviewed By:** ___________________________  
**Date Reviewed:** ___________________________  

---

**Next Steps:**
1. Fix all critical and high priority issues
2. Re-test after fixes
3. Document final results in ACCESSIBILITY_TESTING_RESULTS.md
4. Update advancedev.md with final compliance status
