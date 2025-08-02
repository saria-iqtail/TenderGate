<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get user statistics
$stats = array();

// Count applied tenders
$sql = "SELECT COUNT(*) as count FROM tender_applications WHERE user_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stats['applied_tenders'] = $row['count'];
    mysqli_stmt_close($stmt);
}

// Count open tenders
$sql = "SELECT COUNT(*) as count FROM tenders WHERE status = 'OPEN' AND endDate >= CURDATE()";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $stats['open_tenders'] = $row['count'];
    mysqli_stmt_close($stmt);
}

// Get recent applications
$recent_applications = array();
$sql = "SELECT t.title, t.status, ta.applied_at, t.endDate 
        FROM tender_applications ta 
        JOIN tenders t ON ta.tender_id = t.id 
        WHERE ta.user_id = ? 
        ORDER BY ta.applied_at DESC 
        LIMIT 5";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $recent_applications[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get recent tenders
$recent_tenders = array();
$sql = "SELECT id, title, description, category, endDate, status 
        FROM tenders 
        WHERE status = 'OPEN' AND endDate >= CURDATE() 
        ORDER BY createdAt DESC 
        LIMIT 5";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $recent_tenders[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - TenderGate</title>
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
                        <small>مرحباً، <?php echo htmlspecialchars($user_name); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i>
                                الملف الشخصي
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tenders.php">
                                <i class="fas fa-file-contract me-2"></i>
                                العطاءات المتاحة
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-applications.php">
                                <i class="fas fa-clipboard-list me-2"></i>
                                طلباتي
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
                    <h1 class="h2">لوحة التحكم</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="tenders.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-search me-1"></i>
                                تصفح العطاءات
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">طلباتي</h6>
                                    <div class="stats-number"><?php echo $stats['applied_tenders']; ?></div>
                                </div>
                                <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
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
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">الملف الشخصي</h6>
                                    <div class="stats-number">100%</div>
                                </div>
                                <i class="fas fa-user-check fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">معدل النجاح</h6>
                                    <div class="stats-number">85%</div>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Applications -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    آخر الطلبات
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_applications)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>لم تقدم أي طلبات بعد</p>
                                        <a href="tenders.php" class="btn btn-primary">
                                            تصفح العطاءات
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_applications as $app): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h6>
                                                    <small class="tender-status status-<?php echo strtolower($app['status']); ?>">
                                                        <?php 
                                                        $status_ar = array(
                                                            'OPEN' => 'مفتوح',
                                                            'CLOSED' => 'مغلق',
                                                            'AWARDED' => 'تم الترسية'
                                                        );
                                                        echo $status_ar[$app['status']];
                                                        ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1">
                                                    <small class="text-muted">
                                                        تاريخ التقديم: <?php echo date('Y-m-d', strtotime($app['applied_at'])); ?>
                                                    </small>
                                                </p>
                                                <small class="text-muted">
                                                    ينتهي في: <?php echo date('Y-m-d', strtotime($app['endDate'])); ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="my-applications.php" class="btn btn-outline-primary">
                                            عرض جميع الطلبات
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Tenders -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-fire me-2"></i>
                                    أحدث العطاءات
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_tenders)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                                        <p>لا توجد عطاءات متاحة حالياً</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_tenders as $tender): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($tender['title']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($tender['category']); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars(substr($tender['description'], 0, 100)) . '...'; ?></p>
                                                <small class="text-muted">
                                                    ينتهي في: <?php echo date('Y-m-d', strtotime($tender['endDate'])); ?>
                                                </small>
                                                <div class="mt-2">
                                                    <a href="tender-details.php?id=<?php echo $tender['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        عرض التفاصيل
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="tenders.php" class="btn btn-outline-primary">
                                            عرض جميع العطاءات
                                        </a>
                                    </div>
                                <?php endif; ?>
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

