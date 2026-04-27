<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/header.php';

// Detect view mode
$view_section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;

// Dashboard stats
$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$total_sections = $conn->query("SELECT COUNT(*) as c FROM sections")->fetch_assoc()['c'];
$avg = $total_sections > 0 ? round($total_students / $total_sections, 1) : 0;

$largest = $conn->query("
    SELECT s.section_name, COUNT(st.id) as cnt
    FROM sections s
    LEFT JOIN students st ON st.section_id = s.id
    GROUP BY s.id
    ORDER BY cnt DESC
    LIMIT 1
")->fetch_assoc();

$filtered_name = "";
if ($view_section_id) {
    $res = $conn->query("SELECT section_name FROM sections WHERE id = $view_section_id");
    $row = $res->fetch_assoc();
    $filtered_name = $row['section_name'] ?? 'Unknown';
}

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <span class="topbar-title"><?= $view_section_id ? "Sections / " . htmlspecialchars($filtered_name) : "Dashboard" ?></span>

        <div class="search-box">
            <input type="text" placeholder="Search..." id="genericSearch">
        </div>

        <?php if ($view_section_id): ?>
            <a href="index.php" class="btn-add" style="background: #ef4444; text-decoration: none;">&larr; Back to Sections</a>
        <?php else: ?>
            <a href="#" class="btn-add" onclick="document.getElementById('addModal').classList.add('open'); return false;">
                + Add new student
            </a>
        <?php endif; ?>
    </div>

    <div class="content">

        <?php if ($msg): ?>
            <div class="alert"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if (!$view_section_id): ?>
            <div class="page-header">
                <h2>School Sections</h2>
                <p>Overview of all academic groups and student distribution.</p>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total Students</div>
                    <div class="stat-val"><?= $total_students ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Sections</div>
                    <div class="stat-val"><?= $total_sections ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg/Section</div>
                    <div class="stat-val"><?= $avg ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Largest Section</div>
                    <div class="stat-val" style="font-size: 1.2rem;"><?= htmlspecialchars($largest['section_name'] ?? 'N/A') ?></div>
                </div>
            </div>

            <div class="sections-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 30px;">
                <?php 
                $sections_result = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM students WHERE section_id = s.id) as count FROM sections s ORDER BY section_name");
                while ($sec = $sections_result->fetch_assoc()): 
                ?>
                    <a href="index.php?section_id=<?= $sec['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="section-card-box">
                            <div class="section-card-info">
                                <span class="sec-title"><?= htmlspecialchars($sec['section_name']) ?></span>
                                <span class="sec-badge"><?= $sec['count'] ?> Students</span>
                            </div>
                            <div class="sec-arrow">View List →</div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <div class="page-header">
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <h2>Section: <?= htmlspecialchars($filtered_name) ?></h2>
                        <p>Showing detailed records for all enrolled students.</p>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Email Address</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $students = $conn->query("SELECT * FROM students WHERE section_id = $view_section_id ORDER BY last_name");
                        if ($students->num_rows === 0): 
                        ?>
                            <tr>
                                <td colspan="7" class="empty-row">No students found in this section.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($st = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="name-col">
                                            <div class="table-avatar">
                                                <?= strtoupper(substr($st['first_name'], 0, 1) . substr($st['last_name'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><code class="id-badge"><?= htmlspecialchars($st['student_id']) ?></code></td>
                                    <td><?= htmlspecialchars($st['course'] ?? 'N/A') ?></td>
                                    <td><span class="year-text">Year <?= htmlspecialchars($st['year'] ?? 'N/A') ?></span></td>
                                    <td><?= htmlspecialchars($st['gender'] ?? 'N/A') ?></td>
                                    <td class="email-text"><?= htmlspecialchars($st['email'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="/Student_System/actions/edit_students.php?id=<?= $st['id'] ?>" class="btn-edit-table">Edit</a>
                                            <a href="/Student_System/actions/delete_student.php?id=<?= $st['id'] ?>&from=index" class="btn-del-table" onclick="return confirm('Delete this student?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Section Card Grid Styling */
.section-card-box {
    background: #fff;
    border: 1px solid #e2e8f0;
    padding: 24px;
    border-radius: 12px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.section-card-box:hover {
    border-color: #3b82f6;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
.sec-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; display: block; }
.sec-badge { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; width: fit-content; }
.sec-arrow { font-size: 0.85rem; color: #3b82f6; font-weight: 600; }

/* Table Styling */
.table-container {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.clean-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}
.clean-table thead {
    background: #f8fafc;
}
.clean-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}
.clean-table td {
    padding: 14px 16px;
    vertical-align: middle;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
}
.clean-table tr:last-child td { border-bottom: none; }
.clean-table tr:hover { background-color: #f8fafc; }

/* Table UI Elements */
.name-col { display: flex; align-items: center; gap: 12px; font-weight: 500; }
.table-avatar {
    width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 700; color: #475569;
}
.id-badge { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #475569; }
.email-text { color: #3b82f6; font-size: 0.9rem; }
.year-text { color: #64748b; font-weight: 500; }
.empty-row { padding: 50px !important; text-align: center; color: #94a3b8; }

/* Action Buttons */
.action-group { display: flex; gap: 8px; justify-content: center; }
.btn-edit-table, .btn-del-table {
    padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;
    text-decoration: none; font-weight: 500; transition: 0.2s;
}
.btn-edit-table { background: #eff6ff; color: #2563eb; }
.btn-edit-table:hover { background: #dbeafe; }
.btn-del-table { background: #fef2f2; color: #dc2626; }
.btn-del-table:hover { background: #fee2e2; }
</style>

<?php include 'includes/add_modal.php'; ?>
</body>
</html>