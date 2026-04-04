# Bulk Student Import Guide

## Overview
The bulk import feature allows you to add multiple students at once using a CSV file instead of adding them one by one through the form.

## How to Use

### Step 1: Access the Feature
1. Navigate to **Admin Panel** → **Add Student**
2. Click on the **"Bulk Upload"** tab

### Step 2: Prepare Your CSV File
Your CSV file should have the following format:

**Columns Required:**
- `name` - Full name of student
- `email` - Email address (must be unique)
- `password` - Student password
- `class_id` - Class ID number (must exist in system)
- `date_of_birth` - Date in YYYY-MM-DD format
- `gender` - Male, Female, or Other

**Example CSV Content:**
```
name,email,password,class_id,date_of_birth,gender
John Doe,john@example.com,Pass123!,1,2010-05-15,Male
Jane Smith,jane@example.com,Pass456!,2,2009-03-20,Female
Robert Brown,robert@example.com,Pass789!,1,2010-01-10,Male
```

### Step 3: Upload the File
1. Click the upload area or drag and drop your CSV file
2. You'll see confirmation of which file is selected
3. Click **"Import Students"** button

### Step 4: Review Results
- Success message shows how many students were imported
- If there are errors, they'll be displayed with row numbers and descriptions
- Fix any errors and retry

## CSV Format Details

### Required Format
```
name,email,password,class_id,date_of_birth,gender
```

### Field Specifications
| Field | Type | Requirements | Example |
|-------|------|--------------|---------|
| name | Text | Required, any length | John Doe |
| email | Email | Required, must be unique, valid format | john@example.com |
| password | Text | Required, min 1 character | Secure123! |
| class_id | Number | Required, must exist in system | 1 |
| date_of_birth | Date | Required, YYYY-MM-DD format, must be past date | 2010-05-15 |
| gender | Text | Required, Male/Female/Other (case-sensitive) | Male |

## Error Handling

### Common Errors & Solutions

**"Email already exists"**
- The email you're trying to import is already in the system
- Solution: Use unique email addresses

**"Class ID {X} does not exist"**
- The class doesn't exist in the system
- Solution: Create the class first or use valid class ID

**"Invalid email format"**
- Email doesn't match standard email format
- Solution: Use proper format like student@example.com

**"Invalid date of birth"**
- Date is in wrong format or is a future date
- Solution: Use YYYY-MM-DD format with past dates

**"Missing columns"**
- CSV doesn't have all 6 required columns
- Solution: Ensure your CSV has all columns in correct order

## Sample File
A sample CSV file is provided: **SAMPLE_STUDENTS.csv**
- Download it to see the correct format
- Edit it with your student data
- Re-upload the modified file

## File Specifications
- **Format:** CSV (Comma-Separated Values)
- **Max Size:** 5MB
- **Encoding:** UTF-8 recommended
- **Header Row:** Required (will be skipped automatically)

## Tips for Success

1. **Use a Spreadsheet Program**
   - Create data in Excel/Google Sheets
   - Use "Save As" → CSV format

2. **Verify Class IDs**
   - Go to Admin Panel → Manage Classes
   - Note down the Class ID numbers to use in CSV

3. **Validate Emails**
   - Ensure all emails are unique
   - Check format before uploading

4. **Date Format**
   - Always use YYYY-MM-DD
   - For example: 2010-05-15 (May 15, 2010)

5. **Test First**
   - Try importing a small batch first
   - Check results before large imports

## Why Use Bulk Import?

✅ Add multiple students quickly  
✅ Reduce manual data entry errors  
✅ Saves time compared to single entry  
✅ Useful for semester start or class transfers  
✅ Can be automated with data from other systems  

## Support

If you encounter issues:
1. Review the error messages for specific row problems
2. Check the CSV format matches the template
3. Verify all class IDs exist in the system
4. Ensure all emails are unique
5. Download the sample CSV and compare format
