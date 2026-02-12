<?php
/**
 * Student Pre-Registration Form
 * Collects student information and admission fee as initial requirement
 * After submission, redirects to eSewa payment checkout
 */

include '../../Database/db_connect.php';

// Fetch admission fee (or set default)
$admission_fee = 10000.00; // Default admission fee in NPR

// Check if admin has set a specific fee
$sql = "SELECT value FROM settings WHERE key='admission_fee' LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $admission_fee = floatval($row['value']);
}

$form_submitted = false;
$form_errors = array();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $student_name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
    $student_email = isset($_POST['student_email']) ? trim($_POST['student_email']) : '';
    $father_name = isset($_POST['father_name']) ? trim($_POST['father_name']) : '';
    $mother_name = isset($_POST['mother_name']) ? trim($_POST['mother_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    // Validate required fields
    if (empty($student_name)) {
        $form_errors[] = "Student name is required.";
    }
    if (empty($student_email) || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        $form_errors[] = "Valid email is required.";
    }
    if (empty($father_name)) {
        $form_errors[] = "Father's name is required.";
    }
    if (empty($date_of_birth)) {
        $form_errors[] = "Date of birth is required.";
    }
    
    // If no errors, proceed with pre-registration and payment
    if (empty($form_errors)) {
        // Save pre-admission data to database
        $student_name_db = $conn->real_escape_string($student_name);
        $student_email_db = $conn->real_escape_string($student_email);
        $father_name_db = $conn->real_escape_string($father_name);
        $mother_name_db = $conn->real_escape_string($mother_name);
        $phone_db = $conn->real_escape_string($phone);
        $gender_db = $conn->real_escape_string($gender);
        $date_of_birth_db = $conn->real_escape_string($date_of_birth);
        $address_db = $conn->real_escape_string($address);
        
        $sql_admission = "INSERT INTO pre_admission 
                         (student_name, student_email, father_name, mother_name, phone, gender, date_of_birth, address, admission_fee) 
                         VALUES 
                         ('$student_name_db', '$student_email_db', '$father_name_db', '$mother_name_db', '$phone_db', '$gender_db', '$date_of_birth_db', '$address_db', '$admission_fee')";
        
        if (!$conn->query($sql_admission)) {
            $form_errors[] = "Error saving application: " . $conn->error;
        } else {
            $form_submitted = true;
            // Data will be passed to checkout.php via POST
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Pre-Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-header {
            background: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-bottom: 3px solid #3b82f6;
        }
        
        .form-header h1 {
            color: #1f2937;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-error ul {
            list-style: none;
            padding-left: 0;
        }
        
        .alert-error li {
            margin: 5px 0;
        }
        
        .alert-error li::before {
            content: "• ";
            margin-right: 5px;
        }
        
        .fee-section {
            background: #f0fdf4;
            border: 2px solid #dcfce7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .fee-section h3 {
            color: #059669;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .fee-row {
            display: flex;
            justify-content: space-between;
            color: #047857;
            font-weight: 600;
            font-size: 16px;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background-color: #60bb46;
            color: white;
        }
        
        .btn-submit:hover {
            background-color: #52ad3a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(96, 187, 70, 0.3);
        }
        
        .btn-reset {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .btn-reset:hover {
            background-color: #d1d5db;
        }
        
        .required {
            color: #ef4444;
        }
        
        .field-hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 5px;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .form-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-header">
        <h1>Student Pre-Registration</h1>
        <p>Complete this form to register and proceed with payment</p>
    </div>
    
    <div class="form-card">
        <?php if (!empty($form_errors)): ?>
            <div class="alert-error">
                <ul>
                    <?php foreach ($form_errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($form_submitted && empty($form_errors)): ?>
            <!-- Success - Show checkout redirect message -->
            <div style="text-align: center; padding: 30px;">
                <h2 style="color: #059669; margin-bottom: 15px;">✓ Pre-Registration Complete!</h2>
                <p style="color: #6b7280; margin-bottom: 20px;">Redirecting to payment gateway...</p>
                <p style="font-size: 12px; color: #9ca3af;">If not redirected automatically, click the button below:</p>
            </div>
            
            <!-- Hidden form that auto-submits to checkout -->
            <form id="checkoutForm" action="../../Payment/checkout.php" method="POST">
                <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($student_name); ?>">
                <input type="hidden" name="student_email" value="<?php echo htmlspecialchars($student_email); ?>">
                <input type="hidden" name="admission_fee" value="<?php echo $admission_fee; ?>">
            </form>
            
            <script type="text/javascript">
                // Auto-submit the form after a short delay
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        document.getElementById('checkoutForm').submit();
                    }, 1000);
                });
            </script>
            
            <div class="button-group">
                <button type="button" onclick="document.getElementById('checkoutForm').submit();" class="btn btn-submit">
                    Proceed to Payment
                </button>
            </div>
            
        <?php else: ?>
            <!-- Registration Form -->
            <form method="POST" action="">
                <div class="form-row full">
                    <div class="form-group">
                        <label for="student_name">Student's Full Name <span class="required">*</span></label>
                        <input type="text" id="student_name" name="student_name" 
                               value="<?php echo isset($student_name) ? htmlspecialchars($student_name) : ''; ?>"
                               placeholder="Enter full name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_email">Email Address <span class="required">*</span></label>
                        <input type="email" id="student_email" name="student_email" 
                               value="<?php echo isset($student_email) ? htmlspecialchars($student_email) : ''; ?>"
                               placeholder="student@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                               placeholder="98XXXXXXXX">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo isset($date_of_birth) ? htmlspecialchars($date_of_birth) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo isset($gender) && $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo isset($gender) && $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo isset($gender) && $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="father_name">Father's Name <span class="required">*</span></label>
                        <input type="text" id="father_name" name="father_name" 
                               value="<?php echo isset($father_name) ? htmlspecialchars($father_name) : ''; ?>"
                               placeholder="Father's full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mother_name">Mother's Name</label>
                        <input type="text" id="mother_name" name="mother_name" 
                               value="<?php echo isset($mother_name) ? htmlspecialchars($mother_name) : ''; ?>"
                               placeholder="Mother's full name">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" 
                                  placeholder="Enter residential address"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                        <div class="field-hint">City, District, Province</div>
                    </div>
                </div>
                
                <!-- Admission Fee Display -->
                <div class="fee-section">
                    <h3>Admission Fee (One-time payment)</h3>
                    <div class="fee-row">
                        <span>Total Amount due:</span>
                        <span>NPR <?php echo number_format($admission_fee, 2); ?></span>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-submit">Proceed to Payment</button>
                    <button type="reset" class="btn btn-reset">Clear Form</button>
                </div>
            </form>
        <?php endif; ?>
        
    </div>
</div>

<?php $conn->close(); ?>

</body>
</html>
