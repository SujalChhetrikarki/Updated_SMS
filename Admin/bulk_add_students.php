<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../Database/db_connect.php';

// Accept only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add_student.php");
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "No CSV file uploaded or upload error.";
    header("Location: add_student.php");
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$file_type = getimagesize($file) ? mime_content_type($file) : 'text/csv';

// Validate file type and size
$file_size = filesize($file);
$max_file_size = 5 * 1024 * 1024; // 5MB

if ($file_size > $max_file_size) {
    $_SESSION['error'] = "File size exceeds 5MB limit.";
    header("Location: add_student.php");
    exit;
}

// Read and parse CSV
$students_data = [];
$errors = [];
$row_num = 0;

if (($handle = fopen($file, "r")) !== false) {
    // Skip header row
    $header = fgetcsv($handle, 1000, ",");
    
    while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        $row_num++;
        
        // Expected columns: name, email, password, class_id, date_of_birth, gender
        if (count($row) < 6) {
            $errors[] = "Row {$row_num}: Missing columns. Expected 6 columns (name, email, password, class_id, date_of_birth, gender).";
            continue;
        }
        
        $name = trim($row[0]);
        $email = trim($row[1]);
        $password_raw = trim($row[2]);
        $class_id = intval($row[3]);
        $date_of_birth = trim($row[4]);
        $gender = trim($row[5]);
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($password_raw) || !$class_id || empty($date_of_birth) || empty($gender)) {
            $errors[] = "Row {$row_num}: Missing required fields.";
            continue;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row {$row_num}: Invalid email format ({$email}).";
            continue;
        }
        
        // Validate gender
        $allowed_genders = ['Male', 'Female', 'Other'];
        if (!in_array($gender, $allowed_genders)) {
            $errors[] = "Row {$row_num}: Invalid gender ({$gender}). Must be Male, Female, or Other.";
            continue;
        }
        
        // Validate date of birth
        $dob_timestamp = strtotime($date_of_birth);
        if ($dob_timestamp === false || $dob_timestamp > time()) {
            $errors[] = "Row {$row_num}: Invalid date of birth ({$date_of_birth}). Must be a valid past date.";
            continue;
        }
        
        // Validate class_id exists
        $check_class = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ?");
        $check_class->bind_param("i", $class_id);
        $check_class->execute();
        if ($check_class->get_result()->num_rows === 0) {
            $errors[] = "Row {$row_num}: Class ID {$class_id} does not exist.";
            $check_class->close();
            continue;
        }
        $check_class->close();
        
        // Check for duplicate email
        $check_email = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $errors[] = "Row {$row_num}: Email ({$email}) already exists.";
            $check_email->close();
            continue;
        }
        $check_email->close();
        
        // Add to valid students array
        $students_data[] = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password_raw, PASSWORD_DEFAULT),
            'class_id' => $class_id,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender
        ];
    }
    fclose($handle);
} else {
    $_SESSION['error'] = "Could not open CSV file.";
    header("Location: add_student.php");
    exit;
}

// Insert valid students
$success_count = 0;
$sql = "INSERT INTO students (name, email, password, class_id, date_of_birth, gender)
        VALUES (?, ?, ?, ?, ?, ?)";

foreach ($students_data as $student) {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $errors[] = "Database prepare error for {$student['email']}: " . $conn->error;
        continue;
    }
    
    $stmt->bind_param(
        "sssiss",
        $student['name'],
        $student['email'],
        $student['password'],
        $student['class_id'],
        $student['date_of_birth'],
        $student['gender']
    );
    
    if ($stmt->execute()) {
        $success_count++;
    } else {
        $errors[] = "Failed to insert {$student['email']}: " . $stmt->error;
    }
    $stmt->close();
}

// Prepare response message
$message = "Imported {$success_count} students successfully.";
if (!empty($errors)) {
    $message .= " (" . count($errors) . " errors)";
    $_SESSION['bulk_errors'] = $errors;
}

$_SESSION['success'] = $message;
header("Location: add_student.php");
exit;
