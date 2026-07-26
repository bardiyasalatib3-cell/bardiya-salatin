<?php
require_once 'config.php';

echo "<pre>";
echo "🗑️ شروع پاکسازی دیتابیس...\n";

$conn->query("TRUNCATE TABLE exam_results");
echo "✅ exam_results پاک شد\n";

$conn->query("TRUNCATE TABLE exam_settings");
echo "✅ exam_settings پاک شد\n";

$conn->query("TRUNCATE TABLE soal_t");
echo "✅ soal_t پاک شد\n";

$conn->query("TRUNCATE TABLE exam_attempts");
echo "✅ exam_attempts پاک شد\n";

$conn->query("DELETE FROM users WHERE role = 'student'");
echo "✅ دانش‌آموزان حذف شدند\n";

$conn->query("ALTER TABLE users AUTO_INCREMENT = 10");
echo "✅ AUTO_INCREMENT تنظیم شد\n";

$conn->query("INSERT IGNORE INTO users (id, name, phone, password, plain_password, role) VALUES 
    (7, 'علی رضایی', '09121234567', MD5('123456'), '123456', 'teacher'),
    (8, 'مدیر سیستم', '1504', MD5('bardiya.1504'), 'bardiya.1504', 'admin'),
    (9, 'رضا احمدی', '09129876543', MD5('abcdef'), 'abcdef', 'student')");
echo "✅ معلم و ادمین و دانش‌آموز نمونه تضمین شدند\n";

$conn->query("DELETE FROM site_settings");
$conn->query("INSERT INTO site_settings (site_name, logo_text, primary_color, secondary_color) VALUES 
    ('سامانه آزمون آنلاین', '📚', '#667eea', '#764ba2')");
echo "✅ تنظیمات سایت ریست شد\n";

echo "\n✅ پاکسازی با موفقیت انجام شد!\n";
echo "</pre>";
?>