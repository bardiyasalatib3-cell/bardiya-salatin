<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$page_title = 'خروجی PDF نتایج آزمون';
$active_page = 'export';

require_once 'config.php';

$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch_assoc();
$site_name = $settings['site_name'] ?? 'سامانه آزمون آنلاین';
$primary_color = $settings['primary_color'] ?? '#667eea';

$results = $conn->query("
    SELECT er.*, u.name as student_name, u.phone, es.exam_start_time_shamsi, es.exam_book
    FROM exam_results er
    JOIN users u ON er.user_phone = u.phone
    LEFT JOIN exam_settings es ON er.exam_id = es.id
    WHERE u.role = 'student'
    ORDER BY er.exam_date DESC
");

$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$total_exams = $conn->query("SELECT COUNT(*) as count FROM exam_results")->fetch_assoc()['count'];
$avg_score = $conn->query("SELECT AVG(score) as avg FROM exam_results")->fetch_assoc()['avg'];
$avg_score = $avg_score ? round($avg_score / 5, 1) : 0;

function cleanValue($value) { return str_replace('72', '', $value); }

include 'header.php';
?>

<style>
    @media print{.no-print,.header .nav-links,.header .user-info{display:none}.report-container{margin-top:0;padding-top:0}@page{size:A4;margin:1.5cm}}
    .no-print{text-align:center;margin-bottom:20px}
    .print-btn{background:<?php echo $primary_color; ?>;color:#fff;border:none;padding:10px 20px;border-radius:30px;cursor:pointer;font-size:14px}
    .print-btn:hover{opacity:0.8}
    .report-container{max-width:1200px;margin:0 auto;background:#fff;border-radius:28px;padding:25px;box-shadow:0 10px 25px rgba(0,0,0,0.08)}
    .report-header{text-align:center;margin-bottom:30px;padding-bottom:15px;border-bottom:2px solid <?php echo $primary_color; ?>}
    .report-logo{font-size:24px;font-weight:bold;color:<?php echo $primary_color; ?>;margin-bottom:10px}
    .report-title{font-size:20px;font-weight:bold;margin-bottom:5px}
    .report-date{font-size:12px;color:#666}
    .stats{display:flex;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:30px;background:#f8fafc;padding:15px;border-radius:15px}
    .stat-box{flex:1;text-align:center;min-width:120px}
    .stat-number{font-size:24px;font-weight:bold;color:<?php echo $primary_color; ?>}
    .stat-label{font-size:12px;color:#64748b}
    table{width:100%;border-collapse:collapse;margin-top:20px;font-size:13px}
    th,td{border:1px solid #ddd;padding:8px;text-align:center}
    th{background:<?php echo $primary_color; ?>;color:#fff;font-weight:bold}
    tr:nth-child(even){background:#f9f9f9}
    .score-high{background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:20px;display:inline-block}
    .score-medium{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:20px;display:inline-block}
    .score-low{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:20px;display:inline-block}
    .report-footer{text-align:center;margin-top:30px;padding-top:15px;border-top:1px solid #ddd;font-size:11px;color:#999}
    .average-row{background:#e0e7ff;font-weight:bold}
    @media (max-width:700px){table{font-size:11px}th,td{padding:5px}.stats{flex-direction:column}}
</style>

<div class="no-print"><button class="print-btn" onclick="window.print()">🖨️ چاپ / ذخیره PDF</button></div>

<div class="report-container">
    <div class="report-header"><div class="report-logo">📚 <?php echo htmlspecialchars(cleanValue($site_name)); ?></div><div class="report-title">📊 گزارش کامل نتایج آزمون‌ها</div><div class="report-date">تاریخ چاپ: <?php echo date('Y/m/d H:i'); ?></div></div>
    <div class="stats">
        <div class="stat-box"><div class="stat-number"><?php echo cleanValue($total_students); ?></div><div class="stat-label">👨‍🎓 تعداد دانش‌آموزان</div></div>
        <div class="stat-box"><div class="stat-number"><?php echo cleanValue($total_exams); ?></div><div class="stat-label">📝 تعداد شرکت در آزمون</div></div>
        <div class="stat-box"><div class="stat-number"><?php echo cleanValue($avg_score); ?></div><div class="stat-label">⭐ میانگین نمرات (از 20)</div></div>
    </div>
    <table>
        <thead><tr><th>ردیف</th><th>نام دانش‌آموز</th><th>شماره تلفن</th><th>عنوان آزمون</th><th>تاریخ</th><th>نمره</th><th>صحیح</th><th>غلط</th><th>بی‌پاسخ</th></tr></thead>
        <tbody>
            <?php $row_num=1; $total_score_sum=0; $score_count=0; while ($row = $results->fetch_assoc()): $score = round($row['score'] / 5, 1); $score_class = $score>=14?'score-high':($score>=10?'score-medium':'score-low'); $total_score_sum += $score; $score_count++; $student_name = cleanValue($row['student_name']); $phone = cleanValue($row['phone']); $exam_book = cleanValue($row['exam_book'] ?? 'عمومی'); $exam_date = cleanValue($row['exam_start_time_shamsi'] ?: date('Y/m/d H:i', strtotime($row['exam_date']))); ?>
                <tr><td><?php echo $row_num++; ?></td><td><?php echo htmlspecialchars($student_name); ?></td><td><?php echo htmlspecialchars($phone); ?></td><td><?php echo htmlspecialchars($exam_book); ?></td><td><?php echo htmlspecialchars($exam_date); ?></td><td><span class="<?php echo $score_class; ?>"><?php echo $score; ?></span></td><td><?php echo $row['correct']; ?></td><td><?php echo $row['wrong']; ?></td><td><?php echo $row['unanswered']; ?></td></tr>
            <?php endwhile; if ($row_num == 1): ?><tr><td colspan="9" style="text-align:center;">📭 هنوز داده‌ای برای نمایش وجود ندارد</td></tr><?php endif; ?>
        </tbody>
        <?php if ($score_count > 0): ?><tfoot><tr class="average-row"><td colspan="5" style="text-align:left;"><strong>میانگین کل نمرات</strong></td><td><strong><?php echo round($total_score_sum / $score_count, 1); ?></strong></td><td colspan="3"></td></tr></tfoot><?php endif; ?>
    </table>
    <div class="report-footer"><p>📚 <?php echo htmlspecialchars(cleanValue($site_name)); ?> | تمامی حقوق محفوظ است</p><p>تاریخ چاپ: <?php echo date('Y/m/d H:i'); ?></p></div>
</div>

<?php include 'footer.php'; ?>  