<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where_conditions = array();
$params = array();
$param_types = '';

$sql = "SELECT t.*, u.name as posted_by_name FROM tenders t 
        JOIN users u ON t.postedBy = u.id 
        WHERE 1=1";

if (!empty($search)) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if (!empty($category)) {
    $where_conditions[] = "t.category = ?";
    $params[] = $category;
    $param_types .= 's';
}

if (!empty($status)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status;
    $param_types .= 's';
} else {
    // Default to show only open tenders
    $where_conditions[] = "t.status = 'OPEN' AND t.endDate >= CURDATE()";
}

if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY t.createdAt DESC";

$tenders = array();
if ($stmt = mysqli_prepare($link, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $tenders[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Get categories for filter
$categories = array();
$cat_sql = "SELECT DISTINCT category FROM tenders WHERE category IS NOT NULL AND category != ''";
if ($cat_stmt = mysqli_prepare($link, $cat_sql)) {
    mysqli_stmt_execute($cat_stmt);
    $cat_result = mysqli_stmt_get_result($cat_stmt);
    
    while ($cat_row = mysqli_fetch_array($cat_result, MYSQLI_ASSOC)) {
        $categories[] = $cat_row['category'];
    }
    
    mysqli_stmt_close($cat_stmt);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>العطاءات المتاحة - TenderGate</title>
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
                            <a class="nav-link active" href="tenders.php">
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
                    <h1 class="h2">العطاءات المتاحة</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="fas fa-filter me-1"></i>
                                فلترة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="collapse mb-4" id="filterCollapse">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">البحث</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               placeholder="ابحث في العنوان أو الوصف" 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="category" class="form-label">الفئة</label>
                                        <select class="form-select" id="category" name="category">
                                            <option value="">جميع الفئات</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                                        <?php echo $category === $cat ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="status" class="form-label">الحالة</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">العطاءات المفتوحة</option>
                                            <option value="OPEN" <?php echo $status === 'OPEN' ? 'selected' : ''; ?>>مفتوح</option>
                                            <option value="CLOSED" <?php echo $status === 'CLOSED' ? 'selected' : ''; ?>>مغلق</option>
                                            <option value="AWARDED" <?php echo $status === 'AWARDED' ? 'selected' : ''; ?>>تم الترسية</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>
                                                بحث
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <a href="tenders.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>
                                            مسح الفلاتر
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tenders List -->
                <div class="row">
                    <?php if (empty($tenders)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h4>لا توجد عطاءات</h4>
                                <p class="text-muted">لم يتم العثور على عطاءات تطابق معايير البحث</p>
                                <a href="tenders.php" class="btn btn-primary">
                                    عرض جميع العطاءات
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tenders as $tender): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card tender-card h-100" 
                                     data-category="<?php echo htmlspecialchars($tender['category']); ?>"
                                     data-status="<?php echo strtolower($tender['status']); ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title"><?php echo htmlspecialchars($tender['title']); ?></h5>
                                            <span class="badge bg-<?php 
                                                echo $tender['status'] === 'OPEN' ? 'success' : 
                                                    ($tender['status'] === 'CLOSED' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php 
                                                $status_ar = array(
                                                    'OPEN' => 'مفتوح',
                                                    'CLOSED' => 'مغلق',
                                                    'AWARDED' => 'تم الترسية'
                                                );
                                                echo $status_ar[$tender['status']];
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <p class="card-text"><?php echo htmlspecialchars(substr($tender['description'], 0, 150)) . '...'; ?></p>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">تاريخ البداية</small>
                                                <div class="fw-bold"><?php echo date('Y-m-d', strtotime($tender['startDate'])); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">تاريخ الانتهاء</small>
                                                <div class="fw-bold text-danger"><?php echo date('Y-m-d', strtotime($tender['endDate'])); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">الفئة:</small>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($tender['category']); ?></span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">منشور بواسطة:</small>
                                            <span class="fw-bold"><?php echo htmlspecialchars($tender['posted_by_name']); ?></span>
                                        </div>
                                        
                                        <?php if ($tender['fileUrl']): ?>
                                            <div class="mb-3">
                                                <a href="../uploads/tenders/<?php echo htmlspecialchars($tender['fileUrl']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-file-pdf me-1"></i>
                                                    عرض المرفق
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-grid">
                                            <a href="tender-details.php?id=<?php echo $tender['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-eye me-2"></i>
                                                عرض التفاصيل والتقديم
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer text-muted">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            منشور منذ <?php echo timeAgo($tender['createdAt']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 1) {
                return 'يوم واحد';
            } else if (diffDays < 7) {
                return diffDays + ' أيام';
            } else if (diffDays < 30) {
                const weeks = Math.floor(diffDays / 7);
                return weeks + ' أسبوع';
            } else {
                const months = Math.floor(diffDays / 30);
                return months + ' شهر';
            }
        }
        
        // Update time ago for all cards
        $('.card-footer small').each(function() {
            const text = $(this).text();
            if (text.includes('منشور منذ')) {
                // This would need the actual date to calculate properly
                // For now, we'll leave the PHP calculation
            }
        });
    </script>
</body>
</html>

