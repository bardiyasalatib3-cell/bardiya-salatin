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

$page_title = 'صفحه آزمون';
$active_page = 'exam';

require_once 'config.php';
require_once 'jdf.php';

function getCurrentJalali()
{
    list($jy, $jm, $jd) = gregorian_to_jalali(date('Y'), date('m'), date('d'));
    $gh = date('H');
    $gi = date('i');
    return sprintf("%04d/%02d/%02d %02d:%02d", $jy, $jm, $jd, $gh, $gi);
}

function jalaliToTimestamp($shamsi_datetime)
{
    if (empty($shamsi_datetime))
        return null;
    $parts = explode(' ', $shamsi_datetime);
    if (count($parts) != 2)
        return null;
    $dateParts = explode('/', $parts[0]);
    $timeParts = explode(':', $parts[1]);
    if (count($dateParts) != 3 || count($timeParts) != 2)
        return null;
    $year = (int) $dateParts[0];
    $month = (int) $dateParts[1];
    $day = (int) $dateParts[2];
    $hour = (int) $timeParts[0];
    $minute = (int) $timeParts[1];
    list($gy, $gm, $gd) = jalali_to_gregorian($year, $month, $day);
    return mktime($hour, $minute, 0, $gm, $gd, $gy);
}

$settings_result = $conn->query("SELECT * FROM exam_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$current_settings = $settings_result->fetch_assoc();
$exam_start_time_shamsi = $current_settings['exam_start_time_shamsi'] ?? null;
$exam_duration = $current_settings['exam_duration'] ?? 60;
$exam_book = $current_settings['exam_book'] ?? 'ریاضی';

$start_timestamp = null;
if ($exam_start_time_shamsi) {
    $parts = explode(' ', $exam_start_time_shamsi);
    $date_parts = explode('/', $parts[0]);
    $time_parts = explode(':', $parts[1]);
    $jy = (int) $date_parts[0];
    $jm = (int) $date_parts[1];
    $jd = (int) $date_parts[2];
    $hour = (int) $time_parts[0];
    $minute = (int) $time_parts[1];
    list($gy, $gm, $gd) = jalali_to_gregorian($jy, $jm, $jd);
    $start_timestamp = mktime($hour, $minute, 0, $gm, $gd, $gy);
}

$questions_result = $conn->query("SELECT * FROM soal_t ORDER BY id ASC");
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}
$total_questions = count($questions);

$user_phone = $_SESSION['user_phone'] ?? '';
$already_attempted = false;
if ($user_phone && $exam_start_time_shamsi) {
    $check = $conn->query("SELECT id FROM exam_attempts WHERE user_phone = '$user_phone' AND exam_start_time_shamsi = '$exam_start_time_shamsi'");
    if ($check && $check->num_rows > 0) {
        $already_attempted = true;
    }
}

$exam_is_past = ($start_timestamp && $start_timestamp < time());

// تابع بررسی اینکه آیا زمان آزمون تمام شده
$exam_ended = false;
if ($start_timestamp) {
    $end_timestamp = $start_timestamp + ($exam_duration * 60);
    $exam_ended = (time() > $end_timestamp);
}

include 'header.php';
?>

<style>
    .timer-card {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 30px;
        padding: 20px;
        text-align: center;
        color: #fff;
        margin-bottom: 20px
    }

    .timer-display {
        font-size: 48px;
        font-family: monospace;
        background: rgba(0, 0, 0, 0.3);
        display: inline-block;
        padding: 10px 25px;
        border-radius: 50px;
        margin: 10px 0;
        letter-spacing: 5px
    }

    .questions-container {
        display: none
    }

    .questions-container.active {
        display: block
    }

    .question-card {
        background: #fff;
        border-radius: 24px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08)
    }

    .question-text {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1e293b
    }

    .question-image {
        max-width: 100%;
        max-height: 300px;
        border-radius: 12px;
        margin: 10px 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: block
    }

    .option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        background: #f8fafc;
        border-radius: 50px;
        margin-bottom: 8px;
        cursor: pointer;
        border: 2px solid #e2e8f0;
        transition: 0.2s
    }

    .option:hover {
        border-color: #667eea;
        background: #f1f5f9
    }

    .option input {
        width: 18px;
        height: 18px;
        cursor: pointer
    }

    .option-text {
        flex: 1;
        font-size: 14px
    }

    .nav-buttons {
        display: flex;
        justify-content: space-between;
        margin: 15px 0;
        gap: 15px
    }

    .btn-nav {
        padding: 10px 20px;
        border-radius: 50px;
        border: none;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s
    }

    .btn-prev {
        background: #e2e8f0;
        color: #334155
    }

    .btn-next {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff
    }

    .btn-nav:hover {
        transform: translateY(-2px)
    }

    .progress-bar {
        background: #e2e8f0;
        border-radius: 30px;
        height: 8px;
        margin: 15px 0
    }

    .progress-fill {
        background: linear-gradient(135deg, #667eea, #764ba2);
        height: 100%;
        width: 0%;
        border-radius: 30px;
        transition: width 0.3s
    }

    .question-counter {
        text-align: center;
        font-size: 13px;
        color: #64748b;
        margin-top: 8px
    }

    .message-box {
        background: #fff;
        border-radius: 30px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1)
    }

    .btn-back {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
        padding: 12px 30px;
        border-radius: 60px;
        text-decoration: none;
        display: inline-block;
        margin-top: 15px
    }

    .exam-info {
        font-size: 13px;
        margin-top: 8px;
        opacity: 0.9
    }

    .end-message {
        background: #d1fae5;
        color: #065f46;
        padding: 20px;
        border-radius: 20px;
        text-align: center;
        margin-top: 20px
    }

    .end-message h3 {
        font-size: 20px;
        margin-bottom: 10px
    }

    @media (max-width:600px) {
        .timer-display {
            font-size: 32px;
            padding: 8px 20px
        }

        .question-text {
            font-size: 14px
        }

        .option-text {
            font-size: 13px
        }

        .btn-nav {
            padding: 8px 16px;
            font-size: 13px
        }

        .question-image {
            max-height: 200px
        }
    }
</style>

<?php if (!$exam_start_time_shamsi): ?>
    <div class="message-box">
        <h2>⏰ زمان آزمون مشخص نشده</h2>
        <p>معلم محترم زمان آزمون را تنظیم نکرده است.</p><a href="index.php" class="btn-back">بازگشت به صفحه اصلی</a>
    </div>
<?php elseif ($total_questions == 0): ?>
    <div class="message-box">
        <h2>📭 سوالی وجود ندارد</h2>
        <p>معلم محترم هنوز سوالی اضافه نکرده است.</p><a href="index.php" class="btn-back">بازگشت به صفحه اصلی</a>
    </div>
<?php elseif ($exam_is_past): ?>
    <div class="message-box">
        <h2>⏰ زمان آزمون به پایان رسیده</h2>
        <p>زمان برگزاری این آزمون گذشته است.</p>
        <p style="font-size:13px;color:#64748b;margin-top:10px;">📅 زمان آزمون:
            <?php echo htmlspecialchars($exam_start_time_shamsi); ?></p><a href="index.php" class="btn-back">بازگشت به صفحه
            اصلی</a>
    </div>
<?php elseif ($already_attempted): ?>
    <div class="message-box">
        <h2>✅ شما قبلاً در این آزمون شرکت کرده‌اید</h2>
        <p>امکان شرکت مجدد وجود ندارد.</p><a href="index.php" class="btn-back">بازگشت به صفحه اصلی</a>
    </div>
<?php else: ?>
    <div class="timer-card" id="timerCard">
        <div class="timer-label" id="timerLabel">⏳ زمان باقی‌مانده تا شروع آزمون</div>
        <div class="timer-display" id="timerDisplay">--:--:--</div>
        <div class="exam-info">📅 شروع: <?php echo htmlspecialchars($exam_start_time_shamsi); ?> | ⏱️ مدت:
            <?php echo $exam_duration; ?> دقیقه | 📋 سوالات: <?php echo $total_questions; ?> | 📖
            <?php echo htmlspecialchars($exam_book); ?></div>
    </div>
    <div class="questions-container" id="questionsContainer">
        <form method="POST" action="submit_exam.php" id="examForm">
            <?php foreach ($questions as $index => $q): ?>
                <div class="question-card" id="q_<?php echo $index; ?>"
                    style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                    <?php if (!empty($q['image_path']) && file_exists($q['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($q['image_path']); ?>" alt="تصویر سوال" class="question-image">
                    <?php endif; ?>
                    <?php if (!empty(trim($q['soal']))): ?>
                        <div class="question-text"><?php echo htmlspecialchars($q['soal']); ?></div>
                    <?php endif; ?>
                    <div class="options">
                        <label class="option"><input type="radio" name="q_<?php echo $q['id']; ?>"
                                value="<?php echo htmlspecialchars($q['gozin1']); ?>"><span class="option-text">1.
                                <?php echo htmlspecialchars($q['gozin1']); ?></span></label>
                        <label class="option"><input type="radio" name="q_<?php echo $q['id']; ?>"
                                value="<?php echo htmlspecialchars($q['gozin2']); ?>"><span class="option-text">2.
                                <?php echo htmlspecialchars($q['gozin2']); ?></span></label>
                        <?php if (!empty($q['gozin3'])): ?><label class="option"><input type="radio"
                                    name="q_<?php echo $q['id']; ?>" value="<?php echo htmlspecialchars($q['gozin3']); ?>"><span
                                    class="option-text">3.
                                    <?php echo htmlspecialchars($q['gozin3']); ?></span></label><?php endif; ?>
                        <?php if (!empty($q['gozin4'])): ?><label class="option"><input type="radio"
                                    name="q_<?php echo $q['id']; ?>" value="<?php echo htmlspecialchars($q['gozin4']); ?>"><span
                                    class="option-text">4.
                                    <?php echo htmlspecialchars($q['gozin4']); ?></span></label><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="question-counter" id="questionCounter">سوال 1 از <?php echo $total_questions; ?></div>

            <div class="nav-buttons">
                <button type="button" class="btn-nav btn-prev" id="prevBtn" onclick="changeQuestion(-1)">‹ قبلی</button>
                <button type="button" class="btn-nav btn-next" id="nextBtn" onclick="changeQuestion(1)">بعدی ›</button>
            </div>

            <!-- پیام پایان آزمون که در آخرین سوال نمایش داده می‌شود -->
            <div id="endMessage" style="display:none;" class="end-message">
                <h3>✅ آزمون به پایان رسید</h3>
                <p>شما به تمام سوالات پاسخ داده‌اید. برای ثبت پاسخ‌ها روی دکمه "ثبت و مشاهده نتیجه" کلیک کنید.</p>
                <button type="button" class="btn-submit" onclick="submitExam()"
                    style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:50px;padding:12px 30px;font-weight:bold;cursor:pointer;margin-top:15px;">📊
                    ثبت و مشاهده نتیجه</button>
            </div>

            <!-- دکمه ارسال (پنهان تا آخرین سوال) -->
            <div id="submitButtonWrapper" style="display:none; margin-top:20px; text-align:center;">
                <button type="button" class="btn-submit" onclick="submitExam()"
                    style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:50px;padding:12px 30px;font-weight:bold;cursor:pointer;width:100%;font-size:16px;">📊
                    ثبت و مشاهده نتیجه</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
    const startTimestamp = <?php echo $start_timestamp ? $start_timestamp * 1000 : 0; ?>;
    const examDurationMs = <?php echo $exam_duration; ?> * 60 * 1000;
    let currentQuestion = 0, totalQuestions = <?php echo $total_questions; ?>, examStarted = false, timerInterval = null, endTimestamp = null;

    function showExamQuestions() {
        document.getElementById('timerCard').style.display = 'none';
        document.getElementById('questionsContainer').classList.add('active');
        // بررسی اگر سوال آخر است، دکمه بعدی را غیرفعال کن
        updateButtons();
    }

    function updateButtons() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const endMsg = document.getElementById('endMessage');
        const submitWrapper = document.getElementById('submitButtonWrapper');

        // دکمه قبلی
        if (prevBtn) prevBtn.style.display = currentQuestion === 0 ? 'none' : 'inline-block';

        // دکمه بعدی و پیام پایان
        if (currentQuestion === totalQuestions - 1) {
            // آخرین سوال - دکمه بعدی را مخفی کن
            if (nextBtn) nextBtn.style.display = 'none';
            // نمایش پیام پایان
            if (endMsg) endMsg.style.display = 'block';
            // نمایش دکمه ثبت
            if (submitWrapper) submitWrapper.style.display = 'block';
        } else {
            if (nextBtn) nextBtn.style.display = 'inline-block';
            if (endMsg) endMsg.style.display = 'none';
            if (submitWrapper) submitWrapper.style.display = 'none';
            // اگر سوال اول است دکمه قبلی را مخفی کن
            if (prevBtn) prevBtn.style.display = currentQuestion === 0 ? 'none' : 'inline-block';
        }

        // بروزرسانی پیشرفت
        const progress = ((currentQuestion + 1) / totalQuestions) * 100;
        document.getElementById('progressFill').style.width = progress + '%';
        document.getElementById('questionCounter').innerHTML = 'سوال ' + (currentQuestion + 1) + ' از ' + totalQuestions;
    }

    function startExamTimer() {
        examStarted = true;
        if (!endTimestamp) { endTimestamp = Date.now() + examDurationMs }
        const card = document.getElementById('timerCard');
        card.style.display = 'block';
        card.style.background = 'linear-gradient(135deg,#ef4444,#dc2626)';
        document.getElementById('timerLabel').innerHTML = '⏱️ زمان باقی‌مانده از آزمون';
        function update() {
            const remaining = endTimestamp - Date.now();
            if (remaining <= 0) {
                clearInterval(timerInterval);
                alert('زمان آزمون به پایان رسید!');
                submitExam();
                return;
            }
            const minutes = Math.floor(remaining / 60000), seconds = Math.floor((remaining % 60000) / 1000);
            document.getElementById('timerDisplay').innerHTML = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
            document.getElementById('timerDisplay').style.fontSize = '48px';
        }
        update();
        timerInterval = setInterval(update, 1000);
    }

    function startCountdown() {
        function updateCountdown() {
            const remaining = startTimestamp - Date.now();
            if (remaining <= 0) {
                clearInterval(timerInterval);
                startExamTimer();
                showExamQuestions();
                return;
            }
            const hours = Math.floor(remaining / 3600000), minutes = Math.floor((remaining % 3600000) / 60000), seconds = Math.floor((remaining % 60000) / 1000);
            document.getElementById('timerDisplay').innerHTML = hours.toString().padStart(2, '0') + ':' + minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
        }
        updateCountdown();
        timerInterval = setInterval(updateCountdown, 1000);
    }

    function init() {
        if (startTimestamp <= Date.now()) {
            startExamTimer();
            showExamQuestions();
            // بعد از نمایش سوالات، دکمه‌ها را تنظیم کن
            setTimeout(updateButtons, 100);
        } else {
            startCountdown();
        }
    }

    function changeQuestion(d) {
        const n = currentQuestion + d;
        if (n >= 0 && n < totalQuestions) {
            document.getElementById('q_' + currentQuestion).style.display = 'none';
            currentQuestion = n;
            document.getElementById('q_' + currentQuestion).style.display = 'block';
            updateButtons();
        }
    }

    function submitExam() {
        if (confirm('آیا از پایان آزمون اطمینان دارید؟')) {
            if (timerInterval) clearInterval(timerInterval);
            document.getElementById('examForm').submit();
        }
    }

    if (startTimestamp > 0) {
        init();
    } else {
        document.getElementById('timerCard').innerHTML = '<div class="timer-label">⏰ زمان شروع معتبر نیست</div>';
    }

    // مقداردهی اولیه
    setTimeout(function () {
        if (document.getElementById('prevBtn')) {
            document.getElementById('prevBtn').style.display = 'none';
        }
        updateButtons();
    }, 200);
</script>
<?php include 'footer.php'; ?>