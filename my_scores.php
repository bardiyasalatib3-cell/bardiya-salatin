<?php
session_start();
date_default_timezone_set('Asia/Tehran');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$page_title = 'نمرات من';
$active_page = 'my_scores';

require_once 'config.php';

$user_phone = $_SESSION['user_phone'] ?? '';

$scores_result = $conn->query("SELECT er.*, es.exam_start_time_shamsi FROM exam_results er LEFT JOIN exam_settings es ON er.exam_id = es.id WHERE er.user_phone = '$user_phone' ORDER BY er.exam_date DESC");

$avg_result = $conn->query("SELECT AVG(score) as average, COUNT(*) as exam_count FROM exam_results WHERE user_phone = '$user_phone'");
$avg_data = $avg_result->fetch_assoc();
$average_percent = $avg_data['average'] ?? 0;
$average_score = round($average_percent / 5, 1);
$exam_count = $avg_data['exam_count'] ?? 0;

include 'header.php';
?>

<style>
    .scores-container {
        max-width: 950px;
        margin: 0 auto
    }

    .stats-card {
        background: #fff;
        border-radius: 28px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        text-align: center
    }

    .stats-title {
        font-size: 18px;
        color: #64748b;
        margin-bottom: 10px
    }

    .stats-value {
        font-size: 48px;
        font-weight: bold;
        color: #1e293b
    }

    .stats-value small {
        font-size: 16px;
        font-weight: normal;
        color: #64748b
    }

    .average-good {
        color: #10b981
    }

    .average-medium {
        color: #f59e0b
    }

    .average-bad {
        color: #ef4444
    }

    .scores-table {
        background: #fff;
        border-radius: 28px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        overflow-x: auto
    }

    .scores-table h3 {
        font-size: 20px;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #667eea;
        display: inline-block
    }

    table {
        width: 100%;
        border-collapse: collapse
    }

    th,
    td {
        padding: 14px 12px;
        text-align: right;
        border-bottom: 1px solid #e2e8f0
    }

    th {
        background: #f1f5f9;
        font-weight: 600;
        font-size: 14px
    }

    .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 30px;
        font-weight: bold
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

    .detail-link {
        color: #667eea;
        text-decoration: none;
        font-size: 13px
    }

    .detail-link:hover {
        text-decoration: underline
    }

    @media(max-width:600px) {

        th,
        td {
            padding: 8px 6px;
            font-size: 12px
        }

        .stats-value {
            font-size: 36px
        }
    }
</style>

<div class="scores-container">
    <div class="stats-card">
        <div class="stats-title">📊 آمار کلی</div>
        <div class="stats-value"><?php echo $exam_count; ?> <small>آزمون</small></div>
        <div class="stats-title" style="margin-top:15px;">⭐ میانگین نمرات</div>
        <div
            class="stats-value <?php if ($average_score >= 14)
                echo 'average-good';
            elseif ($average_score >= 10)
                echo 'average-medium';
            else
                echo 'average-bad'; ?>">
            <?php echo $average_score; ?> <small>از 20</small></div>
    </div>
    <div class="scores-table">
        <h3>📋 لیست آزمون‌های من</h3>
        <table>
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>تاریخ آزمون</th>
                    <th>نمره</th>
                    <th>پاسخ صحیح</th>
                    <th>پاسخ غلط</th>
                    <th>بی‌پاسخ</th>
                    <th>جزئیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($scores_result && $scores_result->num_rows > 0):
                    $row_num = 1;
                    while ($row = $scores_result->fetch_assoc()):
                        $score_from_20 = round($row['score'] / 5, 1);
                        $score_class = 'score-low';
                        if ($score_from_20 >= 14)
                            $score_class = 'score-high';
                        elseif ($score_from_20 >= 10)
                            $score_class = 'score-medium'; ?>
                        <tr>
                            <td><?php echo $row_num++; ?></td>
                            <td><?php echo $row['exam_start_time_shamsi'] ?: date('Y/m/d H:i', strtotime($row['exam_date'])); ?>
                            </td>
                            <td><span class="score-badge <?php echo $score_class; ?>"><?php echo $score_from_20; ?> از 20</span>
                            </td>
                            <td style="color:#10b981;"><?php echo $row['correct']; ?></td>
                            <td style="color:#ef4444;"><?php echo $row['wrong']; ?></td>
                            <td style="color:#f59e0b;"><?php echo $row['unanswered']; ?></td>
                            <td><a href="score_detail.php?id=<?php echo $row['id']; ?>" class="detail-link">🔍 مشاهده</a></td>
                        </tr>
                    <?php endwhile; else: ?>
                    <tr class="empty-row">
                        <td colspan="7">📭 هنوز در هیچ آزمونی شرکت نکرده‌اید</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>