# Database Setup Guide - Payment Success Tracking

## What You Have

Your database is already set up to track **successful payments** with the following:

### Database Column: `status`

```sql
CREATE TABLE payment_logs (
    ...
    status VARCHAR(20) DEFAULT 'PENDING',
    ...
)
```

This column stores payment status:
- **PENDING** - Payment initiated, waiting for completion
- **COMPLETE** - ✅ Successful payment (verified by eSewa)
- **FAILED** - ❌ Payment failed or cancelled
- **REFUNDED** - Payment was refunded
- **NOT_FOUND** - Session expired

---

## How Successful Payment Status Gets Set

### When Payment is Successful:

1. **Student completes payment on eSewa** → Redirects to `success.php`
2. **System verifies signature** → Confirms authenticity
3. **Database is updated**:
   ```php
   UPDATE payment_logs 
   SET status='COMPLETE', ref_id='$transaction_code' 
   WHERE transaction_uuid='$transaction_uuid'
   ```
4. **Success page displays** → Shows confirmation

---

## Verify Database Setup

### Step 1: Open phpMyAdmin
```
http://localhost/phpmyadmin
```

### Step 2: Check if Table Exists
```
1. Click on database: SMS
2. Look for table: payment_logs
3. Should see columns including: status, amount, student_name, etc.
```

### Step 3: Run This Query

Copy and paste in SQL tab to see all successful payments:

```sql
-- View ALL Successful Payments
SELECT 
    id,
    student_name,
    student_email,
    amount,
    status,
    ref_id,
    created_at
FROM payment_logs 
WHERE status = 'COMPLETE'
ORDER BY created_at DESC;
```

### Step 4: See Payment Statistics

To see breakdown of all payments:

```sql
-- Payment Statistics by Status
SELECT 
    status,
    COUNT(*) as total_count,
    SUM(amount) as total_amount,
    ROUND(AVG(amount), 2) as average_amount
FROM payment_logs 
GROUP BY status;
```

### Step 5: See Daily Revenue

To see successful payments by date:

```sql
-- Daily Revenue Report
SELECT 
    DATE(created_at) as payment_date,
    COUNT(*) as successful_payments,
    SUM(amount) as daily_revenue
FROM payment_logs 
WHERE status = 'COMPLETE'
GROUP BY DATE(created_at)
ORDER BY payment_date DESC;
```

---

## View Successful Payments Through Admin Panel

### Open Admin Dashboard

URL: `http://localhost/Student_Management_System/Payment/view_successful_payments.php`

This page shows:
- ✅ **Total Payments** - All transactions in system
- ✅ **Successful Count** - Payments with status = COMPLETE
- ⏳ **Pending Count** - Awaiting completion
- ❌ **Failed Count** - Cancelled or failed payments
- 💰 **Total Revenue** - From all successful payments
- 📊 **Table** - All successful payment details

---

## If Table Doesn't Exist

### Run the Migration Script:

1. Go to phpMyAdmin
2. Click your database: `SMS`
3. Click `SQL` tab
4. Copy content from: `/Payment/database_migration.sql`
5. Paste and click `Execute`

This creates:
- `payment_logs` table (with status column)
- `settings` table
- `payment_statistics` view

---

## Database Column Breakdown

```sql
payment_logs table columns:

id                    → Auto-increment ID
student_name          → Who paid
student_email         → Student email
transaction_uuid      → Unique payment ID
amount                → Payment amount
status                → PENDING | COMPLETE | FAILED  ✅ THIS SHOWS SUCCESS
ref_id                → eSewa reference number
payment_method        → Always "ESEWA" for now
created_at            → When payment started
updated_at            → Last update
notes                 → Any extra info
```

---

## Sample Queries for Your Needs

### Find All Successful Payments
```sql
SELECT * FROM payment_logs WHERE status = 'COMPLETE';
```

### Find Specific Student's Successful Payment
```sql
SELECT * FROM payment_logs 
WHERE student_email = 'student@example.com' AND status = 'COMPLETE';
```

### Count Successful Payments This Month
```sql
SELECT COUNT(*) as successful_this_month
FROM payment_logs 
WHERE status = 'COMPLETE' 
AND MONTH(created_at) = MONTH(NOW())
AND YEAR(created_at) = YEAR(NOW());
```

### Total Revenue from Successful Payments
```sql
SELECT SUM(amount) as total_revenue
FROM payment_logs 
WHERE status = 'COMPLETE';
```

### Show Successful Payments with eSewa Reference
```sql
SELECT 
    student_name,
    amount,
    ref_id,
    created_at
FROM payment_logs 
WHERE status = 'COMPLETE'
ORDER BY created_at DESC;
```

---

## How to Add Link to Admin Dashboard

Add this to your admin panel (Admin/admin.php):

```html
<a href="../Payment/view_successful_payments.php" class="btn">
    View Successful Payments
</a>
```

---

## Step-by-Step Setup

### 1. Create Database Table (if not already done)
```
☐ Open phpMyAdmin
☐ Select SMS database  
☐ Go to SQL tab
☐ Copy database_migration.sql
☐ Paste and Execute
```

### 2. Test Payment
```
☐ Go to Pre-Registration form
☐ Fill form and proceed to payment
☐ Complete eSewa payment with test credentials
☐ See success page
```

### 3. Verify in Database
```
☐ Open phpMyAdmin
☐ Go to payment_logs table
☐ Should see new record with status = 'COMPLETE'
```

### 4. View Admin Dashboard
```
☐ Open: Payment/view_successful_payments.php
☐ Should see successful payment statistics
☐ Should see table with payment details
```

---

## What Happens When Payment is Successful

```
Flow:
    Student completes eSewa payment
            ↓
    eSewa redirects to success.php
            ↓
    PHP verifies signature (prevents fraud)
            ↓
    Database UPDATE:
        UPDATE payment_logs 
        SET status = 'COMPLETE'   ✅ Shows as successful
        WHERE transaction_uuid = 'txn-xxx'
            ↓
    Success page displays confirmation
            ↓
    Status column now shows: COMPLETE ✅
```

---

## Quick Access

| Need | URL |
|------|-----|
| View Successful Payments | `/Payment/view_successful_payments.php` |
| phpMyAdmin | `http://localhost/phpmyadmin` |
| Database queries | SQL tab in phpMyAdmin |
| Pre-Registration | `/Admin/PreRegistration/preregistration.php` |

---

## Troubleshooting

### Q: Can't see payment_logs table in phpMyAdmin
**A**: Run database_migration.sql script to create it

### Q: Payments showing as PENDING, not COMPLETE
**A**: Payment verification page (success.php) may not be reached. Check:
- success_url in esewa_config.php
- Database connection in success.php
- Signature verification logic

### Q: Want to manually change status to COMPLETE
**A**: Use this SQL:
```sql
UPDATE payment_logs 
SET status='COMPLETE', ref_id='REF123' 
WHERE transaction_uuid='txn-xxx';
```

### Q: How to delete test payments
**A**: 
```sql
DELETE FROM payment_logs WHERE status='PENDING';
```

---

**Remember**: The `status` column automatically shows "COMPLETE" ✅ when payment is successful and verified by the system!
