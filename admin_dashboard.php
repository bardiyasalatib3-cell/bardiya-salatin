<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$page_title = 'پنل مدیریت';
$active_page = 'admin';

require_once 'config.php';

$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$total_questions = $conn->query("SELECT COUNT(*) as count FROM soal_t")->fetch_assoc()['count'];
$total_exams = $conn->query("SELECT COUNT(*) as count FROM exam_results")->fetch_assoc()['count'];

include 'header.php';
?>

<style>
    .dashboard-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}
    .stat-card{background:#fff;border-radius:28px;padding:25px;text-align:center;box-shadow:0 8px 20px rgba(0,0,0,0.05);transition:0.2s}
    .stat-card:hover{transform:translateY(-4px)}
    .stat-icon{font-size:40px;margin-bottom:10px}
    .stat-number{font-size:32px;font-weight:bold;color:#667eea}
    .stat-label{font-size:13px;color:#64748b;margin-top:8px}
    .action-buttons{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-top:20px}
    .action-card{background:#fff;border-radius:28px;padding:25px;text-align:center;box-shadow:0 8px 20px rgba(0,0,0,0.05);text-decoration:none;transition:0.2s;display:block}
    .action-card:hover{transform:translateY(-4px)}
    .action-icon{font-size:48px;margin-bottom:10px}
    .action-title{font-size:18px;font-weight:bold;color:#1e293b;margin-bottom:8px}
    .action-desc{font-size:12px;color:#64748b}
    .warning-note{background:#fef3c7;border-right:4px solid #f59e0b;padding:15px;border-radius:15px;margin-top:20px;font-size:13px;color:#92400e}
</style>

<div class="dashboard-stats">
    <div class="stat-card"><div class="stat-icon">👨‍🏫</div><div class="stat-number"><?php echo $total_teachers; ?></div><div class="stat-label">تعداد معلمان</div></div>
    <div class="stat-card"><div class="stat-icon">👨‍🎓</div><div class="stat-number"><?php echo $total_students; ?></div><div class="stat-label">تعداد دانش‌آموزان</div></div>
    <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-number"><?php echo $total_questions; ?></div><div class="stat-label">تعداد سوالات</div></div>
    <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-number"><?php echo $total_exams; ?></div><div class="stat-label">تعداد شرکت در آزمون</div></div>
</div>

<div class="action-buttons">
    <a href="settings.php" class="action-card"><div class="action-icon">⚙️</div><div class="action-title">تنظیمات سایت</div><div class="action-desc">تغییر نام سایت، لوگو، رنگ‌ها</div></a>
    <a href="export_pdf.php" class="action-card"><div class="action-icon">📄</div><div class="action-title">خروجی PDF</div><div class="action-desc">گزارش نتایج آزمون</div></a>
</div>

<div class="warning-note">⚠️ توجه: مدیریت دانش‌آموزان و پنل معلم فقط در اختیار معلم است.</div>

<?php include 'footer.php'; ?>