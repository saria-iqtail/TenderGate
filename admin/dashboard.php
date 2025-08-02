<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$admin_name = $_SESSION['name'];

// Get statistics
$stats = array();

// Total users
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stats['total_users'] = $row['count'];
    mysqli_stmt_close($stmt);
}

// Total tenders
$sql = "SELECT COUNT(*) as count FROM tenders";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stats['total_tenders'] = $row['count'];
    mysqli_stmt_close($stmt);
}

// Total applications
$sql = "SELECT COUNT(*) as count FROM tender_applications";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stats['total_applications'] = $row['count'];
    mysqli_stmt_close($stmt);
}

// Open tenders
$sql = "SELECT COUNT(*) as count FROM tenders WHERE status = 'OPEN'";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stats['open_tenders'] = $row['count'];
    mysqli_stmt_close($stmt);
}

// Recent users
$recent_users = array();
$sql = "SELECT name, email, createdAt FROM users WHERE role = 'user' ORDER BY createdAt DESC LIMIT 5";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $recent_users[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Recent applications
$recent_applications = array();
$sql = "SELECT ta.applied_at, u.name as user_name, t.title as tender_title 
        FROM tender_applications ta 
        JOIN users u ON ta.user_id = u.id 
        JOIN tenders t ON ta.tender_id = t.id 
        ORDER BY ta.applied_at DESC 
        LIMIT 5";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $recent_applications[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Tenders by status
$tender_stats = array();
$sql = "SELECT status, COUNT(*) as count FROM tenders GROUP BY status";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $tender_stats[$row['status']] = $row['count'];
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الإدارة - TenderGate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-gavel fa-2x mb-2"></i>
                        <h5>TenderGate</h5>
                        <small>مرحباً، <?php echo htmlspecialchars($admin_name); ?></small>
                        <div class="badge bg-warning mt-1">مدير</div>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>
                                إدارة المستخدمين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tenders.php">
                                <i class="fas fa-file-contract me-2"></i>
                                إدارة العطاءات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="applications.php">
                                <i class="fas fa-clipboard-list me-2"></i>
                                مراجعة الطلبات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../public/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                تسجيل الخروج
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">لوحة تحكم الإدارة</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add-tender.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                إضافة عطاء جديد
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">المستخدمين</h6>
                                    <div class="stats-number"><?php echo $stats['total_users']; ?></div>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">العطاءات</h6>
                                    <div class="stats-number"><?php echo $stats['total_tenders']; ?></div>
                                </div>
                                <i class="fas fa-file-contract fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">الطلبات</h6>
                                    <div class="stats-number"><?php echo $stats['total_applications']; ?></div>
                                </div>
                                <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">العطاءات المفتوحة</h6>
                                    <div class="stats-number"><?php echo $stats['open_tenders']; ?></div>
                                </div>
                                <i class="fas fa-folder-open fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tender Statistics -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    إحصائيات العطاءات
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-success"><?php echo isset($tender_stats['OPEN']) ? $tender_stats['OPEN'] : 0; ?></h4>
                                            <small class="text-muted">مفتوحة</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-danger"><?php echo isset($tender_stats['CLOSED']) ? $tender_stats['CLOSED'] : 0; ?></h4>
                                            <small class="text-muted">مغلقة</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3">
                                            <h4 class="text-warning"><?php echo isset($tender_stats['AWARDED']) ? $tender_stats['AWARDED'] : 0; ?></h4>
                                            <small class="text-muted">تم الترسية</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>
                                    إجراءات سريعة
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="add-tender.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>
                                        إضافة عطاء جديد
                                    </a>
                                    <a href="users.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus me-2"></i>
                                        إضافة مستخدم جديد
                                    </a>
                                    <a href="applications.php" class="btn btn-outline-warning">
                                        <i class="fas fa-eye me-2"></i>
                                        مراجعة الطلبات
                                    </a>
                                    <a href="tenders.php" class="btn btn-outline-info">
                                        <i class="fas fa-cog me-2"></i>
                                        إدارة العطاءات
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Recent Users -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-clock me-2"></i>
                                    المستخدمون الجدد
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_users)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <p>لا يوجد مستخدمون جدد</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_users as $user): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                    <small><?php echo date('Y-m-d', strtotime($user['createdAt'])); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="users.php" class="btn btn-outline-primary btn-sm">
                                            عرض جميع المستخدمين
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Applications -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clipboard-check me-2"></i>
                                    أحدث الطلبات
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_applications)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                        <p>لا توجد طلبات جديدة</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_applications as $app): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($app['user_name']); ?></h6>
                                                    <small><?php echo date('Y-m-d', strtotime($app['applied_at'])); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars($app['tender_title']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="applications.php" class="btn btn-outline-primary btn-sm">
                                            عرض جميع الطلبات
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    معلومات النظام
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <h6 class="text-muted">إصدار النظام</h6>
                                        <p>TenderGate v1.0</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted">آخر تحديث</h6>
                                        <p><?php echo date('Y-m-d'); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted">حالة النظام</h6>
                                        <span class="badge bg-success">يعمل بشكل طبيعي</span>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted">قاعدة البيانات</h6>
                                        <span class="badge bg-success">متصلة</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>

