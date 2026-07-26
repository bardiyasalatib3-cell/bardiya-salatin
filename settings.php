<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$page_title = 'تنظیمات سایت';
$active_page = 'settings';

require_once 'config.php';

$message = '';
$error = '';

$settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $site_name = trim($_POST['site_name']);
    $logo_text = trim($_POST['logo_text']);
    $primary_color = trim($_POST['primary_color']);
    $secondary_color = trim($_POST['secondary_color']);
    
    $stmt = $conn->prepare("UPDATE site_settings SET site_name = ?, logo_text = ?, primary_color = ?, secondary_color = ? WHERE id = 1");
    $stmt->bind_param("ssss", $site_name, $logo_text, $primary_color, $secondary_color);
    if ($stmt->execute()) {
        $message = "✅ تنظیمات با موفقیت ذخیره شد.";
        $settings = ['site_name' => $site_name, 'logo_text' => $logo_text, 'primary_color' => $primary_color, 'secondary_color' => $secondary_color];
    } else {
        $error = "❌ خطا در ذخیره تنظیمات.";
    }
    $stmt->close();
}

include 'header.php';
?>

<style>
    .settings-container{max-width:600px;margin:0 auto}
    .settings-card{background:#fff;border-radius:28px;padding:30px;box-shadow:0 10px 25px rgba(0,0,0,0.08)}
    .form-title{font-size:22px;font-weight:bold;color:#1e293b;margin-bottom:25px;padding-bottom:10px;border-bottom:3px solid #667eea;display:inline-block}
    .input-group{margin-bottom:20px}
    .input-group label{display:block;margin-bottom:8px;font-weight:600;font-size:13px;color:#334155}
    .input-group input{width:100%;padding:12px 16px;border:2px solid #e2e8f0;border-radius:60px;font-size:14px}
    .input-group input:focus{outline:none;border-color:#667eea}
    .btn-save{background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:60px;padding:12px 20px;font-weight:bold;cursor:pointer;width:100%}
    .btn-save:hover{transform:translateY(-2px)}
    .message{padding:12px;border-radius:20px;margin-bottom:20px;text-align:center}
    .message-success{background:#d1fae5;color:#065f46}
    .message-error{background:#fee2e2;color:#991b1b}
    .preview-box{background:#f8fafc;border-radius:20px;padding:15px;margin-top:20px;text-align:center}
</style>

<div class="settings-container">
    <div class="settings-card">
        <div class="form-title">⚙️ تنظیمات سایت</div>
        <?php if ($message): ?><div class="message message-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message message-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="input-group"><label>🏷️ نام سایت</label><input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required></div>
            <div class="input-group"><label>🔣 متن لوگو (ایموجی یا متن)</label><input type="text" name="logo_text" value="<?php echo htmlspecialchars($settings['logo_text']); ?>" placeholder="مثال: 📚 یا آزمون"></div>
            <div class="input-group"><label>🎨 رنگ اصلی</label><input type="color" name="primary_color" value="<?php echo $settings['primary_color']; ?>"></div>
            <div class="input-group"><label>🎨 رنگ ثانویه</label><input type="color" name="secondary_color" value="<?php echo $settings['secondary_color']; ?>"></div>
            <div class="preview-box"><strong>پیش‌نمایش:</strong><br><span style="background:<?php echo $settings['primary_color']; ?>;color:#fff;padding:4px 12px;border-radius:30px;display:inline-block;margin-top:10px;"><?php echo htmlspecialchars($settings['logo_text']); ?> <?php echo htmlspecialchars($settings['site_name']); ?></span></div>
            <button type="submit" name="save_settings" class="btn-save" style="margin-top:20px;">💾 ذخیره تنظیمات</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>