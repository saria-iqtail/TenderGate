<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get user applications
$applications = array();
$sql = "SELECT ta.*, t.title, t.description, t.status, t.endDate, t.category 
        FROM tender_applications ta 
        JOIN tenders t ON ta.tender_id = t.id 
        WHERE ta.user_id = ? 
        ORDER BY ta.applied_at DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $applications[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Get statistics
$stats = array();

// Total applications
$stats['total'] = count($applications);

// Applications by status
$stats['open'] = 0;
$stats['closed'] = 0;
$stats['awarded'] = 0;

foreach ($applications as $app) {
    switch ($app['status']) {
        case 'OPEN':
            $stats['open']++;
            break;
        case 'CLOSED':
            $stats['closed']++;
            break;
        case 'AWARDED':
            $stats['awarded']++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلباتي - TenderGate</title>
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
                            <a class="nav-link" href="dashboard.php">
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
                            <a class="nav-link active" href="my-applications.php">
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
                    <h1 class="h2">طلباتي</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="tenders.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-plus me-1"></i>
                                تصفح العطاءات
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                                <h4 class="card-title"><?php echo $stats['total']; ?></h4>
                                <p class="card-text text-muted">إجمالي الطلبات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h4 class="card-title"><?php echo $stats['open']; ?></h4>
                                <p class="card-text text-muted">قيد المراجعة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h4 class="card-title"><?php echo $stats['awarded']; ?></h4>
                                <p class="card-text text-muted">مقبولة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h4 class="card-title"><?php echo $stats['closed']; ?></h4>
                                <p class="card-text text-muted">مرفوضة</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            جميع الطلبات
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4>لا توجد طلبات</h4>
                                <p class="text-muted">لم تقدم أي طلبات بعد</p>
                                <a href="tenders.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>
                                    تصفح العطاءات
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>العطاء</th>
                                            <th>الفئة</th>
                                            <th>تاريخ التقديم</th>
                                            <th>تاريخ الانتهاء</th>
                                            <th>الحالة</th>
                                            <th>الملف</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($app['title']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars(substr($app['description'], 0, 50)) . '...'; ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($app['category']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('Y-m-d', strtotime($app['applied_at'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('H:i', strtotime($app['applied_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo date('Y-m-d', strtotime($app['endDate'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php
                                                        $days_left = ceil((strtotime($app['endDate']) - time()) / (60 * 60 * 24));
                                                        if ($days_left > 0) {
                                                            echo "باقي $days_left يوم";
                                                        } else {
                                                            echo "انتهى";
                                                        }
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $app['status'] === 'OPEN' ? 'warning' : 
                                                            ($app['status'] === 'CLOSED' ? 'danger' : 'success'); 
                                                    ?>">
                                                        <?php 
                                                        $status_ar = array(
                                                            'OPEN' => 'قيد المراجعة',
                                                            'CLOSED' => 'مرفوض',
                                                            'AWARDED' => 'مقبول'
                                                        );
                                                        echo $status_ar[$app['status']];
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($app['application_file']): ?>
                                                        <a href="../uploads/applications/<?php echo htmlspecialchars($app['application_file']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file me-1"></i>
                                                            عرض
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">لا يوجد</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="tender-details.php?id=<?php echo $app['tender_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($app['status'] === 'AWARDED'): ?>
                                                            <button class="btn btn-sm btn-success" title="تم قبول طلبك">
                                                                <i class="fas fa-trophy"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Success Rate Chart -->
                <?php if (!empty($applications)): ?>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        توزيع الطلبات حسب الحالة
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h4 class="text-warning"><?php echo $stats['open']; ?></h4>
                                                <small class="text-muted">قيد المراجعة</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h4 class="text-success"><?php echo $stats['awarded']; ?></h4>
                                                <small class="text-muted">مقبولة</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h4 class="text-danger"><?php echo $stats['closed']; ?></h4>
                                                <small class="text-muted">مرفوضة</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        معدل النجاح
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <h2 class="text-primary">
                                            <?php 
                                            $success_rate = $stats['total'] > 0 ? round(($stats['awarded'] / $stats['total']) * 100) : 0;
                                            echo $success_rate; 
                                            ?>%
                                        </h2>
                                        <p class="text-muted">معدل قبول الطلبات</p>
                                    </div>
                                    
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $success_rate; ?>%" 
                                             aria-valuenow="<?php echo $success_rate; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    
                                    <small class="text-muted mt-2 d-block">
                                        <?php echo $stats['awarded']; ?> من أصل <?php echo $stats['total']; ?> طلب
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>

