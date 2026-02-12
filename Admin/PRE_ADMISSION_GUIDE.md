# Pre-Admission Dashboard Setup & Usage Guide

## Overview
The new Pre-Admission Dashboard allows admins to:
- View all student pre-registration applications
- Filter by application status and search by name/email/phone
- View detailed student information including contact details and guardians
- Approve or reject applications
- Track payment status for each application
- Add admin notes to applications

## Files Created

1. **PreAdmissions.php** - Main dashboard showing all applications
2. **view_pre_admission.php** - Detailed view of individual application
3. **approve_pre_admission.php** - Approve an application
4. **reject_pre_admission.php** - Reject an application with reason

## Database Setup

### 1. Add Pre-Admission Table
Run this SQL in phpMyAdmin (also in database_migration.sql):

```sql
CREATE TABLE IF NOT EXISTS `pre_admission` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_name` VARCHAR(150) NOT NULL,
    `student_email` VARCHAR(100) NOT NULL,
    `father_name` VARCHAR(150) NOT NULL,
    `mother_name` VARCHAR(150),
    `phone` VARCHAR(20),
    `gender` VARCHAR(20),
    `date_of_birth` DATE,
    `address` TEXT,
    `admission_fee` DECIMAL(10, 2) DEFAULT 100.00,
    `application_status` VARCHAR(30) DEFAULT 'PENDING',
    `payment_status` VARCHAR(20) DEFAULT 'PENDING',
    `transaction_uuid` VARCHAR(100),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_student_email` (`student_email`),
    KEY `idx_application_status` (`application_status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_transaction_uuid` (`transaction_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Files Modified
- **preregistration.php** - Now saves all student data to pre_admission table
- **checkout.php** - Links transaction UUID to pre_admission record
- **success.php** - Updates payment_status to COMPLETE in pre_admission table
- **database_migration.sql** - Added pre_admission table creation

## How to Access

1. **Login as Admin** to your SMS admin panel
2. **Add link to PreAdmissions.php** in your admin navigation or manually visit:
   ```
   http://localhost/Student_Management_System/Admin/PreAdmissions.php
   ```

## Application Workflow

### Student Side
1. Student clicks "Pre-Admission" on homepage
2. Fills pre-registration form with all details
3. System saves to `pre_admission` table (status: PENDING)
4. Student proceeds to payment
5. After payment, status updates to PAID + COMPLETE

### Admin Side
1. **View Applications** - PreAdmissions.php dashboard shows all submissions
2. **View Details** - Click "View" button to see full application
3. **Approve Application** - Click "Approve" (moves to APPROVED status)
4. **Reject Application** - Click "Reject" and provide reason
5. **Add Notes** - Include notes when approving/rejecting

## Application Status Values

- **PENDING** - Initial status when student submits form
- **PROCESSING** - Under admin review (optional)
- **APPROVED** - Admin has approved the application
- **PAID** - Payment has been completed successfully
- **REJECTED** - Admin has rejected the application

## Payment Status Values

- **PENDING** - Waiting for payment
- **COMPLETE** - Payment received and verified

## Features

### Dashboard Statistics
Shows count of:
- Total applications
- Pending applications
- Processing applications
- Approved applications
- Paid applications
- Rejected applications

### Search & Filter
- Search by student name, email, or phone
- Filter by application status
- View results in sorted table

### Detailed View
Each application shows:
- Personal information (name, email, phone, DOB, gender, address)
- Guardian information (father's name, mother's name)
- Admission details (fee amount, application status, payment status)
- Payment transaction details (if payment made)
- Admin notes section

### Admin Actions
- Approve applications (with optional notes)
- Reject applications (with required reason)
- View full application details
- Track payment status in real-time

## Database Queries

### View all pending applications
```sql
SELECT * FROM pre_admission 
WHERE application_status = 'PENDING' 
ORDER BY created_at DESC;
```

### View successful payments
```sql
SELECT * FROM pre_admission 
WHERE payment_status = 'COMPLETE' 
ORDER BY created_at DESC;
```

### View rejected applications with reasons
```sql
SELECT student_name, student_email, notes, created_at 
FROM pre_admission 
WHERE application_status = 'REJECTED' 
ORDER BY created_at DESC;
```

### Statistics by date
```sql
SELECT 
    DATE(created_at) as date,
    application_status,
    COUNT(*) as count
FROM pre_admission
GROUP BY DATE(created_at), application_status
ORDER BY date DESC;
```

## Integration Notes

The pre-admission system is fully integrated with:
- **eSewa Payment** - Tracks payment through transaction UUID
- **Database** - Syncs with payment_logs and pre_admission tables
- **Admin Panel** - Can be added as menu item to admin dashboard

## Next Steps

1. Execute database migration SQL in phpMyAdmin
2. Test the student flow: Registration → Payment → Success
3. Verify admin dashboard displays applications
4. Add PreAdmissions.php link to admin navigation menu
5. Test approve/reject functionality

## Troubleshooting

**Table doesn't exist error**
- Run database_migration.sql script in phpMyAdmin

**Applications not showing**
- Ensure pre_admission table was created
- Check that students completed registration forms
- Verify database connection is working

**Payment status not updating**
- Check that checkout.php and success.php were updated
- Verify transaction UUID is being passed correctly
- Check payment_logs table for transaction records

## Support

For issues or questions about the pre-admission system, check:
- `/Admin/PreAdmissions.php` - Main dashboard
- `/Admin/view_pre_admission.php` - Application details
- `/Payment/database_migration.sql` - Database schema
