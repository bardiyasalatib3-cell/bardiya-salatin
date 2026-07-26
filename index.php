<?php
session_start();
date_default_timezone_set('Asia/Tehran');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'صفحه اصلی | سامانه آزمون';
$active_page = 'index';

require_once 'config.php';

$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch_assoc();
$site_name = $settings['site_name'] ?? 'سامانه آزمون آنلاین';

$total_questions = $conn->query("SELECT COUNT(*) as count FROM soal_t")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$user_role = $_SESSION['user_role'] ?? '';

include 'header.php';
?>

<style>
    .hero {
        background: linear-gradient(135deg, #fff5f0 0%, #ffe8e0 100%);
        border-radius: 48px;
        padding: 50px 30px;
        text-align: center;
        margin-bottom: 45px;
        box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08)
    }

    .hero h1 {
        font-size: 38px;
        background: linear-gradient(135deg, #9a3412, #c2410c);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 16px
    }

    .hero p {
        font-size: 16px;
        color: #78350f;
        max-width: 600px;
        margin: 0 auto 25px auto;
        background: rgba(255, 255, 240, 0.6);
        display: inline-block;
        padding: 6px 20px;
        border-radius: 60px
    }

    .hero-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap
    }

    .btn-large {
        padding: 12px 32px;
        font-size: 15px;
        border-radius: 60px;
        font-weight: 600;
        transition: 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px
    }

    .btn-exam {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff
    }

    .btn-teacher {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        margin-bottom: 50px
    }

    .stat-card {
        border-radius: 28px;
        padding: 25px 15px;
        text-align: center;
        transition: 0.2s;
        box-shadow: 0 10px 25px -8px rgba(0, 0, 0, 0.06)
    }

    .stat-card:nth-child(1) {
        background: linear-gradient(145deg, #fef9c3, #fef3c7);
        border-bottom: 4px solid #eab308
    }

    .stat-card:nth-child(2) {
        background: linear-gradient(145deg, #dcfce7, #d1fae5);
        border-bottom: 4px solid #10b981
    }

    .stat-card:nth-child(3) {
        background: linear-gradient(145deg, #dbeafe, #bfdbfe);
        border-bottom: 4px solid #3b82f6
    }

    .stat-icon {
        font-size: 44px;
        margin-bottom: 10px
    }

    .stat-number {
        font-size: 38px;
        font-weight: 800;
        color: #1e293b
    }

    .stat-label {
        font-size: 14px;
        font-weight: 500;
        color: #334155;
        margin-top: 6px
    }

    .features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        margin-bottom: 20px
    }

    .feature-card {
        border-radius: 28px;
        padding: 25px 20px;
        text-align: center;
        transition: 0.2s;
        box-shadow: 0 8px 20px -6px rgba(0, 0, 0, 0.05)
    }

    .feature-card:hover {
        transform: translateY(-4px)
    }

    .feature-card:nth-child(1) {
        background: #fff7ed;
        border-bottom: 4px solid #f97316
    }

    .feature-card:nth-child(2) {
        background: #eff6ff;
        border-bottom: 4px solid #3b82f6
    }

    .feature-card:nth-child(3) {
        background: #f0fdf4;
        border-bottom: 4px solid #22c55e
    }

    .feature-icon {
        font-size: 40px;
        width: 75px;
        height: 75px;
        line-height: 75px;
        border-radius: 50%;
        margin: 0 auto 15px auto
    }

    .feature-card:nth-child(1) .feature-icon {
        background: #ffedd5;
        color: #ea580c
    }

    .feature-card:nth-child(2) .feature-icon {
        background: #dbeafe;
        color: #2563eb
    }

    .feature-card:nth-child(3) .feature-icon {
        background: #dcfce7;
        color: #16a34a
    }

    .feature-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 10px
    }

    .feature-desc {
        font-size: 13px;
        color: #475569;
        line-height: 1.5
    }

    @media (max-width:600px) {
        .stats {
            grid-template-columns: 1fr;
            gap: 15px
        }

        .features {
            grid-template-columns: 1fr;
            gap: 18px
        }

        .hero-buttons {
            flex-direction: column;
            align-items: center
        }

        .btn-large {
            width: 80%;
            justify-content: center
        }

        .hero h1 {
            font-size: 28px
        }
    }
</style>

<div class="hero">
    <h1>به <?php echo htmlspecialchars($site_name); ?> خوش آمدید</h1>
    <p>سامانه‌ای حرفه‌ای برای برگزاری آزمون‌های زمان‌دار با امکانات کامل</p>
    <div class="hero-buttons">
        <?php if ($user_role == 'student'): ?>
            <a href="exam.php" class="btn-large btn-exam">📝 شروع آزمون</a>
        <?php elseif ($user_role == 'teacher'): ?>
            <a href="teacher_panel.php" class="btn-large btn-teacher">👨‍🏫 پنل مدیریت</a>
        <?php elseif ($user_role == 'admin'): ?>
            <a href="admin_dashboard.php" class="btn-large btn-teacher">👑 پنل مدیریت</a>
        <?php endif; ?>
    </div>
</div>

<div class="stats">
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-number"><?php echo $total_questions; ?></div>
        <div class="stat-label">سوال در بانک سوالات</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-number"><?php echo $total_students; ?></div>
        <div class="stat-label">دانش‌آموز فعال</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏆</div>
        <div class="stat-number">0</div>
        <div class="stat-label">آزمون برگزار شده</div>
    </div>
</div>

<div class="features">
    <div class="feature-card">
        <div class="feature-icon">📝</div>
        <div class="feature-title">آزمون آنلاین</div>
        <div class="feature-desc">شرکت در آزمون‌ها بدون نیاز به نصب نرم‌افزار، فقط با مرورگر</div>
    </div>
    <div class="feature-card">
        <div class="feature-icon">⏱️</div>
        <div class="feature-title">زمان‌بندی دقیق</div>
        <div class="feature-desc">تعیین زمان شروع و مدت آزمون توسط معلم، شروع خودکار شمارش معکوس</div>
    </div>
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <div class="feature-title">نتیجه‌گیری آنی</div>
        <div class="feature-desc">مشاهده نمره، پاسخ صحیح و تحلیل عملکرد بلافاصله پس از پایان آزمون</div>
    </div>
</div>

<?php include 'footer.php'; ?>