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

$page_title = 'نتیجه آزمون';
$active_page = 'exam';

require_once 'config.php';

$user_name = $_SESSION['user_name'] ?? 'کاربر';
$user_phone = $_SESSION['user_phone'] ?? '';

$settings_result = $conn->query("SELECT * FROM exam_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$current_settings = $settings_result->fetch_assoc();
$exam_start_time_shamsi = $current_settings['exam_start_time_shamsi'] ?? null;
$exam_duration = $current_settings['exam_duration'] ?? 60;
$exam_id = $current_settings['id'] ?? 0;

$questions_result = $conn->query("SELECT * FROM soal_t ORDER BY id ASC");
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[$row['id']] = $row;
}

$total = count($questions);
$correct = 0;
$wrong = 0;
$unanswered = 0;
$answers_log = [];

foreach ($questions as $qid => $q) {
    $user_answer = $_POST['q_' . $qid] ?? '';
    $correct_answer = $q['javab'];
    if ($user_answer == '') {
        $unanswered++;
        $answers_log[] = "❌ سوال {$qid}: پاسخ داده نشده";
    } elseif ($user_answer == $correct_answer) {
        $correct++;
        $answers_log[] = "✅ سوال {$qid}: صحیح ({$user_answer})";
    } else {
        $wrong++;
        $answers_log[] = "❌ سوال {$qid}: غلط ({$user_answer}) - صحیح: {$correct_answer}";
    }
}

$score = $total > 0 ? round(($correct / $total) * 100) : 0;
$answers_text = implode("\n", $answers_log);

$stmt = $conn->prepare("INSERT INTO exam_results (user_phone, user_name, total_questions, correct, wrong, unanswered, score, answers, exam_date, exam_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
$stmt->bind_param("ssiiiiisi", $user_phone, $user_name, $total, $correct, $wrong, $unanswered, $score, $answers_text, $exam_id);
$stmt->execute();
$stmt->close();

$conn->query("CREATE TABLE IF NOT EXISTS exam_attempts (id INT AUTO_INCREMENT PRIMARY KEY, user_phone VARCHAR(20) NOT NULL, exam_start_time_shamsi VARCHAR(30) NOT NULL, attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_attempt (user_phone, exam_start_time_shamsi))");
if ($user_phone && $exam_start_time_shamsi) {
    $conn->query("INSERT IGNORE INTO exam_attempts (user_phone, exam_start_time_shamsi) VALUES ('$user_phone', '$exam_start_time_shamsi')");
}

include 'header.php';
?>

<style>
    .result-card {
        max-width: 550px;
        margin: 0 auto;
        background: #fff;
        border-radius: 40px;
        padding: 35px;
        text-align: center;
        box-shadow: 0 20px 35px rgba(0, 0, 0, 0.1)
    }

    .result-icon {
        font-size: 70px;
        margin-bottom: 15px
    }

    .result-title {
        font-size: 28px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 10px
    }

    .score-circle {
        width: 160px;
        height: 160px;
        margin: 25px auto;
        position: relative
    }

    .score-number {
        font-size: 48px;
        font-weight: bold;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
    }

    .score-good {
        color: #10b981
    }

    .score-medium {
        color: #f59e0b
    }

    .score-bad {
        color: #ef4444
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0
    }

    .detail-label {
        font-weight: 600;
        color: #64748b
    }

    .detail-value {
        font-weight: bold;
        font-size: 18px
    }

    .detail-correct {
        color: #10b981
    }

    .detail-wrong {
        color: #ef4444
    }

    .detail-unanswered {
        color: #f59e0b
    }

    .message-box {
        background: #d4edda;
        color: #155724;
        padding: 12px;
        border-radius: 20px;
        margin-top: 20px;
        font-size: 14px
    }

    .buttons {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        flex-wrap: wrap
    }

    .btn {
        flex: 1;
        padding: 12px;
        border-radius: 60px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.2s
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #334155
    }

    .btn:hover {
        transform: translateY(-2px)
    }

    .exam-info {
        background: #f1f5f9;
        border-radius: 20px;
        padding: 12px;
        margin-top: 20px;
        font-size: 12px;
        color: #64748b
    }

    @media (max-width:500px) {
        .result-card {
            padding: 25px
        }

        .result-title {
            font-size: 24px
        }

        .score-number {
            font-size: 36px
        }
    }
</style>

<div class="result-card">
    <div class="result-icon">📊</div>
    <div class="result-title">نتیجه آزمون</div>
    <div class="score-circle">
        <svg width="160" height="160">
            <circle cx="80" cy="80" r="70" fill="none" stroke="#e2e8f0" stroke-width="10" />
            <circle cx="80" cy="80" r="70" fill="none"
                stroke="<?php echo $score >= 70 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444'); ?>" stroke-width="10"
                stroke-dasharray="<?php echo ($score / 100) * 440; ?> 440" stroke-linecap="round"
                transform="rotate(-90 80 80)" />
        </svg>
        <div class="score-number <?php echo $score >= 70 ? 'score-good' : ($score >= 50 ? 'score-medium' : 'score-bad'); ?>">
            <?php echo $score; ?>%</div>
    </div>
    <div class="detail-row"><span class="detail-label">✅ پاسخ صحیح</span><span
            class="detail-value detail-correct"><?php echo $correct; ?> از <?php echo $total; ?></span></div>
    <div class="detail-row"><span class="detail-label">❌ پاسخ غلط</span><span
            class="detail-value detail-wrong"><?php echo $wrong; ?></span></div>
    <div class="detail-row"><span class="detail-label">❓ پاسخ نداده</span><span
            class="detail-value detail-unanswered"><?php echo $unanswered; ?></span></div>
    <div class="message-box">
        <?php if ($score >= 90)
            echo "🏆 عالی! نتیجه فوق‌العاده‌ای کسب کردی!";
        elseif ($score >= 75)
            echo "🎉 خیلی خوب!";
        elseif ($score >= 60)
            echo "👍 خوب! می‌تونی بهتر از این باشی!";
        elseif ($score >= 50)
            echo "📚 قابل قبول! بیشتر تمرین کن!";
        else
            echo "💪 ناامید نشو! دفعه بعد حتماً موفق می‌شی!"; ?>
    </div>
    <div class="exam-info">📅 زمان آزمون: <?php echo htmlspecialchars($exam_start_time_shamsi); ?> | ⏱️ مدت:
        <?php echo $exam_duration; ?> دقیقه</div>
    <div class="buttons"><a href="index.php" class="btn btn-primary">🏠 صفحه اصلی</a></div>
</div>
<?php include 'footer.php'; ?>