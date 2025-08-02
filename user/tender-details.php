<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$error = '';
$success = '';

// Get tender ID
$tender_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tender_id <= 0) {
    header("Location: tenders.php");
    exit();
}

// Get tender details
$sql = "SELECT t.*, u.name as posted_by_name FROM tenders t 
        JOIN users u ON t.postedBy = u.id 
        WHERE t.id = ?";
$tender = null;
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $tender_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $tender = mysqli_fetch_array($result, MYSQLI_ASSOC);
    } else {
        header("Location: tenders.php");
        exit();
    }
    
    mysqli_stmt_close($stmt);
}

// Check if user already applied
$already_applied = false;
$application = null;
$check_sql = "SELECT * FROM tender_applications WHERE tender_id = ? AND user_id = ?";
if ($check_stmt = mysqli_prepare($link, $check_sql)) {
    mysqli_stmt_bind_param($check_stmt, "ii", $tender_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $already_applied = true;
        $application = mysqli_fetch_array($check_result, MYSQLI_ASSOC);
    }
    
    mysqli_stmt_close($check_stmt);
}

// Handle application submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$already_applied) {
    if ($tender['status'] !== 'OPEN' || strtotime($tender['endDate']) < time()) {
        $error = "هذا العطاء غير متاح للتقديم";
    } else {
        $application_file = null;
        
        // Handle file upload
        if (isset($_FILES['application_file']) && $_FILES['application_file']['error'] == 0) {
            $upload_dir = '../uploads/applications/';
            $file_extension = pathinfo($_FILES['application_file']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = array('pdf', 'doc', 'docx');
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $file_name = $user_id . '_' . $tender_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['application_file']['tmp_name'], $file_path)) {
                    $application_file = $file_name;
                } else {
                    $error = "حدث خطأ أثناء رفع الملف";
                }
            } else {
                $error = "نوع الملف غير مدعوم. يرجى رفع ملف PDF أو Word";
            }
        }
        
        if (empty($error)) {
            // Insert application
            $insert_sql = "INSERT INTO tender_applications (tender_id, user_id, application_file) VALUES (?, ?, ?)";
            if ($insert_stmt = mysqli_prepare($link, $insert_sql)) {
                mysqli_stmt_bind_param($insert_stmt, "iis", $tender_id, $user_id, $application_file);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success = "تم تقديم طلبك بنجاح!";
                    $already_applied = true;
                    
                    // Send notification email to user
                    $subject = "تأكيد تقديم الطلب - TenderGate";
                    $message = "
                        <h2>تم تقديم طلبك بنجاح</h2>
                        <p>مرحباً {$_SESSION['name']}</p>
                        <p>تم تقديم طلبك للعطاء: {$tender['title']}</p>
                        <p>سيتم مراجعة طلبك وإشعارك بالنتيجة قريباً.</p>
                        <p>مع تحيات فريق TenderGate</p>
                    ";
                    sendEmail($_SESSION['email'], $subject, $message);
                    
                    // Send notification to admin
                    $admin_sql = "SELECT email FROM users WHERE role = 'admin' LIMIT 1";
                    if ($admin_stmt = mysqli_prepare($link, $admin_sql)) {
                        mysqli_stmt_execute($admin_stmt);
                        $admin_result = mysqli_stmt_get_result($admin_stmt);
                        
                        if ($admin_row = mysqli_fetch_array($admin_result, MYSQLI_ASSOC)) {
                            $admin_subject = "طلب جديد للعطاء - TenderGate";
                            $admin_message = "
                                <h2>طلب جديد للعطاء</h2>
                                <p>تم تقديم طلب جديد للعطاء: {$tender['title']}</p>
                                <p>المتقدم: {$_SESSION['name']}</p>
                                <p>البريد الإلكتروني: {$_SESSION['email']}</p>
                                <p>يرجى مراجعة الطلب في لوحة التحكم.</p>
                            ";
                            sendEmail($admin_row['email'], $admin_subject, $admin_message);
                        }
                        
                        mysqli_stmt_close($admin_stmt);
                    }
                    
                } else {
                    $error = "حدث خطأ أثناء تقديم الطلب";
                }
                
                mysqli_stmt_close($insert_stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tender['title']); ?> - TenderGate</title>
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
                    <h1 class="h2">تفاصيل العطاء</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="tenders.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-right me-1"></i>
                                العودة للعطاءات
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

                <div class="row">
                    <!-- Tender Details -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0"><?php echo htmlspecialchars($tender['title']); ?></h4>
                                    <span class="badge bg-<?php 
                                        echo $tender['status'] === 'OPEN' ? 'success' : 
                                            ($tender['status'] === 'CLOSED' ? 'danger' : 'warning'); 
                                    ?> fs-6">
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
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">تاريخ البداية</h6>
                                        <p class="fw-bold"><?php echo date('Y-m-d', strtotime($tender['startDate'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">تاريخ الانتهاء</h6>
                                        <p class="fw-bold text-danger"><?php echo date('Y-m-d', strtotime($tender['endDate'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">الفئة</h6>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($tender['category']); ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">منشور بواسطة</h6>
                                        <p class="fw-bold"><?php echo htmlspecialchars($tender['posted_by_name']); ?></p>
                                    </div>
                                </div>
                                
                                <h6 class="text-muted">الوصف</h6>
                                <div class="mb-4">
                                    <?php echo nl2br(htmlspecialchars($tender['description'])); ?>
                                </div>
                                
                                <?php if ($tender['fileUrl']): ?>
                                    <h6 class="text-muted">المرفقات</h6>
                                    <div class="mb-4">
                                        <a href="../uploads/tenders/<?php echo htmlspecialchars($tender['fileUrl']); ?>" 
                                           target="_blank" class="btn btn-outline-info">
                                            <i class="fas fa-file-pdf me-2"></i>
                                            عرض ملف العطاء
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        تم النشر في: <?php echo date('Y-m-d H:i', strtotime($tender['createdAt'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Form -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    تقديم الطلب
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($already_applied): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        لقد قمت بتقديم طلب لهذا العطاء بالفعل
                                    </div>
                                    
                                    <?php if ($application && $application['application_file']): ?>
                                        <div class="mb-3">
                                            <h6>الملف المرفق:</h6>
                                            <a href="../uploads/applications/<?php echo htmlspecialchars($application['application_file']); ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file me-1"></i>
                                                عرض الملف
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-muted">
                                        <small>
                                            تاريخ التقديم: <?php echo date('Y-m-d H:i', strtotime($application['applied_at'])); ?>
                                        </small>
                                    </div>
                                    
                                <?php elseif ($tender['status'] !== 'OPEN' || strtotime($tender['endDate']) < time()): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        هذا العطاء غير متاح للتقديم
                                    </div>
                                    
                                <?php else: ?>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $tender_id; ?>" 
                                          enctype="multipart/form-data">
                                        
                                        <div class="mb-3">
                                            <label for="application_file" class="form-label">
                                                رفع ملف الطلب <span class="text-muted">(اختياري)</span>
                                            </label>
                                            <div class="file-upload-area">
                                                <input type="file" class="form-control" id="application_file" 
                                                       name="application_file" accept=".pdf,.doc,.docx">
                                                <div class="text-center mt-2">
                                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">اسحب الملف هنا أو اضغط للاختيار</p>
                                                    <small class="text-muted">PDF, DOC, DOCX (حد أقصى 10MB)</small>
                                                </div>
                                                <div class="file-name mt-2 text-center"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle me-1"></i>
                                                بتقديم هذا الطلب، أنت توافق على الشروط والأحكام
                                            </small>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>
                                                تقديم الطلب
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Tender Statistics -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    إحصائيات العطاء
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="mb-3">
                                        <h4 class="text-primary">
                                            <?php
                                            $count_sql = "SELECT COUNT(*) as count FROM tender_applications WHERE tender_id = ?";
                                            if ($count_stmt = mysqli_prepare($link, $count_sql)) {
                                                mysqli_stmt_bind_param($count_stmt, "i", $tender_id);
                                                mysqli_stmt_execute($count_stmt);
                                                $count_result = mysqli_stmt_get_result($count_stmt);
                                                $count_row = mysqli_fetch_array($count_result, MYSQLI_ASSOC);
                                                echo $count_row['count'];
                                                mysqli_stmt_close($count_stmt);
                                            }
                                            ?>
                                        </h4>
                                        <p class="text-muted mb-0">عدد المتقدمين</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5 class="text-warning">
                                            <?php
                                            $days_left = ceil((strtotime($tender['endDate']) - time()) / (60 * 60 * 24));
                                            echo max(0, $days_left);
                                            ?>
                                        </h5>
                                        <p class="text-muted mb-0">يوم متبقي</p>
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

