<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$page_title = 'گزارش‌ها';
$active_page = 'reports';

require_once 'config.php';

$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['total'];
$result = $conn->query("SELECT COUNT(*) as total FROM exam_settings WHERE exam_start_time_shamsi IS NOT NULL");
$stats['total_exams'] = $result->fetch_assoc()['total'];
$result = $conn->query("SELECT COUNT(*) as total FROM exam_results");
$stats['total_participations'] = $result->fetch_assoc()['total'];
$result = $conn->query("SELECT AVG(score) as avg FROM exam_results");
$avg_score = $result->fetch_assoc()['avg'];
$stats['avg_score'] = $avg_score ? round($avg_score / 5, 1) : 0;
$result = $conn->query("SELECT MAX(score) as max FROM exam_results");
$max_score = $result->fetch_assoc()['max'];
$stats['max_score'] = $max_score ? round($max_score / 5, 1) : 0;
$result = $conn->query("SELECT MIN(score) as min FROM exam_results");
$min_score = $result->fetch_assoc()['min'];
$stats['min_score'] = $min_score ? round($min_score / 5, 1) : 0;

$top_students = $conn->query("SELECT u.name, u.phone, AVG(er.score) as avg_score, COUNT(er.id) as exam_count FROM users u LEFT JOIN exam_results er ON u.phone = er.user_phone WHERE u.role = 'student' AND er.id IS NOT NULL GROUP BY u.id ORDER BY avg_score DESC LIMIT 5");

$exam_scores = $conn->query("SELECT es.exam_start_time_shamsi, COUNT(er.id) as participants, AVG(er.score) as avg_score, MAX(er.score) as max_score, MIN(er.score) as min_score FROM exam_settings es INNER JOIN exam_results er ON es.id = er.exam_id WHERE es.exam_start_time_shamsi IS NOT NULL GROUP BY es.id ORDER BY es.created_at DESC LIMIT 10");

include 'header.php';
?>

<style>
    .reports-container {
        max-width: 1200px;
        margin: 0 auto
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px
    }

    .stat-card {
        background: #fff;
        border-radius: 28px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        transition: 0.2s
    }

    .stat-card:hover {
        transform: translateY(-4px)
    }

    .stat-icon {
        font-size: 40px;
        margin-bottom: 10px
    }

    .stat-value {
        font-size: 32px;
        font-weight: bold;
        color: #1e293b
    }

    .stat-label {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px
    }

    .stat-good {
        color: #10b981
    }

    .stat-medium {
        color: #f59e0b
    }

    .stat-bad {
        color: #ef4444
    }

    .reports-card {
        background: #fff;
        border-radius: 28px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08)
    }

    .reports-title {
        font-size: 20px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #667eea;
        display: inline-block
    }

    .ranking-table {
        width: 100%;
        border-collapse: collapse
    }

    .ranking-table th,
    .ranking-table td {
        padding: 12px;
        text-align: right;
        border-bottom: 1px solid #e2e8f0
    }

    .ranking-table th {
        background: #f1f5f9;
        font-weight: 600
    }

    .rank-1 {
        background: linear-gradient(135deg, #fef3c7, #fffbeb)
    }

    .rank-2 {
        background: #f1f5f9
    }

    .rank-3 {
        background: #fef3c7
    }

    .score-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 12px
    }

    .score-high {
        background: #d1fae5;
        color: #065f46
    }

    .score-medium {
        background: #fef3c7;
        color: #92400e
    }

    .score-low {
        background: #fee2e2;
        color: #991b1b
    }

    .empty-row td {
        text-align: center;
        padding: 40px;
        color: #64748b
    }

    @media (max-width:700px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .ranking-table th,
        .ranking-table td {
            padding: 8px;
            font-size: 12px
        }
    }

    @media (max-width:500px) {
        .stats-grid {
            grid-template-columns: 1fr
        }
    }
</style>

<div class="reports-container">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-value">
                <?php echo $stats['total_students']; ?>
            </div>
            <div class="stat-label">تعداد دانش‌آموزان</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-value">
                <?php echo $stats['total_exams']; ?>
            </div>
            <div class="stat-label">تعداد آزمون‌ها</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value">
                <?php echo $stats['total_participations']; ?>
            </div>
            <div class="stat-label">تعداد شرکت‌کنندگان</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div
                class="stat-value <?php echo $stats['avg_score'] >= 14 ? 'stat-good' : ($stats['avg_score'] >= 10 ? 'stat-medium' : 'stat-bad'); ?>">
                <?php echo $stats['avg_score']; ?>
            </div>
            <div class="stat-label">میانگین نمرات (از 20)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-value stat-good">
                <?php echo $stats['max_score']; ?>
            </div>
            <div class="stat-label">بهترین نمره</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-value stat-bad">
                <?php echo $stats['min_score']; ?>
            </div>
            <div class="stat-label">ضعیف‌ترین نمره</div>
        </div>
    </div>

    <div class="reports-card">
        <div class="reports-title">🏆 دانش‌آموزان برتر</div>
        <table class="ranking-table">
            <thead>
                <tr>
                    <th>رتبه</th>
                    <th>نام</th>
                    <th>شماره تلفن</th>
                    <th>میانگین نمرات</th>
                    <th>تعداد آزمون</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($top_students && $top_students->num_rows > 0):
                    $rank = 1;
                    while ($row = $top_students->fetch_assoc()):
                        $avg = round($row['avg_score'] / 5, 1);
                        $rank_class = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : '')); ?>
                        <tr class="<?php echo $rank_class; ?>">
                            <td style="font-weight:bold;">
                                <?php echo $rank; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['phone']); ?>
                            </td>
                            <td><span
                                    class="score-badge <?php echo $avg >= 14 ? 'score-high' : ($avg >= 10 ? 'score-medium' : 'score-low'); ?>">
                                    <?php echo $avg; ?>
                                </span></td>
                            <td>
                                <?php echo $row['exam_count']; ?>
                            </td>
                        </tr>
                        <?php $rank++; endwhile; else: ?>
                    <tr class="empty-row">
                        <td colspan="5">📭 هنوز داده‌ای وجود ندارد</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="reports-card">
        <div class="reports-title">📊 آمار آزمون‌های برگزار شده</div>
        <table class="ranking-table">
            <thead>
                <tr>
                    <th>تاریخ آزمون</th>
                    <th>تعداد شرکت‌کنندگان</th>
                    <th>میانگین نمره</th>
                    <th>بهترین نمره</th>
                    <th>بدترین نمره</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($exam_scores && $exam_scores->num_rows > 0):
                    while ($row = $exam_scores->fetch_assoc()):
                        $avg = $row['avg_score'] ? round($row['avg_score'] / 5, 1) : 0;
                        $max = $row['max_score'] ? round($row['max_score'] / 5, 1) : 0;
                        $min = $row['min_score'] ? round($row['min_score'] / 5, 1) : 0; ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['exam_start_time_shamsi']); ?>
                            </td>
                            <td>
                                <?php echo $row['participants']; ?>
                            </td>
                            <td><span
                                    class="score-badge <?php echo $avg >= 14 ? 'score-high' : ($avg >= 10 ? 'score-medium' : 'score-low'); ?>">
                                    <?php echo $avg; ?>
                                </span></td>
                            <td><span class="score-badge score-high">
                                    <?php echo $max; ?>
                                </span></td>
                            <td><span class="score-badge score-low">
                                    <?php echo $min; ?>
                                </span></td>
                        </tr>
                    <?php endwhile; else: ?>
                    <tr class="empty-row">
                        <td colspan="5">📭 هنوز آزمونی برگزار نشده است</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>