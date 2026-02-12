<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../Database/db_connect.php';

// Total students and teachers
$total_students = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$total_teachers = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'] ?? 0;

// Pass/Fail trend by exam
$sql_exams = "SELECT exam_id, exam_date FROM exams ORDER BY exam_date ASC";
$result_exams = $conn->query($sql_exams);

$exam_dates = [];
$pass_counts = [];
$fail_counts = [];

while ($exam = $result_exams->fetch_assoc()) {
    $exam_dates[] = $exam['exam_date'];

    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN average_marks >= 40 THEN 1 ELSE 0 END) AS pass_count,
            SUM(CASE WHEN average_marks < 40 THEN 1 ELSE 0 END) AS fail_count
        FROM results 
        WHERE exam_id=?
    ");
    $stmt->bind_param("i", $exam['exam_id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    $pass_counts[] = $res['pass_count'] ?? 0;
    $fail_counts[] = $res['fail_count'] ?? 0;
}

// Upcoming birthdays (next 7 days)
$sql_birthdays = "
    SELECT s.name, s.date_of_birth, c.class_name
    FROM students s
    JOIN classes c ON s.class_id = c.class_id
    WHERE DATE_FORMAT(s.date_of_birth, '%m-%d') 
          BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
          AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
    ORDER BY DATE_FORMAT(s.date_of_birth, '%m-%d') ASC
";
$birthdays = $conn->query($sql_birthdays);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ===== Reset & Body ===== */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: #f0f2f5;
    display: flex;
    min-height: 100vh;
    color: #333;
}

/* ===== Sidebar ===== */
.sidebar {
    width: 240px;
    background: #1f2937;
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 30px;
    display: flex;
    flex-direction: column;
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 22px;
    color: #3b82f6;
}
.sidebar a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    margin: 4px 12px;
    background: #374151;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
}
.sidebar a:hover { background: #3b82f6; color: #fff; }
.sidebar a.logout { background: #ef4444; }
.sidebar a.logout:hover { background: #f87171; }

/* ===== Main Content ===== */
.main {
    margin-left: 240px;
    padding: 20px 30px;
    flex: 1;
}

/* ===== Header ===== */
.header {
    background: #fff;
    padding: 20px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}
.header h1 {
    margin: 0;
    font-size: 24px;
    color: #111;
}

/* ===== Dashboard Cards ===== */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.card {
    background: #fff;
    padding: 22px;
    border-radius: 16px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
}
.card h3 {
    margin-bottom: 12px;
    font-size: 18px;
    color: #111;
}
.card p {
    font-size: 20px;
    font-weight: 600;
    color: #3b82f6;
}
.card a.button-link {
    display: inline-block;
    margin-top: 12px;
    padding: 8px 14px;
    border-radius: 8px;
    background: #3b82f6;
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}
.card a.button-link:hover { background: #2563eb; }

/* ===== Birthday List ===== */
.birthday-card ul { list-style: none; padding: 0; margin-top: 12px; }
.birthday-list { display: flex; flex-direction: column; gap: 10px; }
.birthday-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
    padding: 10px 14px;
    border-radius: 10px;
    transition: 0.3s;
}
.birthday-item:hover { background: #e0f2fe; }
.birthday-info { font-weight: 600; }
.bday-class { font-size: 13px; color: #6b7280; }
.birthday-date { text-align: right; font-size: 13px; color: #374151; }
.today-badge { background: #ef4444; color: #fff; font-size: 12px; padding: 3px 7px; border-radius: 6px; }
.upcoming-badge { background: #3b82f6; color: #fff; font-size: 12px; padding: 3px 7px; border-radius: 6px; }

/* ===== Chart Container ===== */
.chart-container {
    background: #fff;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}
.chart-container h3 {
    margin-bottom: 15px;
    font-size: 18px;
    color: #111;
}
</style>
</head>
<body>

<div class="sidebar">
  <h2>Admin Panel</h2>
  <a href="index.php">🏠 Home</a>
  <a href="PreAdmissions.php">📝 Pre-Admissions</a>
  <a href="../Admin/Manage_student/Managestudent.php">📚 Manage Students</a>
  <a href="./Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
  <a href="./classes/classes.php">🏫 Manage Classes</a>
  <a href="subjects.php">📖 Manage Subjects</a>
  <a href="Managebook.php">📚 Manage Books</a>
  <a href="add_student.php">➕ Add Student</a>
  <a href="add_teacher.php">➕ Add Teacher</a>
  <a href="./Add_exam/add_exam.php">➕ Add Exam</a>
  <a href="admin_approve_results.php">✅ Approve Results</a>
  <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
  <div class="header">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_name']); ?> 👋</h1>
  </div>

  <div class="cards">
    <div class="card">
      <h3>Total Students</h3>
      <p><?= $total_students ?></p>
    </div>
    <div class="card">
      <h3>Total Teachers</h3>
      <p><?= $total_teachers ?></p>
    </div>
    <div class="card">
      <h3>📢 Manage Notices</h3>
      <a href="manage_notices.php" class="button-link">➕ Add / Manage Notices</a>
    </div>
    <div class="card birthday-card">
      <h3>🎂 Birthdays This Week</h3>
      <?php if ($birthdays->num_rows > 0): ?>
          <ul class="birthday-list">
              <?php while ($b = $birthdays->fetch_assoc()): 
                  $dob = strtotime($b['date_of_birth']);
                  $dobThisYear = date("Y") . "-" . date("m-d", $dob);
                  $nextBirthday = (strtotime($dobThisYear) < strtotime(date("Y-m-d"))) 
                                  ? strtotime("+1 year", strtotime($dobThisYear)) 
                                  : strtotime($dobThisYear);
                  $daysLeft = (int)(($nextBirthday - time()) / (60*60*24));
                  $isToday = ($daysLeft === 0);
              ?>
                  <li class="birthday-item">
                      <div class="birthday-info"><?= htmlspecialchars($b['name']) ?> <span class="bday-class">(<?= htmlspecialchars($b['class_name']) ?>)</span></div>
                      <div class="birthday-date"><?= date("M d", $dob) ?> <span class="<?= $isToday ? 'today-badge' : 'upcoming-badge' ?>"><?= $isToday ? '🎉 Today!' : "in $daysLeft days" ?></span></div>
                  </li>
              <?php endwhile; ?>
          </ul>
      <?php else: ?>
          <p>No birthdays this week 🎉</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="chart-container">
    <h3>📊 Students Pass vs Fail Trend</h3>
    <canvas id="passFailLineChart" height="150"></canvas>
  </div>
</div>

<script>
const ctx = document.getElementById('passFailLineChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($exam_dates) ?>,
        datasets: [
            { label: 'Pass', data: <?= json_encode($pass_counts) ?>, borderColor: '#10b981', backgroundColor: '#10b981', fill:false, tension:0.3 },
            { label: 'Fail', data: <?= json_encode($fail_counts) ?>, borderColor: '#ef4444', backgroundColor: '#ef4444', fill:false, tension:0.3 }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Pass/Fail Trend by Exam' }
        },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
