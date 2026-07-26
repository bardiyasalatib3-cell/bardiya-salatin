<?php
require_once 'config.php';

$settings_result = $conn->query("SELECT * FROM site_settings LIMIT 1");
if ($settings_result && $settings_result->num_rows > 0) {
    $site_settings = $settings_result->fetch_assoc();
    $site_name = $site_settings['site_name'] ?? 'سامانه آزمون آنلاین';
    $logo_text = $site_settings['logo_text'] ?? '📚';
    $primary_color = $site_settings['primary_color'] ?? '#667eea';
    $secondary_color = $site_settings['secondary_color'] ?? '#764ba2';
} else {
    $site_name = 'سامانه آزمون آنلاین';
    $logo_text = '📚';
    $primary_color = '#667eea';
    $secondary_color = '#764ba2';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo $page_title ?? $site_name; ?></title>
    <link rel="stylesheet" href="responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: Tahoma, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $secondary_color; ?> 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1)
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent
        }

        .nav-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .nav-btn {
            padding: 6px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block
        }

        .nav-btn:active {
            transform: scale(0.95)
        }

        .nav-btn-outline {
            border: 2px solid
                <?php echo $primary_color; ?>
            ;
            color: <?php echo $primary_color; ?>;
            background: #fff
        }

        .nav-btn-outline:hover {
            background: <?php echo $primary_color; ?>;
            color: #fff
        }

        .nav-btn-active {
            background: <?php echo $primary_color; ?>;
            color: #fff;
            border: 2px solid
                <?php echo $primary_color; ?>
            ;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4)
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 13px
        }

        .user-name {
            font-weight: 600;
            color: #1e293b
        }

        .main-content {
            flex: 1;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            width: 100%
        }

        @media (max-width:850px) {
            .header {
                flex-direction: column;
                text-align: center
            }

            .nav-links {
                justify-content: center
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo"><?php echo $logo_text; ?> <?php echo $site_name; ?></div>
        <div class="nav-links">
            <a href="index.php"
                class="nav-btn <?php echo ($active_page == 'index') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">🏠 صفحه
                اصلی</a>
            <?php if (isset($_SESSION['user_role'])): ?>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="admin_dashboard.php"
                        class="nav-btn <?php echo ($active_page == 'admin') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">👑 پنل
                        مدیریت</a>
                    <a href="settings.php"
                        class="nav-btn <?php echo ($active_page == 'settings') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">⚙️
                        تنظیمات</a>
                    <a href="export_pdf.php"
                        class="nav-btn <?php echo ($active_page == 'export') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">📄
                        خروجی PDF</a>
                    <a href="logout.php" class="nav-btn nav-btn-outline">🚪 خروج</a>
                <?php elseif ($_SESSION['user_role'] == 'teacher'): ?>
                    <a href="teacher_panel.php"
                        class="nav-btn <?php echo ($active_page == 'teacher_panel') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">👨‍🏫
                        پنل معلم</a>
                    <a href="manage_students.php"
                        class="nav-btn <?php echo ($active_page == 'manage_students') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">👥
                        مدیریت دانش‌آموزان</a>
                    <a href="reports.php"
                        class="nav-btn <?php echo ($active_page == 'reports') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">📊
                        گزارش‌ها</a>
                    <a href="export_pdf.php"
                        class="nav-btn <?php echo ($active_page == 'export') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">📄
                        خروجی PDF</a>
                    <a href="logout.php" class="nav-btn nav-btn-outline">🚪 خروج</a>
                <?php elseif ($_SESSION['user_role'] == 'student'): ?>
                    <a href="exam.php"
                        class="nav-btn <?php echo ($active_page == 'exam') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">📝 شرکت
                        در آزمون</a>
                    <a href="my_scores.php"
                        class="nav-btn <?php echo ($active_page == 'my_scores') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">📊
                        نمرات من</a>
                    <a href="logout.php" class="nav-btn nav-btn-outline">🚪 خروج</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php"
                    class="nav-btn <?php echo ($active_page == 'login') ? 'nav-btn-active' : 'nav-btn-outline'; ?>">🔐
                    ورود</a>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['user_name'])): ?>
            <div class="user-info">
                <span>👋</span>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span style="font-size:11px;">(<?php
                if ($_SESSION['user_role'] == 'admin')
                    echo 'مدیر';
                elseif ($_SESSION['user_role'] == 'teacher')
                    echo 'معلم';
                else
                    echo 'دانش‌آموز';
                ?>)</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="main-content"></div>