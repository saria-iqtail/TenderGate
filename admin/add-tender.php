<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$admin_name = $_SESSION['name'];
$admin_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $category = sanitizeInput($_POST['category']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $status = sanitizeInput($_POST['status']);
    
    if (empty($title) || empty($description) || empty($category) || empty($start_date) || empty($end_date)) {
        $error = "جميع الحقول مطلوبة";
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = "تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية";
    } else {
        $file_url = null;
        
        // Handle file upload
        if (isset($_FILES['tender_file']) && $_FILES['tender_file']['error'] == 0) {
            $upload_dir = '../uploads/tenders/';
            $file_extension = pathinfo($_FILES['tender_file']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = array('pdf', 'doc', 'docx');
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $file_name = 'tender_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['tender_file']['tmp_name'], $file_path)) {
                    $file_url = $file_name;
                } else {
                    $error = "حدث خطأ أثناء رفع الملف";
                }
            } else {
                $error = "نوع الملف غير مدعوم. يرجى رفع ملف PDF أو Word";
            }
        }
        
        if (empty($error)) {
            // Insert tender
            $sql = "INSERT INTO tenders (title, description, category, startDate, endDate, status, fileUrl, postedBy) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssi", $title, $description, $category, $start_date, $end_date, $status, $file_url, $admin_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "تم إضافة العطاء بنجاح";
                    
                    // Send notification emails to all users
                    $users_sql = "SELECT email, name FROM users WHERE role = 'user' AND isActive = 1";
                    if ($users_stmt = mysqli_prepare($link, $users_sql)) {
                        mysqli_stmt_execute($users_stmt);
                        $users_result = mysqli_stmt_get_result($users_stmt);
                        
                        while ($user = mysqli_fetch_array($users_result, MYSQLI_ASSOC)) {
                            $subject = "عطاء جديد متاح - TenderGate";
                            $message = "
                                <h2>عطاء جديد متاح</h2>
                                <p>مرحباً {$user['name']}</p>
                                <p>تم نشر عطاء جديد: $title</p>
                                <p>الفئة: $category</p>
                                <p>تاريخ الانتهاء: $end_date</p>
                                <p>يرجى تسجيل الدخول لعرض التفاصيل والتقديم.</p>
                                <p>مع تحيات فريق TenderGate</p>
                            ";
                            sendEmail($user['email'], $subject, $message);
                        }
                        
                        mysqli_stmt_close($users_stmt);
                    }
                    
                    // Clear form data
                    $_POST = array();
                    
                } else {
                    $error = "حدث خطأ أثناء إضافة العطاء";
                }
                
                mysqli_stmt_close($stmt);
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
    <title>إضافة عطاء جديد - TenderGate</title>
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
                    <h1 class="h2">إضافة عطاء جديد</h1>
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
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-plus me-2"></i>
                                    بيانات العطاء الجديد
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                                      enctype="multipart/form-data">
                                    
                                    <div class="mb-3">
                                        <label for="title" class="form-label">عنوان العطاء *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">وصف العطاء *</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="6" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        <div class="form-text">اكتب وصفاً مفصلاً للعطاء ومتطلباته</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="category" class="form-label">الفئة *</label>
                                                <select class="form-select" id="category" name="category" required>
                                                    <option value="">اختر الفئة</option>
                                                    <option value="تقنية المعلومات" <?php echo (isset($_POST['category']) && $_POST['category'] === 'تقنية المعلومات') ? 'selected' : ''; ?>>تقنية المعلومات</option>
                                                    <option value="الإنشاءات" <?php echo (isset($_POST['category']) && $_POST['category'] === 'الإنشاءات') ? 'selected' : ''; ?>>الإنشاءات</option>
                                                    <option value="الخدمات" <?php echo (isset($_POST['category']) && $_POST['category'] === 'الخدمات') ? 'selected' : ''; ?>>الخدمات</option>
                                                    <option value="التوريدات" <?php echo (isset($_POST['category']) && $_POST['category'] === 'التوريدات') ? 'selected' : ''; ?>>التوريدات</option>
                                                    <option value="الاستشارات" <?php echo (isset($_POST['category']) && $_POST['category'] === 'الاستشارات') ? 'selected' : ''; ?>>الاستشارات</option>
                                                    <option value="أخرى" <?php echo (isset($_POST['category']) && $_POST['category'] === 'أخرى') ? 'selected' : ''; ?>>أخرى</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">حالة العطاء *</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="OPEN" <?php echo (isset($_POST['status']) && $_POST['status'] === 'OPEN') ? 'selected' : ''; ?>>مفتوح</option>
                                                    <option value="CLOSED" <?php echo (isset($_POST['status']) && $_POST['status'] === 'CLOSED') ? 'selected' : ''; ?>>مغلق</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">تاريخ البداية *</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                                       value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="end_date" class="form-label">تاريخ الانتهاء *</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                                       value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>" 
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tender_file" class="form-label">
                                            ملف العطاء <span class="text-muted">(اختياري)</span>
                                        </label>
                                        <div class="file-upload-area">
                                            <input type="file" class="form-control" id="tender_file" 
                                                   name="tender_file" accept=".pdf,.doc,.docx">
                                            <div class="text-center mt-2">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">اسحب الملف هنا أو اضغط للاختيار</p>
                                                <small class="text-muted">PDF, DOC, DOCX (حد أقصى 10MB)</small>
                                            </div>
                                            <div class="file-name mt-2 text-center"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="tenders.php" class="btn btn-secondary me-md-2">إلغاء</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            حفظ العطاء
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    إرشادات
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb me-1"></i> نصائح مهمة:</h6>
                                    <ul class="mb-0">
                                        <li>اكتب عنواناً واضحاً ومختصراً</li>
                                        <li>أضف وصفاً مفصلاً للمتطلبات</li>
                                        <li>حدد تاريخ انتهاء مناسب</li>
                                        <li>أرفق ملف PDF بالتفاصيل الكاملة</li>
                                        <li>اختر الفئة المناسبة للعطاء</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-1"></i> تنبيه:</h6>
                                    <p class="mb-0">سيتم إرسال إشعار بالبريد الإلكتروني لجميع المستخدمين المسجلين عند نشر العطاء.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-alt me-2"></i>
                                    أنواع الملفات المدعومة
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                                        <p class="small">PDF</p>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-file-word fa-2x text-primary mb-2"></i>
                                        <p class="small">DOC</p>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-file-word fa-2x text-primary mb-2"></i>
                                        <p class="small">DOCX</p>
                                    </div>
                                </div>
                                <small class="text-muted">الحد الأقصى لحجم الملف: 10 ميجابايت</small>
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
    
    <script>
        // File upload preview
        document.getElementById('tender_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDiv = document.querySelector('.file-name');
            
            if (fileName) {
                fileNameDiv.innerHTML = `<small class="text-success"><i class="fas fa-check me-1"></i>${fileName}</small>`;
            } else {
                fileNameDiv.innerHTML = '';
            }
        });
        
        // Set minimum end date to tomorrow
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDateInput = document.getElementById('end_date');
            
            startDate.setDate(startDate.getDate() + 1);
            const minEndDate = startDate.toISOString().split('T')[0];
            
            endDateInput.setAttribute('min', minEndDate);
            
            if (endDateInput.value && endDateInput.value <= this.value) {
                endDateInput.value = minEndDate;
            }
        });
    </script>
</body>
</html>

