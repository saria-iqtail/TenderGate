<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$admin_name = $_SESSION['name'];
$error = '';
$success = '';

// Get filter parameters
$tender_filter = isset($_GET['tender']) ? (int)$_GET['tender'] : 0;
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Get all applications with tender and user details
$applications = array();
$where_conditions = array();
$params = array();
$param_types = '';

$sql = "SELECT ta.*, t.title as tender_title, t.status as tender_status, 
        u.name as user_name, u.email as user_email 
        FROM tender_applications ta 
        JOIN tenders t ON ta.tender_id = t.id 
        JOIN users u ON ta.user_id = u.id 
        WHERE 1=1";

if ($tender_filter > 0) {
    $where_conditions[] = "ta.tender_id = ?";
    $params[] = $tender_filter;
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY ta.applied_at DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $applications[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Get all tenders for filter dropdown
$tenders = array();
$tender_sql = "SELECT id, title FROM tenders ORDER BY title";
if ($tender_stmt = mysqli_prepare($link, $tender_sql)) {
    mysqli_stmt_execute($tender_stmt);
    $tender_result = mysqli_stmt_get_result($tender_stmt);
    
    while ($tender_row = mysqli_fetch_array($tender_result, MYSQLI_ASSOC)) {
        $tenders[] = $tender_row;
    }
    
    mysqli_stmt_close($tender_stmt);
}

// Get statistics
$stats = array();

// Total applications
$stats['total'] = count($applications);

// Applications by tender status
$stats['open'] = 0;
$stats['closed'] = 0;
$stats['awarded'] = 0;

foreach ($applications as $app) {
    switch ($app['tender_status']) {
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
    <title>مراجعة الطلبات - TenderGate</title>
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
                            <a class="nav-link" href="dashboard.php">
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
                            <a class="nav-link active" href="applications.php">
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
                    <h1 class="h2">مراجعة الطلبات</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="fas fa-filter me-1"></i>
                                فلترة
                            </button>
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
                                <p class="card-text text-muted">عطاءات مفتوحة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h4 class="card-title"><?php echo $stats['closed']; ?></h4>
                                <p class="card-text text-muted">عطاءات مغلقة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-award fa-2x text-success mb-2"></i>
                                <h4 class="card-title"><?php echo $stats['awarded']; ?></h4>
                                <p class="card-text text-muted">تم الترسية</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="collapse mb-4" id="filterCollapse">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="tender" class="form-label">العطاء</label>
                                        <select class="form-select" id="tender" name="tender">
                                            <option value="">جميع العطاءات</option>
                                            <?php foreach ($tenders as $tender): ?>
                                                <option value="<?php echo $tender['id']; ?>" 
                                                        <?php echo $tender_filter === $tender['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tender['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="status" class="form-label">حالة العطاء</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">جميع الحالات</option>
                                            <option value="OPEN" <?php echo $status_filter === 'OPEN' ? 'selected' : ''; ?>>مفتوح</option>
                                            <option value="CLOSED" <?php echo $status_filter === 'CLOSED' ? 'selected' : ''; ?>>مغلق</option>
                                            <option value="AWARDED" <?php echo $status_filter === 'AWARDED' ? 'selected' : ''; ?>>تم الترسية</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>
                                                فلترة
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <a href="applications.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>
                                            مسح الفلاتر
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Applications Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الطلبات (<?php echo count($applications); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4>لا توجد طلبات</h4>
                                <p class="text-muted">لم يتم تقديم أي طلبات بعد</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>المتقدم</th>
                                            <th>العطاء</th>
                                            <th>تاريخ التقديم</th>
                                            <th>حالة العطاء</th>
                                            <th>الملف المرفق</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($app['user_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($app['user_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($app['tender_title']); ?></h6>
                                                        <small class="text-muted">ID: <?php echo $app['tender_id']; ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('Y-m-d', strtotime($app['applied_at'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('H:i', strtotime($app['applied_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $app['tender_status'] === 'OPEN' ? 'success' : 
                                                            ($app['tender_status'] === 'CLOSED' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php 
                                                        $status_ar = array(
                                                            'OPEN' => 'مفتوح',
                                                            'CLOSED' => 'مغلق',
                                                            'AWARDED' => 'تم الترسية'
                                                        );
                                                        echo $status_ar[$app['tender_status']];
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($app['application_file']): ?>
                                                        <a href="../uploads/applications/<?php echo htmlspecialchars($app['application_file']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file me-1"></i>
                                                            عرض الملف
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">لا يوجد ملف</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="../user/tender-details.php?id=<?php echo $app['tender_id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="عرض العطاء">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <a href="mailto:<?php echo htmlspecialchars($app['user_email']); ?>" 
                                                           class="btn btn-sm btn-outline-secondary" title="إرسال بريد إلكتروني">
                                                            <i class="fas fa-envelope"></i>
                                                        </a>
                                                        
                                                        <a href="view-applications.php?tender_id=<?php echo $app['tender_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="عرض جميع طلبات هذا العطاء">
                                                            <i class="fas fa-list"></i>
                                                        </a>
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

                <!-- Export Options -->
                <?php if (!empty($applications)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-download me-2"></i>
                                تصدير البيانات
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted">يمكنك تصدير قائمة الطلبات بصيغ مختلفة:</p>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
                                            <i class="fas fa-file-csv me-1"></i>
                                            CSV
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                            <i class="fas fa-print me-1"></i>
                                            طباعة
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-end">
                                        <small class="text-muted">
                                            آخر تحديث: <?php echo date('Y-m-d H:i'); ?>
                                        </small>
                                    </div>
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
    
    <script>
        function exportToCSV() {
            const table = document.querySelector('table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            const csvContent = rows.map(row => {
                const cells = Array.from(row.querySelectorAll('th, td'));
                return cells.map(cell => {
                    // Clean up cell content
                    let content = cell.textContent.trim();
                    content = content.replace(/\s+/g, ' '); // Replace multiple spaces with single space
                    content = content.replace(/"/g, '""'); // Escape quotes
                    return `"${content}"`; // Wrap in quotes
                }).join(',');
            }).join('\n');
            
            const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'applications_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    
    <style>
        @media print {
            .sidebar, .btn-toolbar, .card-header .btn-group, .btn-group {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</body>
</html>

