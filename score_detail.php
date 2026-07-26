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

$page_title = 'جزئیات آزمون';
$active_page = 'my_scores';

require_once 'config.php';

$user_phone = $_SESSION['user_phone'] ?? '';
$result_id = (int) $_GET['id'];

$result = $conn->query("SELECT er.*, es.exam_start_time_shamsi FROM exam_results er LEFT JOIN exam_settings es ON er.exam_id = es.id WHERE er.id = $result_id AND er.user_phone = '$user_phone'");
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: my_scores.php");
    exit();
}

$score_from_20 = round($data['score'] / 5, 1);
$answers = explode("\n", $data['answers']);

include 'header.php';
?>

<style>
    .detail-container {
        max-width: 700px;
        margin: 0 auto
    }

    .detail-card {
        background: #fff;
        border-radius: 28px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08)
    }

    .detail-header {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e2e8f0
    }

    .detail-header h2 {
        font-size: 24px;
        color: #1e293b;
        margin-bottom: 10px
    }

    .score-circle {
        width: 120px;
        height: 120px;
        margin: 20px auto;
        position: relative
    }

    .score-number {
        font-size: 36px;
        font-weight: bold;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0
    }

    .info-label {
        font-weight: 600;
        color: #64748b
    }

    .answers-list {
        margin-top: 20px
    }

    .answer-item {
        padding: 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
        direction: ltr;
        text-align: left
    }

    .answer-correct {
        color: #10b981;
        background: #d1fae5
    }

    .answer-wrong {
        color: #ef4444;
        background: #fee2e2
    }

    .answer-unanswered {
        color: #f59e0b;
        background: #fef3c7
    }

    .btn-back {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
        padding: 12px 25px;
        border-radius: 60px;
        text-decoration: none;
        display: inline-block;
        margin-top: 20px
    }
</style>

<div class="detail-container">
    <div class="detail-card">
        <div class="detail-header">
            <h2>📝 جزئیات آزمون</h2>
            <div class="score-circle"><svg width="120" height="120">
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="8" />
                    <circle cx="60" cy="60" r="50" fill="none"
                        stroke="<?php echo $score_from_20 >= 14 ? '#10b981' : ($score_from_20 >= 10 ? '#f59e0b' : '#ef4444'); ?>"
                        stroke-width="8" stroke-dasharray="<?php echo ($score_from_20 / 20) * 314; ?> 314"
                        stroke-linecap="round" transform="rotate(-90 60 60)" />
                </svg>
                <div class="score-number">
                    <?php echo $score_from_20; ?>
                </div>
            </div>
        </div>
        <div class="info-row"><span class="info-label">📅 تاریخ آزمون:</span><span>
                <?php echo $data['exam_start_time_shamsi'] ?: date('Y/m/d H:i', strtotime($data['exam_date'])); ?>
            </span></div>
        <div class="info-row"><span class="info-label">✅ پاسخ صحیح:</span><span style="color:#10b981;">
                <?php echo $data['correct']; ?> از
                <?php echo $data['total_questions']; ?>
            </span></div>
        <div class="info-row"><span class="info-label">❌ پاسخ غلط:</span><span style="color:#ef4444;">
                <?php echo $data['wrong']; ?>
            </span></div>
        <div class="info-row"><span class="info-label">❓ پاسخ نداده:</span><span style="color:#f59e0b;">
                <?php echo $data['unanswered']; ?>
            </span></div>
        <div class="info-row"><span class="info-label">🎯 نمره نهایی:</span><span><strong>
                    <?php echo $score_from_20; ?> از 20
                </strong></span></div>
        <?php if (!empty($answers) && $answers[0] != ''): ?>
            <div class="answers-list">
                <div class="info-label" style="margin-bottom:10px;">📋 پاسخ‌های ثبت شده:</div>
                <?php foreach ($answers as $answer):
                    $answer_class = '';
                    if (strpos($answer, '✅') !== false)
                        $answer_class = 'answer-correct';
                    elseif (strpos($answer, '❌') !== false)
                        $answer_class = 'answer-wrong';
                    else
                        $answer_class = 'answer-unanswered'; ?>
                    <div class="answer-item <?php echo $answer_class; ?>">
                        <?php echo htmlspecialchars($answer); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div style="text-align:center;"><a href="my_scores.php" class="btn-back">← بازگشت به لیست نمرات</a></div>
    </div>
</div>
<?php include 'footer.php'; ?>