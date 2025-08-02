<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$admin_name = $_SESSION['name'];
$admin_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle tender actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_tender'])) {
        $tender_id = (int)$_POST['tender_id'];
        
        $delete_sql = "DELETE FROM tenders WHERE id = ?";
        if ($delete_stmt = mysqli_prepare($link, $delete_sql)) {
            mysqli_stmt_bind_param($delete_stmt, "i", $tender_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $success = "تم حذف العطاء بنجاح";
            } else {
                $error = "حدث خطأ أثناء حذف العطاء";
            }
            
            mysqli_stmt_close($delete_stmt);
        }
    } elseif (isset($_POST['update_status'])) {
        $tender_id = (int)$_POST['tender_id'];
        $new_status = sanitizeInput($_POST['new_status']);
        
        $update_sql = "UPDATE tenders SET status = ? WHERE id = ?";
        if ($update_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $tender_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = "تم تحديث حالة العطاء بنجاح";
                
                // Send notification emails to applicants
                $applicants_sql = "SELECT u.email, u.name FROM users u 
                                   JOIN tender_applications ta ON u.id = ta.user_id 
                                   WHERE ta.tender_id = ?";
                if ($applicants_stmt = mysqli_prepare($link, $applicants_sql)) {
                    mysqli_stmt_bind_param($applicants_stmt, "i", $tender_id);
                    mysqli_stmt_execute($applicants_stmt);
                    $applicants_result = mysqli_stmt_get_result($applicants_stmt);
                    
                    $tender_title_sql = "SELECT title FROM tenders WHERE id = ?";
                    if ($title_stmt = mysqli_prepare($link, $tender_title_sql)) {
                        mysqli_stmt_bind_param($title_stmt, "i", $tender_id);
                        mysqli_stmt_execute($title_stmt);
                        $title_result = mysqli_stmt_get_result($title_stmt);
                        $title_row = mysqli_fetch_array($title_result, MYSQLI_ASSOC);
                        $tender_title = $title_row['title'];
                        mysqli_stmt_close($title_stmt);
                    }
                    
                    while ($applicant = mysqli_fetch_array($applicants_result, MYSQLI_ASSOC)) {
                        $status_ar = array(
                            'OPEN' => 'مفتوح',
                            'CLOSED' => 'مغلق',
                            'AWARDED' => 'تم الترسية'
                        );
                        
                        $subject = "تحديث حالة العطاء - TenderGate";
                        $message = "
                            <h2>تحديث حالة العطاء</h2>
                            <p>مرحباً {$applicant['name']}</p>
                            <p>تم تحديث حالة العطاء: $tender_title</p>
                            <p>الحالة الجديدة: {$status_ar[$new_status]}</p>
                            <p>يرجى تسجيل الدخول لمراجعة التفاصيل.</p>
                            <p>مع تحيات فريق TenderGate</p>
                        ";
                        sendEmail($applicant['email'], $subject, $message);
                    }
                    
                    mysqli_stmt_close($applicants_stmt);
                }
                
            } else {
                $error = "حدث خطأ أثناء تحديث حالة العطاء";
            }
            
            mysqli_stmt_close($update_stmt);
        }
    }
}

// Get all tenders
$tenders = array();
$sql = "SELECT t.*, u.name as posted_by_name, 
        (SELECT COUNT(*) FROM tender_applications WHERE tender_id = t.id) as applications_count
        FROM tenders t 
        JOIN users u ON t.postedBy = u.id 
        ORDER BY t.createdAt DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $tenders[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة العطاءات - TenderGate</title>
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
                            <a class="nav-link active" href="tenders.php">
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
                    <h1 class="h2">إدارة العطاءات</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add-tender.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                إضافة عطاء جديد
                            </a>
                        </div>
                    </div>
                </div>

                <div id="alerts-container">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tenders Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-contract me-2"></i>
                            قائمة العطاءات (<?php echo count($tenders); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tenders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                                <h4>لا توجد عطاءات</h4>
                                <p class="text-muted">لم يتم إنشاء أي عطاءات بعد</p>
                                <a href="add-tender.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة أول عطاء
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>العطاء</th>
                                            <th>الفئة</th>
                                            <th>تاريخ البداية</th>
                                            <th>تاريخ الانتهاء</th>
                                            <th>الحالة</th>
                                            <th>عدد المتقدمين</th>
                                            <th>منشور بواسطة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tenders as $tender): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($tender['title']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars(substr($tender['description'], 0, 50)) . '...'; ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($tender['category']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($tender['startDate'])); ?></td>
                                                <td>
                                                    <?php echo date('Y-m-d', strtotime($tender['endDate'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php
                                                        $days_left = ceil((strtotime($tender['endDate']) - time()) / (60 * 60 * 24));
                                                        if ($days_left > 0) {
                                                            echo "باقي $days_left يوم";
                                                        } else {
                                                            echo "انتهى";
                                                        }
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="tender_id" value="<?php echo $tender['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm" 
                                                                onchange="this.form.submit()" style="width: auto;">
                                                            <option value="OPEN" <?php echo $tender['status'] === 'OPEN' ? 'selected' : ''; ?>>مفتوح</option>
                                                            <option value="CLOSED" <?php echo $tender['status'] === 'CLOSED' ? 'selected' : ''; ?>>مغلق</option>
                                                            <option value="AWARDED" <?php echo $tender['status'] === 'AWARDED' ? 'selected' : ''; ?>>تم الترسية</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo $tender['applications_count']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($tender['posted_by_name']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="edit-tender.php?id=<?php echo $tender['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <a href="view-applications.php?tender_id=<?php echo $tender['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="عرض الطلبات">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($tender['fileUrl']): ?>
                                                            <a href="../uploads/tenders/<?php echo htmlspecialchars($tender['fileUrl']); ?>" 
                                                               target="_blank" class="btn btn-sm btn-outline-secondary" title="عرض الملف">
                                                                <i class="fas fa-file"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا العطاء؟')">
                                                            <input type="hidden" name="tender_id" value="<?php echo $tender['id']; ?>">
                                                            <button type="submit" name="delete_tender" 
                                                                    class="btn btn-sm btn-danger" title="حذف">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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

                <!-- Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-folder-open fa-2x text-success mb-2"></i>
                                <h4 class="card-title">
                                    <?php
                                    $open_count = 0;
                                    foreach ($tenders as $tender) {
                                        if ($tender['status'] === 'OPEN') $open_count++;
                                    }
                                    echo $open_count;
                                    ?>
                                </h4>
                                <p class="card-text text-muted">عطاءات مفتوحة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-folder fa-2x text-danger mb-2"></i>
                                <h4 class="card-title">
                                    <?php
                                    $closed_count = 0;
                                    foreach ($tenders as $tender) {
                                        if ($tender['status'] === 'CLOSED') $closed_count++;
                                    }
                                    echo $closed_count;
                                    ?>
                                </h4>
                                <p class="card-text text-muted">عطاءات مغلقة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-award fa-2x text-warning mb-2"></i>
                                <h4 class="card-title">
                                    <?php
                                    $awarded_count = 0;
                                    foreach ($tenders as $tender) {
                                        if ($tender['status'] === 'AWARDED') $awarded_count++;
                                    }
                                    echo $awarded_count;
                                    ?>
                                </h4>
                                <p class="card-text text-muted">تم الترسية</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x text-info mb-2"></i>
                                <h4 class="card-title">
                                    <?php
                                    $total_applications = 0;
                                    foreach ($tenders as $tender) {
                                        $total_applications += $tender['applications_count'];
                                    }
                                    echo $total_applications;
                                    ?>
                                </h4>
                                <p class="card-text text-muted">إجمالي الطلبات</p>
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

