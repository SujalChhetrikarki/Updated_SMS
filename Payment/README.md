# eSewa Payment Integration Guide

## Overview
This document provides step-by-step instructions for the eSewa ePay V2 API integration into the Student Management System.

## Files Created
The following files have been created in the `Payment/` folder:

```
/Payment
├── esewa_config.php          # Configuration file with credentials
├── checkout.php              # Payment form and signature generation
├── success.php               # Success handler (payment verification)
├── failure.php               # Failure/cancellation handler
├── database_migration.sql    # SQL script to create payment tables
└── README.md                 # This file
```

## Database Setup

### Step 1: Import the SQL Migration
1. Open phpMyAdmin in your XAMPP control panel
2. Select your database (e.g., `SMS`)
3. Go to the SQL tab
4. Open the file: `/Payment/database_migration.sql`
5. Copy all SQL code and paste it into the SQL editor
6. Click "Execute"

This creates:
- `payment_logs` table - Stores all payment transactions
- `settings` table - Stores configuration values like admission fee
- `payment_statistics` view - For analyzing payment data

## Configuration

### Step 2: Update esewa_config.php
Update the site URL in `/Payment/esewa_config.php`:

```php
// Production - Change 'localhost' to your actual domain
$site_url = "http://localhost/Student_Management_System"; 
// Production example: "https://your-domain.com" or "https://your-domain.com/Student_Management_System"
```

### Step 3: Test Credentials (Pre-populated)
The configuration already includes test credentials:
- **Merchant Code**: EPAYTEST
- **Secret Key**: 8gBm/:&EnhH.1/q
- **Test URL**: https://rc-epay.esewa.com.np/api/epay/main/v2/form

### For Production (After Testing):
When ready for production, contact eSewa to get:
- Production merchant code
- Production secret key
- Production URL: https://epay.esewa.com.np/api/epay/main/v2/form

Then update `esewa_config.php` with production credentials.

## Workflow

### Student Payment Journey:

1. **Pre-Registration Form** (`/Admin/PreRegistration/preregistration.php`)
   - Student fills out details (name, email, DOB, parents' info, address)
   - Displays admission fee amount
   - Clicks "Proceed to Payment"

2. **Checkout Page** (`/Payment/checkout.php`)
   - Receives student data from pre-registration form
   - Generates HMAC-SHA256 signature for security
   - Saves payment log with "PENDING" status
   - Displays payment confirmation with eSewa button

3. **eSewa Payment**
   - Student is redirected to eSewa login page
   - Enters eSewa credentials:
     - **eSewa ID**: 9806800001 to 9806800005 (test)
     - **Password**: Nepal@123
     - **OTP/Token**: 123456
   - Confirms the transaction amount
   - Payment processed by eSewa

4. **Success Handler** (`/Payment/success.php`)
   - eSewa redirects with Base64 encoded response
   - Response is decoded and signature is verified
   - If signature matches → Payment confirmed (COMPLETE)
   - Database updated with reference ID from eSewa
   - Student sees confirmation page

5. **Failure Handler** (`/Payment/failure.php`)
   - Handles cancelled or failed payments
   - Shows error message and suggestions
   - Student can attempt payment again

## Technical Details

### Signature Generation (Security)
The signature ensures data integrity and prevents fraud:

```
Message: total_amount=100,transaction_uuid=txn-xyz,product_code=EPAYTEST
Secret Key: 8gBm/:&EnhH.1/q
Algorithm: HMAC-SHA256 (Base64 encoded)
```

PHP Code (already implemented in checkout.php):
```php
$message = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=$product_code";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));
```

### Payment Status Values
- **PENDING**: Transaction initiated but not yet completed
- **COMPLETE**: Successful payment
- **FULL_REFUND**: Complete refund issued
- **PARTIAL_REFUND**: Partial refund issued
- **AMBIGUOUS**: Payment in uncertain state
- **NOT_FOUND**: Session expired
- **CANCELED**: User or eSewa cancelled

## Testing Steps

### Step 1: Access Pre-Registration Form
Navigate to: `http://localhost/Student_Management_System/Admin/PreRegistration/preregistration.php`

### Step 2: Fill Form & Click Payment
- Enter test student details
- Click "Proceed to Payment"

### Step 3: Complete eSewa Payment
- Use test credentials (provided above)
- Select OTP method: SMS or Email
- Enter OTP: 123456
- Confirm payment

### Step 4: Verify Success
- Should see success page with payment details
- Check database (`payment_logs` table) - status should be "COMPLETE"

### Step 5: Test Failure Scenario
- On eSewa payment page, click Cancel
- Should be redirected to failure.php with helpful messages

## Database Queries

### View All Payments
```sql
SELECT * FROM payment_logs ORDER BY created_at DESC;
```

### View Completed Payments
```sql
SELECT * FROM payment_logs WHERE status = 'COMPLETE';
```

### View Pending Payments
```sql
SELECT * FROM payment_logs WHERE status = 'PENDING';
```

### Generate Payment Report
```sql
SELECT 
    DATE(created_at) as payment_date,
    status,
    COUNT(*) as total_transactions,
    SUM(amount) as total_amount
FROM payment_logs
GROUP BY DATE(created_at), status;
```

## Troubleshooting

### Issue: "Signature verification failed"
**Solution**: Ensure `esewa_config.php` has correct secret key and URLs match exactly.

### Issue: Payment redirects to failure after eSewa login
**Solution**: 
- Check if success_url and failure_url are accessible from internet
- Verify database connection in success.php
- Check browser console for JavaScript errors

### Issue: "Payment initiated but no response received"
**Solution**: 
- eSewa provides a Status Check API (implemented in esewa_config.php)
- Use it to verify payment status after 5 minutes
- Update database accordingly

### Issue: Database table not created
**Solution**:
- Verify you're using the correct database (`SMS`)
- Check if user has CREATE TABLE permissions
- Re-run the SQL migration script

## Security Considerations

1. **HTTPS in Production**: Always use HTTPS URLs, not HTTP
2. **Secret Key**: Keep secret key in config file, never expose in code
3. **Signature Verification**: Always verify eSewa signatures to prevent fraud
4. **Data Validation**: Validate all incoming data from eSewa
5. **Database**: Use parameterized queries (already implemented)

## Converting to Production

When moving to production:

1. Update credentials in `esewa_config.php`:
   ```php
   $merchant_code = "YOUR_PRODUCTION_CODE";
   $secret_key = "YOUR_PRODUCTION_SECRET";
   $payment_url = "https://epay.esewa.com.np/api/epay/main/v2/form";
   ```

2. Update URLs:
   ```php
   $site_url = "https://your-domain.com"; // Your actual domain
   ```

3. Test thoroughly with production credentials

4. Contact eSewa for final approval

## API Reference

### eSewa Status Check API
If no response received within 5 minutes:

```php
$status_url = "https://rc.esewa.com.np/api/epay/transaction/status/";
// Parameters: product_code, transaction_uuid, total_amount
```

Example:
```
GET /api/epay/transaction/status/?product_code=EPAYTEST&total_amount=100&transaction_uuid=123
```

### Using eSewa Merchant Wallet
Access your merchant account: https://merchant.esewa.com.np

## Support

- **eSewa Developer Portal**: https://developer.esewa.com.np/
- **eSewa Support**: Check their website for contact information
- **System Support**: Contact your system administrator

## Additional Resources

- eSewa Documentation: https://developer.esewa.com.np/pages/Epay
- HMAC-SHA256 Info: https://tools.ietf.org/html/rfc2104
- PHP hash_hmac(): https://www.php.net/manual/en/function.hash-hmac.php

---

**Last Updated**: February 2026
**Integration Version**: eSewa ePay V2
**Status**: Ready for Testing
