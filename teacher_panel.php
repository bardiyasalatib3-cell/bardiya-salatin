<?php
session_start();
date_default_timezone_set('Asia/Tehran');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$page_title = 'پنل مدیریت آزمون';
$active_page = 'teacher_panel';

require_once 'config.php';
require_once 'jdf.php';

// توابع تبدیل تاریخ
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

// ایجاد پوشه آپلود
$upload_dir = "uploads/questions/";
if (!is_dir($upload_dir))
    mkdir($upload_dir, 0777, true);

// ایجاد جدول تنظیمات
$conn->query("CREATE TABLE IF NOT EXISTS exam_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_start_time_shamsi VARCHAR(30) NOT NULL,
    exam_duration INT DEFAULT 60,
    exam_book VARCHAR(100) DEFAULT 'ریاضی',
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// اضافه کردن فیلد image_path به جدول soal_t
$fields = $conn->query("SHOW COLUMNS FROM soal_t");
$existing_fields = [];
while ($field = $fields->fetch_assoc()) {
    $existing_fields[] = $field['Field'];
}
if (!in_array('image_path', $existing_fields)) {
    $conn->query("ALTER TABLE soal_t ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
}

$fields = $conn->query("SHOW COLUMNS FROM exam_settings");
$existing_fields = [];
while ($field = $fields->fetch_assoc()) {
    $existing_fields[] = $field['Field'];
}
if (!in_array('exam_book', $existing_fields)) {
    $conn->query("ALTER TABLE exam_settings ADD COLUMN exam_book VARCHAR(100) DEFAULT 'ریاضی'");
}

$message = '';
$error = '';
$settings_result = $conn->query("SELECT * FROM exam_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$current = $settings_result->fetch_assoc();
$stored_time = $current['exam_start_time_shamsi'] ?? '';
$stored_duration = $current['exam_duration'] ?? 60;
$stored_book = $current['exam_book'] ?? 'ریاضی';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exam_datetime_shamsi'])) {
    $shamsi = trim($_POST['exam_datetime_shamsi']);
    $dur = (int) $_POST['exam_duration'];
    $exam_book = $_POST['exam_book'] ?? 'ریاضی';
    if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2} \d{1,2}:\d{1,2}$/', $shamsi)) {
        $timestamp = jalaliToTimestamp($shamsi);
        $now = time();
        if ($timestamp && $timestamp <= $now) {
            $error = "❌ زمان آزمون باید در آینده باشد. زمان فعلی: " . getCurrentJalali();
        } else {
            $conn->query("UPDATE exam_settings SET is_active = 0");
            $stmt = $conn->prepare("INSERT INTO exam_settings (exam_start_time_shamsi, exam_duration, exam_book, is_active) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sis", $shamsi, $dur, $exam_book);
            if ($stmt->execute()) {
                $message = "✅ زمان آزمون با موفقیت ذخیره شد.";
                $stored_time = $shamsi;
                $stored_duration = $dur;
                $stored_book = $exam_book;
            } else {
                $error = "❌ خطا در ذخیره زمان";
            }
            $stmt->close();
        }
    } else {
        $error = "❌ فرمت تاریخ صحیح نیست. مثال: 1404/02/27 14:30";
    }
}
if (isset($_GET['reset_time'])) {
    $conn->query("UPDATE exam_settings SET is_active = 0");
    header("Location: teacher_panel.php");
    exit();
}

// مدیریت سوالات با آپلود تصویر
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // حذف فایل تصویر
    $img_result = $conn->query("SELECT image_path FROM soal_t WHERE id = $id");
    if ($img_row = $img_result->fetch_assoc()) {
        if (!empty($img_row['image_path']) && file_exists($img_row['image_path'])) {
            unlink($img_row['image_path']);
        }
    }
    $conn->query("DELETE FROM soal_t WHERE id = $id");
    header("Location: teacher_panel.php?msg=سوال حذف شد");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $soal = $_POST['soal'] ?? '';
    $g1 = $_POST['g1'];
    $g2 = $_POST['g2'];
    $g3 = $_POST['g3'];
    $g4 = $_POST['g4'];
    $opt = (int) $_POST['correct_option'];
    $opts = [1 => $g1, 2 => $g2, 3 => $g3, 4 => $g4];
    $javab = $opts[$opt] ?? $g1;

    // پردازش تصویر
    $image_path = null;
    if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
        $target_dir = "uploads/questions/";
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);

        $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
        $file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        if (in_array($file_extension, $allowed_types)) {
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($_FILES['question_image']['size'] <= $max_size) {
                if (move_uploaded_file($_FILES['question_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }
    }

    // اگر تصویر وجود داشته باشد و متن سوال خالی باشد، متن را "تصویر سوال" می‌گذاریم
    if (empty($soal) && $image_path) {
        $soal = "تصویر سوال";
    }

    $stmt = $conn->prepare("INSERT INTO soal_t (soal, javab, gozin1, gozin2, gozin3, gozin4, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $soal, $javab, $g1, $g2, $g3, $g4, $image_path);
    $stmt->execute();
    $stmt->close();
    header("Location: teacher_panel.php?msg=سوال اضافه شد");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $soal = $_POST['soal'] ?? '';
    $g1 = $_POST['g1'];
    $g2 = $_POST['g2'];
    $g3 = $_POST['g3'];
    $g4 = $_POST['g4'];
    $opt = (int) $_POST['correct_option'];
    $opts = [1 => $g1, 2 => $g2, 3 => $g3, 4 => $g4];
    $javab = $opts[$opt] ?? $g1;

    // دریافت تصویر فعلی
    $current_img = $conn->query("SELECT image_path FROM soal_t WHERE id = $id")->fetch_assoc();
    $image_path = $current_img['image_path'] ?? null;

    // آپلود تصویر جدید
    if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] == 0) {
        $target_dir = "uploads/questions/";
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);

        $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
        $file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        if (in_array($file_extension, $allowed_types)) {
            $max_size = 5 * 1024 * 1024;
            if ($_FILES['question_image']['size'] <= $max_size) {
                // حذف تصویر قبلی
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                if (move_uploaded_file($_FILES['question_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }
    }

    // اگر تصویر وجود داشته باشد و متن سوال خالی باشد، متن را "تصویر سوال" می‌گذاریم
    if (empty($soal) && $image_path) {
        $soal = "تصویر سوال";
    }

    $stmt = $conn->prepare("UPDATE soal_t SET soal=?, javab=?, gozin1=?, gozin2=?, gozin3=?, gozin4=?, image_path=? WHERE id=?");
    $stmt->bind_param("sssssssi", $soal, $javab, $g1, $g2, $g3, $g4, $image_path, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: teacher_panel.php?msg=سوال ویرایش شد");
    exit();
}

$msg = $_GET['msg'] ?? '';
$questions = $conn->query("SELECT * FROM soal_t ORDER BY id DESC");
$total = $questions->num_rows;
$edit = isset($_GET['edit']) ? $conn->query("SELECT * FROM soal_t WHERE id=" . (int) $_GET['edit'])->fetch_assoc() : null;
$now_shamsi = getCurrentJalali();

include 'header.php';
?>

<style>
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px
    }

    .stat-item {
        background: #fff;
        border-radius: 28px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05)
    }

    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #667eea
    }

    .stat-label {
        color: #64748b;
        font-size: 13px;
        margin-top: 8px
    }

    .time-card {
        background: linear-gradient(135deg, #667eea10, #764ba210);
        border: 1px solid #667eea30
    }

    .datetime-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px
    }

    .datetime-field {
        flex: 1;
        min-width: 200px
    }

    .datetime-field label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        font-size: 13px;
        color: #334155
    }

    .datetime-field input,
    .datetime-field select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 60px;
        font-size: 14px;
        background: #fff
    }

    .btn-save-time {
        background: #10b981;
        color: #fff;
        border: none;
        border-radius: 60px;
        padding: 12px 24px;
        cursor: pointer;
        font-weight: bold;
        width: 100%
    }

    .btn-save-time:hover {
        transform: translateY(-2px)
    }

    .time-info {
        background: linear-gradient(135deg, #1e293b, #2d3a5e);
        border-radius: 24px;
        padding: 18px;
        margin-top: 20px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px
    }

    .time-value {
        font-size: 22px;
        font-weight: bold;
        font-family: monospace;
        background: rgba(255, 255, 255, 0.15);
        padding: 6px 18px;
        border-radius: 60px
    }

    .reset-link {
        color: #fbbf24;
        text-decoration: none
    }

    .exam-info-badge {
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 12px;
        border-radius: 30px;
        font-size: 13px
    }

    .form-card {
        background: #fff;
        border-radius: 32px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08)
    }

    .form-title {
        font-size: 20px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #667eea;
        display: inline-block
    }

    .option-item {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        padding: 8px 18px;
        border-radius: 60px;
        margin-bottom: 12px
    }

    .option-item span {
        width: 70px;
        font-weight: bold
    }

    .correct-radio {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 20px;
        background: #f1f5f9;
        padding: 12px 20px;
        border-radius: 60px;
        margin: 20px 0
    }

    .question-table {
        width: 100%;
        border-collapse: collapse
    }

    .question-table th,
    .question-table td {
        padding: 12px;
        text-align: right;
        border-bottom: 1px solid #e2e8f0
    }

    .question-table th {
        background: #f1f5f9
    }

    .btn-sm {
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 12px;
        text-decoration: none;
        display: inline-block;
        margin: 0 3px
    }

    .btn-edit {
        background: #f59e0b;
        color: #fff
    }

    .btn-delete {
        background: #ef4444;
        color: #fff
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        padding: 12px;
        border-radius: 20px;
        margin-top: 15px
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-radius: 20px;
        margin-top: 15px
    }

    .current-time-info {
        background: #e0e7ff;
        padding: 10px 15px;
        border-radius: 20px;
        margin-bottom: 20px;
        font-size: 13px;
        text-align: center
    }

    .question-image-preview {
        max-width: 100px;
        max-height: 80px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0
    }

    .file-input {
        padding: 8px;
        border: 2px dashed #e2e8f0;
        border-radius: 16px;
        width: 100%;
        cursor: pointer
    }

    .file-input:hover {
        border-color: #667eea
    }

    .question-type-badge {
        background: #e0e7ff;
        color: #4338ca;
        padding: 2px 8px;
        border-radius: 30px;
        font-size: 10px
    }

    @media (max-width:700px) {
        .time-info {
            flex-direction: column;
            align-items: flex-start
        }

        .datetime-row {
            flex-direction: column
        }
    }
</style>

<div class="dashboard-stats">
    <div class="stat-item">
        <div class="stat-number"><?php echo $total; ?></div>
        <div class="stat-label">📋 تعداد سوالات</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?php echo $stored_time ? "✓" : "—"; ?></div>
        <div class="stat-label">⏰ زمان آزمون</div>
    </div>
</div>

<div class="form-card time-card">
    <div class="form-title">⏰ تنظیم زمان آزمون</div>
    <div class="current-time-info">🕐 زمان فعلی سرور: <strong><?php echo $now_shamsi; ?></strong> <span
            style="font-size:11px;color:#4338ca;">(زمان وارد شده باید بعد از این زمان باشد)</span></div>
    <form method="POST">
        <div class="datetime-row">
            <div class="datetime-field"><label>📅 تاریخ و زمان شروع (شمسی)</label><input type="text"
                    name="exam_datetime_shamsi" value="<?php echo htmlspecialchars($stored_time ?: $now_shamsi); ?>"
                    placeholder="1404/02/27 14:30" required><small style="font-size:11px;color:#64748b;">فرمت:
                    سال/ماه/روز ساعت:دقیقه</small></div>
            <div class="datetime-field"><label>⏱️ مدت زمان (دقیقه)</label><input type="number" name="exam_duration"
                    value="<?php echo $stored_duration; ?>" min="1" max="180" required></div>
            <div class="datetime-field"><label>&nbsp;</label><button type="submit" class="btn-save-time">💾 ذخیره
                    تغییرات</button></div>
        </div>
        <div class="datetime-field" style="margin-top:15px;"><label>📖 عنوان آزمون</label><input type="text"
                name="exam_book" value="<?php echo htmlspecialchars($stored_book); ?>"
                placeholder="مثال: ریاضی، علوم، فارسی، قرآن" style="font-family:Tahoma;"></div>
    </form>
    <?php if ($stored_time): ?>
        <div class="time-info"><span>📌 زمان ثبت‌شده:</span><span
                class="time-value"><?php echo htmlspecialchars($stored_time); ?></span><span class="exam-info-badge">📖
                <?php echo htmlspecialchars($stored_book); ?></span><span>⏱️ مدت: <?php echo $stored_duration; ?>
                دقیقه</span><a href="?reset_time=1" class="reset-link" onclick="return confirm('بازنشانی شود؟')">🔄
                بازنشانی</a></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
</div>

<div class="form-card">
    <div class="form-title"><?php echo $edit ? "✏️ ویرایش سوال" : "➕ افزودن سوال جدید"; ?></div>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>

        <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:8px;font-weight:600;">📌 متن سوال <span
                    style="font-size:11px;color:#64748b;">(در صورت وجود تصویر، متن اختیاری است)</span></label>
            <textarea name="soal"
                style="width:100%;padding:12px;border-radius:24px;border:2px solid #e2e8f0;resize:vertical;"><?php echo $edit ? htmlspecialchars($edit['soal']) : ''; ?></textarea>
            <small style="font-size:11px;color:#64748b;">اگر تصویر آپلود کنید و متن را خالی بگذارید، فقط تصویر نمایش
                داده می‌شود</small>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:8px;font-weight:600;">🖼️ تصویر سوال <span
                    style="font-size:11px;color:#64748b;">(اختیاری)</span></label>
            <?php if ($edit && !empty($edit['image_path']) && file_exists($edit['image_path'])): ?>
                <div style="margin-bottom:10px;display:flex;align-items:center;gap:15px;">
                    <img src="<?php echo htmlspecialchars($edit['image_path']); ?>"
                        style="max-width:150px; max-height:120px; border-radius:12px; border:1px solid #e2e8f0;">
                    <span style="font-size:11px;color:#64748b;">(تصویر فعلی)</span>
                </div>
            <?php endif; ?>
            <input type="file" name="question_image" accept="image/*" class="file-input">
            <small style="font-size:11px;color:#64748b;display:block;margin-top:5px;">فرمت‌های مجاز: JPG, PNG, GIF,
                WEBP, SVG, BMP | حداکثر 5 مگابایت</small>
        </div>

        <?php for ($i = 1; $i <= 4; $i++):
            $val = $edit ? htmlspecialchars($edit['gozin' . $i]) : '';
            $required = ($i <= 2) ? 'required' : ''; ?>
            <div class="option-item"><span>🔵 گزینه <?php echo $i; ?>:</span><input type="text" name="g<?php echo $i; ?>"
                    value="<?php echo $val; ?>" <?php echo $required; ?> style="flex:1;padding:10px;border-radius:60px;">
            </div>
        <?php endfor; ?>

        <div class="correct-radio"><span style="font-weight:bold;">✅ پاسخ صحیح:</span>
            <?php for ($i = 1; $i <= 4; $i++):
                $checked = ($edit && $edit['javab'] == $edit['gozin' . $i]) ? 'checked' : ($edit ? '' : ($i == 1 ? 'checked' : '')); ?>
                <label><input type="radio" name="correct_option" value="<?php echo $i; ?>" <?php echo $checked; ?>> گزینه
                    <?php echo $i; ?></label>
            <?php endfor; ?>
        </div>

        <button type="submit" name="<?php echo $edit ? 'edit' : 'add'; ?>" class="btn-save-time"
            style="margin-top:10px;">💾 <?php echo $edit ? 'اعمال تغییرات' : 'ذخیره سوال'; ?></button>
        <?php if ($edit): ?><a href="teacher_panel.php"
                style="margin-right:12px;display:inline-block;padding:10px 20px;background:#94a3b8;color:#fff;border-radius:60px;text-decoration:none;">➕
                سوال جدید</a><?php endif; ?>
    </form>
</div>

<div class="form-card">
    <div class="form-title">📋 لیست سوالات</div>
    <?php if ($msg): ?>
        <div class="alert-success">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div style="overflow-x:auto;">
        <table class="question-table">
            <thead>
                <tr>
                    <th>نوع</th>
                    <th>متن سوال</th>
                    <th>تصویر</th>
                    <th>گزینه‌ها</th>
                    <th>پاسخ صحیح</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total > 0):
                    $questions->data_seek(0);
                    while ($q = $questions->fetch_assoc()):
                        $opts = [$q['gozin1'], $q['gozin2'], $q['gozin3'], $q['gozin4']];
                        $correct = array_search($q['javab'], $opts) + 1;
                        if (!$correct)
                            $correct = 1; ?>
                        <tr>
                            <td data-label="نوع">
                                <?php if (!empty($q['image_path']) && file_exists($q['image_path'])): ?>
                                    <span class="question-type-badge">🖼️ تصویری</span>
                                <?php else: ?>
                                    <span class="question-type-badge" style="background:#dcfce7;color:#166534;">📝 متنی</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="متن سوال" style="font-weight:500;">
                                <?php echo htmlspecialchars(mb_substr($q['soal'], 0, 60)); ?>...</td>
                            <td data-label="تصویر"><?php if (!empty($q['image_path']) && file_exists($q['image_path'])): ?><img
                                        src="<?php echo htmlspecialchars($q['image_path']); ?>"
                                        class="question-image-preview"><?php else: ?><span
                                        style="color:#94a3b8;">—</span><?php endif; ?></td>
                            <td data-label="گزینه‌ها"><?php for ($i = 0; $i < 4; $i++):
                                if (!empty($opts[$i])): ?>
                                        <div <?php echo ($i + 1 == $correct) ? 'style="color:#10b981;font-weight:bold;"' : ''; ?>>
                                            <?php echo ($i + 1) . '. ' . htmlspecialchars(mb_substr($opts[$i], 0, 40)); ?></div>
                                    <?php endif; endfor; ?>
                            </td>
                            <td data-label="پاسخ صحیح"><span
                                    style="background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;">گزینه
                                    <?php echo $correct; ?></span></td>
                            <td data-label="عملیات"><a href="?edit=<?php echo $q['id']; ?>" class="btn-sm btn-edit">✏️</a> <a
                                    href="?delete=<?php echo $q['id']; ?>" class="btn-sm btn-delete"
                                    onclick="return confirm('حذف شود؟')">🗑️</a></td>
                        </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;">📭 سوالی وجود ندارد</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>