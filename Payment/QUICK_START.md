# eSewa Payment Integration - Quick Start Guide

## ⚡ Quick Setup (5 Minutes)

### 1. Import Database Schema
```
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select database: SMS
3. Go to SQL tab
4. Open file: C:\xampp\htdocs\Student_Management_System\Payment\database_migration.sql
5. Copy all content and paste in SQL editor
6. Click Execute
✅ Tables created successfully
```

### 2. Test the Integration
```
URL: http://localhost/Student_Management_System/Admin/PreRegistration/preregistration.php

1. Fill the registration form with any test data
2. Click "Proceed to Payment"
3. You should see payment confirmation with amount
4. Click "Pay with eSewa"
5. You'll be redirected to eSewa login page
```

### 3. Complete eSewa Test Payment
```
Login Credentials (TEST):
- eSewa ID: 9806800001
- Password: Nepal@123
- OTP: 123456

Process:
1. Enter credentials and click Login
2. Click "Confirm" to approve payment
3. Should redirect to Success page (green checkmark)
4. See transaction details
```

### 4. Verify in Database
```
1. Open phpMyAdmin
2. Go to SMS database → payment_logs table
3. Should see new record with:
   - Status: COMPLETE
   - Amount: 100.00
   - Reference ID: (from eSewa)
```

### ✅ You're Done! Integration is Working!

---

## 📁 Key Files Location

```
Payment Folder: c:\xampp\htdocs\Student_Management_System\Payment\

Key Files:
- esewa_config.php              ← Configuration (credentials, URLs)
- checkout.php                  ← Payment form page
- success.php                   ← Success handler
- failure.php                   ← Failure handler
- check_payment_status.php      ← Admin verification tool
- database_migration.sql        ← Database setup
- README.md                     ← Full documentation
- IMPLEMENTATION_SUMMARY.md     ← Detailed explanation
- QUICK_START.md               ← This file
```

---

## 🔗 Important URLs

### For Students:
- Pre-Registration: http://localhost/Student_Management_System/Admin/PreRegistration/preregistration.php

### For Admins:
- Payment Status Checker: http://localhost/Student_Management_System/Payment/check_payment_status.php
- phpMyAdmin: http://localhost/phpmyadmin

### eSewa Resources:
- Developer Docs: https://developer.esewa.com.np/pages/Epay
- Merchant Portal: https://merchant.esewa.com.np

---

## ⚙️ Configuration

**Current Setup (TEST MODE)**:
```
✓ Merchant Code: EPAYTEST
✓ Secret Key: 8gBm/:&EnhH.1/q
✓ Payment URL: https://rc-epay.esewa.com.np/api/epay/main/v2/form
✓ Database: SMS
✓ Tables: payment_logs, settings
```

**To Change Payment Amount**:
```sql
-- In phpMyAdmin SQL editor, run:
UPDATE settings SET value='1500.00' WHERE key='admission_fee';
-- (Changes admission fee to NPR 1500)
```

---

## 🧪 Test Scenarios

### Test 1: Successful Payment
```
1. Fill registration form
2. Proceed to payment
3. Login with: 9806800001 / Nepal@123
4. OTP: 123456
5. Click Confirm
6. ✅ Should see SUCCESS page
```

### Test 2: Failed/Cancelled Payment
```
1. Fill registration form
2. Proceed to payment
3. Login with: 9806800001 / Nepal@123
4. OTP: 123456
5. Click CANCEL
6. ✅ Should see FAILURE page with helpful messages
```

### Test 3: Check Payment Status (Admin)
```
1. Go to: Payment/check_payment_status.php
2. Enter Transaction UUID from any payment attempt
3. Enter Amount
4. Click "Check Status"
5. ✅ Shows current status from database or eSewa API
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Payment form doesn't appear | Check if checkout.php is in Payment folder |
| "Database connection error" | Verify db_connect.php path in esewa_config.php |
| "Signature verification failed" | Ensure secret key matches in esewa_config.php |
| Payment stuck on "PENDING" | Use check_payment_status.php to verify with eSewa |
| Pre-registration not loading | Check if PreRegistration folder exists in Admin |

**For detailed troubleshooting**: See Payment/README.md

---

## 📊 Database Queries

### View All Payments
```sql
SELECT * FROM payment_logs ORDER BY created_at DESC;
```

### View Successful Payments Only
```sql
SELECT student_name, amount, status, created_at 
FROM payment_logs 
WHERE status = 'COMPLETE' 
ORDER BY created_at DESC;
```

### Check Total Revenue
```sql
SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_revenue
FROM payment_logs 
WHERE status = 'COMPLETE';
```

---

## 🚀 Moving to Production

When ready to go live:

1. **Get Production Credentials** from eSewa
   - Contact: https://developer.esewa.com.np

2. **Update Configuration**:
   ```php
   // In esewa_config.php, change:
   $merchant_code = "YOUR_PROD_CODE";
   $secret_key = "YOUR_PROD_SECRET";
   $payment_url = "https://epay.esewa.com.np/api/epay/main/v2/form";
   $site_url = "https://yourdomain.com";
   ```

3. **Test with Real Environment**

4. **Enable HTTPS** (required!)

5. **Deploy to Server**

---

## 📞 Support

**Need Help?**
- Read: Payment/README.md (comprehensive guide)
- See: Payment/IMPLEMENTATION_SUMMARY.md (detailed explanation)
- Check: eSewa Docs - https://developer.esewa.com.np/pages/Epay

**Common Questions**:
- Q: Can I change the admission fee?
  - A: Yes, update in settings table: `UPDATE settings SET value='NEW_AMOUNT' WHERE key='admission_fee'`

- Q: How do I see who paid what?
  - A: Query payment_logs table or use check_payment_status.php

- Q: What if a payment fails?
  - A: No money is deducted. Student can try again. Use check_payment_status.php to verify.

- Q: Can I refund a payment?
  - A: Yes, contact eSewa with the reference ID. They handle refunds. Update status in database.

---

## ✨ What's Included

```
✅ Secure payment form with HMAC-SHA256 signature
✅ Automated database logging of all transactions
✅ Payment verification and fraud detection
✅ Success and failure pages with clear messaging
✅ Admin tool to verify pending payments
✅ Complete documentation and guides
✅ Database schema with proper indexing
✅ Error handling and user-friendly messages
✅ Mobile-responsive design
✅ Production-ready code
```

---

## 🎯 Next Steps

1. ✅ **Import Database** - Run database_migration.sql
2. ✅ **Test Payment** - Use test credentials
3. ✅ **Check Database** - Verify records created
4. ✅ **Add Link** - Link pre-registration in your menu
5. ✅ **Review Docs** - Read Payment/README.md
6. 🚀 **Go Live** - Convert to production when ready

---

**Ready?** Start with: http://localhost/Student_Management_System/Admin/PreRegistration/preregistration.php

**Questions?** Check: Payment/README.md or Payment/IMPLEMENTATION_SUMMARY.md

---

**Happy Payment Processing!** 💳✨
