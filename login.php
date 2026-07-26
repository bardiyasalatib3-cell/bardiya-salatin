<?php
session_start();
date_default_timezone_set('Asia/Tehran');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($_SESSION['user_role'] == 'teacher') {
        header("Location: teacher_panel.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

require_once 'config.php';

$error = '';

$students_list = $conn->query("SELECT id, name, phone, plain_password FROM users WHERE role = 'student' ORDER BY name ASC");
$teachers_list = $conn->query("SELECT id, name, phone, plain_password FROM users WHERE role = 'teacher' ORDER BY name ASC");
$admin_list = $conn->query("SELECT id, name, phone, plain_password FROM users WHERE role = 'admin' ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $phone = $_POST['phone'] ?? '';
    $password = md5($_POST['password'] ?? '');
    $result = $conn->query("SELECT * FROM users WHERE phone = '$phone' AND password = '$password'");
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_role'] = $user['role'];
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] == 'teacher') {
            header("Location: teacher_panel.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "شماره تلفن یا رمز عبور اشتباه است";
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه آزمون</title>
    <link rel="stylesheet" href="responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: Tahoma, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px
        }

        .login-container {
            max-width: 580px;
            width: 100%
        }

        .login-card {
            background: white;
            border-radius: 48px;
            padding: 40px 35px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25)
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px
        }

        .login-header .icon {
            font-size: 64px;
            margin-bottom: 12px
        }

        .login-header h2 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 8px
        }

        .input-group {
            margin-bottom: 22px
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            color: #334155
        }

        .input-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 60px;
            font-size: 14px;
            background: #f8fafc
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15)
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 60px;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4)
        }

        .users-section {
            margin-top: 30px
        }

        .users-title {
            font-size: 14px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0
        }

        .users-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 350px;
            overflow-y: auto
        }

        .users-grid::-webkit-scrollbar {
            width: 5px
        }

        .users-grid::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px
        }

        .users-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px
        }

        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-radius: 60px;
            padding: 10px 18px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: 0.2s
        }

        .user-item:hover {
            border-color: #667eea;
            background: #e0e7ff;
            transform: translateX(-5px)
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap
        }

        .user-icon {
            font-size: 24px
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px
        }

        .user-phone {
            font-family: monospace;
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 30px
        }

        .user-pass {
            font-family: monospace;
            font-size: 11px;
            color: #10b981;
            background: #dcfce7;
            padding: 2px 8px;
            border-radius: 30px
        }

        .user-role {
            font-size: 11px;
            padding: 3px 12px;
            border-radius: 30px;
            font-weight: bold
        }

        .role-admin {
            background: #e0e7ff;
            color: #4338ca
        }

        .role-teacher {
            background: #e0e7ff;
            color: #4338ca
        }

        .role-student {
            background: #dcfce7;
            color: #166534
        }

        .error-message {
            background: #fef2f2;
            border-right: 4px solid #ef4444;
            padding: 12px;
            border-radius: 20px;
            margin-bottom: 20px;
            color: #b91c1c;
            font-size: 13px
        }

        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #94a3b8
        }

        .input-filled {
            border-color: #10b981 !important;
            background: #f0fdf4 !important
        }

        @media (max-width:550px) {
            .login-card {
                padding: 30px 22px
            }

            .user-item {
                flex-wrap: wrap;
                justify-content: center
            }

            .user-info {
                justify-content: center
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon">🔐</div>
                <h2>خوش آمدید</h2>
                <p>وارد سامانه آزمون آنلاین شوید</p>
            </div>
            <?php if ($error): ?>
                <div class="error-message">⚠️
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" id="loginForm">
                <div class="input-group"><label>📱 شماره تلفن</label><input type="text" name="phone" id="phone"
                        placeholder="09123456789" required autocomplete="off"></div>
                <div class="input-group"><label>🔒 رمز عبور</label><input type="password" name="password" id="password"
                        placeholder="********" required></div>
                <button type="submit" name="login" class="btn-login">ورود به سامانه</button>
            </form>
            <div class="users-section">
                <div class="users-title">👥 برای ورود سریع کلیک کنید</div>
                <div class="users-grid">
                    <?php if ($admin_list && $admin_list->num_rows > 0): ?>
                        <?php while ($admin = $admin_list->fetch_assoc()):
                            $default_pass = $admin['plain_password'] ?? 'bardiya.1504';
                            ?>
                            <div class="user-item"
                                onclick="fillLogin('<?php echo htmlspecialchars($admin['phone']); ?>', '<?php echo addslashes($default_pass); ?>')">
                                <div class="user-info"><span class="user-icon">👨‍💼</span><span class="user-name">
                                        <?php echo htmlspecialchars($admin['name']); ?>
                                    </span><span class="user-phone">
                                        <?php echo htmlspecialchars($admin['phone']); ?>
                                    </span><span class="user-pass">رمز:
                                        <?php echo htmlspecialchars($default_pass); ?>
                                    </span><span class="user-role role-admin">مدیر</span></div>
                                <span>←</span>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <?php if ($teachers_list && $teachers_list->num_rows > 0): ?>
                        <?php while ($teacher = $teachers_list->fetch_assoc()):
                            $default_pass = $teacher['plain_password'] ?? '123456';
                            ?>
                            <div class="user-item"
                                onclick="fillLogin('<?php echo htmlspecialchars($teacher['phone']); ?>', '<?php echo addslashes($default_pass); ?>')">
                                <div class="user-info"><span class="user-icon">👨‍🏫</span><span class="user-name">
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </span><span class="user-phone">
                                        <?php echo htmlspecialchars($teacher['phone']); ?>
                                    </span><span class="user-pass">رمز:
                                        <?php echo htmlspecialchars($default_pass); ?>
                                    </span><span class="user-role role-teacher">معلم</span></div>
                                <span>←</span>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <?php if ($students_list && $students_list->num_rows > 0): ?>
                        <?php while ($student = $students_list->fetch_assoc()):
                            $default_pass = $student['plain_password'] ?? 'abcdef';
                            ?>
                            <div class="user-item"
                                onclick="fillLogin('<?php echo htmlspecialchars($student['phone']); ?>', '<?php echo addslashes($default_pass); ?>')">
                                <div class="user-info"><span class="user-icon">👨‍🎓</span><span class="user-name">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </span><span class="user-phone">
                                        <?php echo htmlspecialchars($student['phone']); ?>
                                    </span><span class="user-pass">رمز:
                                        <?php echo htmlspecialchars($default_pass); ?>
                                    </span><span class="user-role role-student">دانش‌آموز</span></div>
                                <span>←</span>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                <div class="footer-note">💡 با کلیک روی هر کاربر، شماره و رمز عبور به صورت خودکار وارد می‌شود</div>
            </div>
        </div>
    </div>
    <script>
        function fillLogin(phone, password) {
            document.getElementById('phone').value = phone;
            document.getElementById('password').value = password;
            document.getElementById('phone').classList.add('input-filled');
            document.getElementById('password').classList.add('input-filled');
            setTimeout(() => {
                document.getElementById('phone').classList.remove('input-filled');
                document.getElementById('password').classList.remove('input-filled');
            }, 1000);
        }
    </script>
</body>

</html>