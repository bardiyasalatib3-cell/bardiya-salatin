<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$page_title = 'مدیریت دانش‌آموزان';
$active_page = 'manage_students';

require_once 'config.php';

$message = '';
$error = '';

// حذف دانش‌آموز
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $check = $conn->query("SELECT role FROM users WHERE id = $id");
    if ($check && $check->fetch_assoc()['role'] == 'student') {
        $conn->query("DELETE FROM users WHERE id = $id");
        $message = "✅ دانش‌آموز با موفقیت حذف شد.";
    } else {
        $error = "❌ نمی‌توانید معلم را حذف کنید.";
    }
    header("Location: manage_students.php?msg=" . urlencode($message) . "&error=" . urlencode($error));
    exit();
}

// افزودن دانش‌آموز جدید
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    if (empty($name) || empty($phone) || empty($password)) {
        $error = "❌ لطفاً تمام فیلدها را پر کنید.";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE phone = '$phone'");
        if ($check && $check->num_rows > 0) {
            $error = "❌ این شماره تلفن قبلاً ثبت شده است.";
        } else {
            $hashed_pass = md5($password);
            $role = 'student';
            $stmt = $conn->prepare("INSERT INTO users (name, phone, password, plain_password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $phone, $hashed_pass, $password, $role);
            if ($stmt->execute()) {
                $message = "✅ دانش‌آموز «{$name}» با موفقیت اضافه شد.";
            } else {
                $error = "❌ خطا در ثبت دانش‌آموز: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header("Location: manage_students.php?msg=" . urlencode($message) . "&error=" . urlencode($error));
    exit();
}

// ویرایش دانش‌آموز
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $id = (int) $_POST['id'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    if (empty($name) || empty($phone)) {
        $error = "❌ نام و شماره تلفن الزامی است.";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE phone = '$phone' AND id != $id");
        if ($check && $check->num_rows > 0) {
            $error = "❌ این شماره تلفن قبلاً ثبت شده است.";
        } else {
            if (!empty($password)) {
                $hashed_pass = md5($password);
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, password = ?, plain_password = ? WHERE id = ? AND role = 'student'");
                $stmt->bind_param("ssssi", $name, $phone, $hashed_pass, $password, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ? AND role = 'student'");
                $stmt->bind_param("ssi", $name, $phone, $id);
            }
            if ($stmt->execute()) {
                $message = "✅ اطلاعات دانش‌آموز با موفقیت ویرایش شد.";
            } else {
                $error = "❌ خطا در ویرایش: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header("Location: manage_students.php?msg=" . urlencode($message) . "&error=" . urlencode($error));
    exit();
}

$students = $conn->query("
    SELECT u.id, u.name, u.phone, u.plain_password,
    COUNT(er.id) as exam_count,
    AVG(er.score) as avg_score,
    (SELECT score FROM exam_results WHERE user_phone = u.phone ORDER BY exam_date DESC LIMIT 1) as last_score,
    (SELECT exam_date FROM exam_results WHERE user_phone = u.phone ORDER BY exam_date DESC LIMIT 1) as last_exam_date
    FROM users u
    LEFT JOIN exam_results er ON u.phone = er.user_phone
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.id DESC
");

$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';
if ($msg)
    $message = $msg;
if ($err)
    $error = $err;

include 'header.php';
?>

<style>
    .list-card {
        background: #fff;
        border-radius: 28px;
        padding: 25px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        margin: 0 auto;
        overflow-x: auto
    }

    .form-title {
        font-size: 22px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #667eea;
        display: inline-block
    }

    .search-box {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap
    }

    .search-input {
        flex: 1;
        padding: 10px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 60px;
        font-size: 14px
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea
    }

    .students-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px
    }

    .students-table th,
    .students-table td {
        padding: 12px 10px;
        text-align: right;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle
    }

    .students-table th {
        background: #f1f5f9;
        font-weight: 600;
        font-size: 13px
    }

    .students-table tr {
        cursor: pointer;
        transition: 0.2s
    }

    .students-table tr:hover {
        background: #f8fafc
    }

    .password-cell {
        font-family: monospace;
        background: #f1f5f9;
        padding: 3px 8px;
        border-radius: 20px;
        display: inline-block;
        font-size: 11px
    }

    .score-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 12px
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

    .score-none {
        background: #f1f5f9;
        color: #64748b
    }

    .exam-count-badge {
        background: #e0e7ff;
        color: #4338ca;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block
    }

    .empty-row td {
        text-align: center;
        padding: 40px;
        color: #64748b
    }

    .add-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        border: none;
        border-radius: 60px;
        padding: 10px 22px;
        font-weight: bold;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s;
        display: inline-block
    }

    .add-btn:hover {
        transform: translateY(-2px)
    }

    .message {
        padding: 12px;
        border-radius: 20px;
        margin-bottom: 20px;
        text-align: center
    }

    .message-success {
        background: #d1fae5;
        color: #065f46
    }

    .message-error {
        background: #fee2e2;
        color: #991b1b
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        justify-content: center;
        align-items: center
    }

    .modal.active {
        display: flex
    }

    .modal-content {
        background: #fff;
        border-radius: 32px;
        padding: 30px;
        width: 90%;
        max-width: 550px;
        animation: modalFadeIn 0.3s ease;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        max-height: 90vh;
        overflow-y: auto
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-30px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0
    }

    .modal-header h3 {
        font-size: 20px;
        color: #1e293b
    }

    .modal-close {
        font-size: 24px;
        cursor: pointer;
        color: #94a3b8;
        transition: 0.2s
    }

    .modal-close:hover {
        color: #ef4444
    }

    .input-group {
        margin-bottom: 15px
    }

    .input-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        font-size: 13px;
        color: #334155
    }

    .input-group input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 60px;
        font-size: 14px
    }

    .modal-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px
    }

    .modal-btn {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 60px;
        font-weight: bold;
        cursor: pointer
    }

    .modal-btn-save {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff
    }

    .modal-btn-delete {
        background: #ef4444;
        color: #fff
    }

    .modal-btn-cancel {
        background: #e2e8f0;
        color: #334155
    }

    .student-info {
        background: #f1f5f9;
        border-radius: 20px;
        padding: 15px;
        margin-bottom: 15px
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px
    }

    .info-row:last-child {
        border-bottom: none
    }

    .student-scores {
        background: #f8fafc;
        border-radius: 20px;
        padding: 12px;
        margin-top: 15px;
        margin-bottom: 15px;
        max-height: 300px;
        overflow-y: auto
    }

    .student-scores h4 {
        font-size: 14px;
        margin-bottom: 10px;
        border-right: 3px solid #667eea;
        padding-right: 8px
    }

    .score-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12px
    }

    .average-box {
        background: #e0e7ff;
        border-radius: 15px;
        padding: 10px;
        margin-top: 10px;
        text-align: center
    }

    .no-exam {
        color: #94a3b8;
        text-align: center;
        padding: 15px
    }

    hr {
        margin: 10px 0
    }
</style>

<div class="list-card">
    <div
        style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:20px;">
        <div class="form-title">📋 لیست دانش‌آموزان</div>
        <button class="add-btn" onclick="openAddModal()">➕ افزودن دانش‌آموز جدید</button>
    </div>
    <?php if ($message): ?>
        <div class="message message-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?>
        <div class="message message-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <div class="search-box">
        <input type="text" id="searchInput" class="search-input" placeholder="🔍 جستجو بر اساس نام یا شماره تلفن..."
            onkeyup="searchStudents()">
        <button class="add-btn" style="background:#64748b;" onclick="clearSearch()">✖️ پاک کردن</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="students-table" id="studentsTable">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>شماره</th>
                    <th>رمز</th>
                    <th>تعداد آزمون</th>
                    <th>میانگین</th>
                    <th>آخرین نمره</th>
                    <th>تاریخ آخرین آزمون</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <?php if ($students && $students->num_rows > 0):
                    while ($row = $students->fetch_assoc()):
                        $avg_score = $row['avg_score'] ? round($row['avg_score'] / 5, 1) : 0;
                        $last_score = $row['last_score'] ? round($row['last_score'] / 5, 1) : 0;
                        $avg_class = $avg_score >= 14 ? 'score-high' : ($avg_score >= 10 ? 'score-medium' : ($avg_score > 0 ? 'score-low' : 'score-none'));
                        $last_class = $last_score >= 14 ? 'score-high' : ($last_score >= 10 ? 'score-medium' : ($last_score > 0 ? 'score-low' : 'score-none')); ?>
                        <tr data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>"
                            data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                            data-password="<?php echo htmlspecialchars($row['plain_password'] ?? ''); ?>"
                            data-avg="<?php echo $avg_score; ?>" data-exam-count="<?php echo $row['exam_count'] ?? 0; ?>">
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><span
                                    class="password-cell"><?php echo htmlspecialchars($row['plain_password'] ?? 'ندارد'); ?></span>
                            </td>
                            <td><span class="exam-count-badge"><?php echo $row['exam_count'] ?? 0; ?> بار</span></td>
                            <td><?php if ($row['exam_count'] > 0): ?><span
                                        class="score-badge <?php echo $avg_class; ?>"><?php echo $avg_score; ?></span><?php else: ?><span
                                        class="score-badge score-none">—</span><?php endif; ?></td>
                            <td><?php if ($row['last_score'] !== null): ?><span
                                        class="score-badge <?php echo $last_class; ?>"><?php echo $last_score; ?></span><?php else: ?><span
                                        class="score-badge score-none">—</span><?php endif; ?></td>
                            <td><?php if ($row['last_exam_date']): ?><?php echo date('Y/m/d', strtotime($row['last_exam_date'])); ?><?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                    <tr class="empty-row">
                        <td colspan="7">📭 هنوز دانش‌آموزی اضافه نشده است</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ افزودن دانش‌آموز جدید</h3><span class="modal-close" onclick="closeAddModal()">&times;</span>
        </div>
        <form method="POST" action="manage_students.php">
            <div class="input-group"><label>👤 نام و نام خانوادگی</label><input type="text" name="name" required
                    autocomplete="off"></div>
            <div class="input-group"><label>📱 شماره تلفن</label><input type="text" name="phone" required
                    autocomplete="off"></div>
            <div class="input-group"><label>🔒 رمز عبور</label><input type="text" name="password" required></div>
            <div class="modal-buttons"><button type="submit" name="add_student" class="modal-btn modal-btn-save">✅
                    ذخیره</button><button type="button" class="modal-btn modal-btn-cancel"
                    onclick="closeAddModal()">انصراف</button></div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ ویرایش دانش‌آموز</h3><span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST" action="manage_students.php" id="editForm"><input type="hidden" name="id" id="edit_id">
            <div class="input-group"><label>👤 نام و نام خانوادگی</label><input type="text" name="name" id="edit_name"
                    required></div>
            <div class="input-group"><label>📱 شماره تلفن</label><input type="text" name="phone" id="edit_phone"
                    required></div>
            <div class="input-group"><label>🔒 رمز عبور <span style="font-size:11px;color:#64748b;">(در صورت تمایل به
                        تغییر وارد کنید)</span></label><input type="text" name="password" id="edit_password"
                    placeholder="رمز جدید"></div>
            <div class="student-info" id="studentStats">
                <div class="info-row"><span>📊 تعداد آزمون‌ها:</span><span id="stat_exam_count">0</span></div>
            </div>
            <div class="student-scores" id="studentScores">
                <h4>📊 سوابق تحصیلی</h4>
                <div id="scoresContent">
                    <div class="no-exam">در حال بارگذاری...</div>
                </div>
            </div>
            <div class="modal-buttons"><button type="submit" name="edit_student" class="modal-btn modal-btn-save">💾
                    ذخیره تغییرات</button><button type="button" class="modal-btn modal-btn-delete"
                    onclick="deleteFromModal()">🗑️ حذف دانش‌آموز</button><button type="button"
                    class="modal-btn modal-btn-cancel" onclick="closeEditModal()">انصراف</button></div>
        </form>
    </div>
</div>

<script>
    function searchStudents() { var i = document.getElementById('searchInput'), f = i.value.toLowerCase(), t = document.getElementById('studentsTableBody'), r = t.getElementsByTagName('tr'); for (var j = 0; j < r.length; j++) { var n = r[j].getElementsByTagName('td')[0], p = r[j].getElementsByTagName('td')[1]; if (n || p) { var nv = n ? n.textContent || n.innerText : '', pv = p ? p.textContent || p.innerText : ''; if (nv.toLowerCase().indexOf(f) > -1 || pv.toLowerCase().indexOf(f) > -1) { r[j].style.display = '' } else { r[j].style.display = 'none' } } } }
    function clearSearch() { document.getElementById('searchInput').value = ''; searchStudents() }
    function openAddModal() { document.getElementById('addModal').classList.add('active') }
    function closeAddModal() { document.getElementById('addModal').classList.remove('active'); document.querySelector('#addModal form').reset() }
    function openEditModal(id, name, phone, password, avgScore, examCount) { currentStudentId = id; currentStudentName = name; document.getElementById('edit_id').value = id; document.getElementById('edit_name').value = name; document.getElementById('edit_phone').value = phone; document.getElementById('edit_password').value = ''; document.getElementById('stat_exam_count').innerHTML = examCount; document.getElementById('scoresContent').innerHTML = '<div class="no-exam">در حال بارگذاری...</div>'; fetch('get_student_scores.php?id=' + id).then(r => r.json()).then(d => { if (d.error) { document.getElementById('scoresContent').innerHTML = '<div class="no-exam">' + d.error + '</div>'; return } if (d.scores && d.scores.length > 0) { var h = '', t = 0; d.scores.forEach(function (s) { var c = ''; if (s.score >= 14) c = 'score-high'; else if (s.score >= 10) c = 'score-medium'; else c = 'score-low'; h += '<div class="score-row"><span>📝 نمره:</span><span><span class="score-badge ' + c + '">' + s.score + '</span></span></div><div class="score-row"><span>📅 تاریخ:</span><span>' + s.date + '</span></div><hr>'; t += s.score }); var a = (t / d.scores.length).toFixed(1), ac = ''; if (a >= 14) ac = 'score-high'; else if (a >= 10) ac = 'score-medium'; else ac = 'score-low'; h += '<div class="average-box"><span>📊 میانگین کل نمرات:</span><span class="score-badge ' + ac + '">' + a + '</span></div>'; document.getElementById('scoresContent').innerHTML = h } else { document.getElementById('scoresContent').innerHTML = '<div class="no-exam">📭 این دانش‌آموز هنوز در هیچ آزمونی شرکت نکرده است.</div>' } }).catch(function (e) { document.getElementById('scoresContent').innerHTML = '<div class="no-exam">خطا در دریافت اطلاعات</div>' }); document.getElementById('editModal').classList.add('active') }
    function closeEditModal() { document.getElementById('editModal').classList.remove('active') }
    function deleteFromModal() { if (confirm('آیا از حذف دانش‌آموز "' + currentStudentName + '" اطمینان دارید؟')) { window.location.href = '?delete=' + currentStudentId } }
    document.querySelectorAll('#studentsTableBody tr').forEach(function (r) { if (r.classList.contains('empty-row')) return; r.addEventListener('click', function (e) { var id = this.dataset.id, name = this.dataset.name, phone = this.dataset.phone, password = this.dataset.password, avgScore = this.dataset.avg, examCount = this.dataset.examCount; openEditModal(id, name, phone, password, avgScore, examCount) }) });
    window.onclick = function (e) { var am = document.getElementById('addModal'), em = document.getElementById('editModal'); if (e.target === am) closeAddModal(); if (e.target === em) closeEditModal() }
</script>
<?php include 'footer.php'; ?>