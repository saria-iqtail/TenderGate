<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        
        if (empty($name) || empty($email)) {
            $error = "الاسم والبريد الإلكتروني مطلوبان";
        } elseif (!validateEmail($email)) {
            $error = "البريد الإلكتروني غير صحيح";
        } else {
            // Check if email is already used by another user
            $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            if ($check_stmt = mysqli_prepare($link, $check_sql)) {
                mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error = "البريد الإلكتروني مستخدم بالفعل";
                } else {
                    // Update user profile
                    $update_sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                    if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "ssi", $name, $email, $user_id);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            $success = "تم تحديث الملف الشخصي بنجاح";
                            $_SESSION['name'] = $name;
                            $_SESSION['email'] = $email;
                            $user['name'] = $name;
                            $user['email'] = $email;
                        } else {
                            $error = "حدث خطأ أثناء التحديث";
                        }
                        
                        mysqli_stmt_close($update_stmt);
                    }
                }
                
                mysqli_stmt_close($check_stmt);
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "جميع حقول كلمة المرور مطلوبة";
        } elseif (!verifyPassword($current_password, $user['password'])) {
            $error = "كلمة المرور الحالية غير صحيحة";
        } elseif (!validatePassword($new_password)) {
            $error = "كلمة المرور الجديدة يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد";
        } elseif ($new_password !== $confirm_password) {
            $error = "كلمات المرور الجديدة غير متطابقة";
        } else {
            // Update password
            $hashed_password = hashPassword($new_password);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success = "تم تغيير كلمة المرور بنجاح";
                    $user['password'] = $hashed_password;
                    
                    // Send notification email
                    $subject = "تم تغيير كلمة المرور - TenderGate";
                    $message = "
                        <h2>تم تغيير كلمة المرور</h2>
                        <p>تم تغيير كلمة المرور لحسابك في TenderGate بنجاح.</p>
                        <p>إذا لم تقم بهذا التغيير، يرجى التواصل معنا فوراً.</p>
                        <p>مع تحيات فريق TenderGate</p>
                    ";
                    sendEmail($user['email'], $subject, $message);
                    
                } else {
                    $error = "حدث خطأ أثناء تغيير كلمة المرور";
                }
                
                mysqli_stmt_close($update_stmt);
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
    <title>الملف الشخصي - TenderGate</title>
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
                        <small>مرحباً، <?php echo htmlspecialchars($user['name']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
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
                    <h1 class="h2">الملف الشخصي</h1>
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
                    <!-- Profile Information -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    المعلومات الشخصية
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">الاسم الكامل</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div id="email-feedback" class="form-text"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">تاريخ التسجيل</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-calendar"></i>
                                            </span>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo date('Y-m-d', strtotime($user['createdAt'])); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">نوع الحساب</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-shield-alt"></i>
                                            </span>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>" readonly>
                                        </div>
                                    </div>

                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ التغييرات
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-lock me-2"></i>
                                    تغيير كلمة المرور
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-2"></div>
                                        <div id="password-strength-text" class="form-text"></div>
                                        <small class="form-text text-muted">
                                            يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div id="confirm-password-feedback" class="form-text"></div>
                                    </div>

                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>
                                        تغيير كلمة المرور
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    إحصائيات الحساب
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                                            <h4 class="mb-0">
                                                <?php
                                                $sql = "SELECT COUNT(*) as count FROM tender_applications WHERE user_id = ?";
                                                if ($stmt = mysqli_prepare($link, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                                                    echo $row['count'];
                                                    mysqli_stmt_close($stmt);
                                                }
                                                ?>
                                            </h4>
                                            <p class="text-muted mb-0">إجمالي الطلبات</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                            <h4 class="mb-0">
                                                <?php
                                                $sql = "SELECT COUNT(*) as count FROM tender_applications ta 
                                                        JOIN tenders t ON ta.tender_id = t.id 
                                                        WHERE ta.user_id = ? AND t.status = 'OPEN'";
                                                if ($stmt = mysqli_prepare($link, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                                                    echo $row['count'];
                                                    mysqli_stmt_close($stmt);
                                                }
                                                ?>
                                            </h4>
                                            <p class="text-muted mb-0">طلبات قيد المراجعة</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                            <h4 class="mb-0">
                                                <?php
                                                $sql = "SELECT COUNT(*) as count FROM tender_applications ta 
                                                        JOIN tenders t ON ta.tender_id = t.id 
                                                        WHERE ta.user_id = ? AND t.status = 'AWARDED'";
                                                if ($stmt = mysqli_prepare($link, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                                                    echo $row['count'];
                                                    mysqli_stmt_close($stmt);
                                                }
                                                ?>
                                            </h4>
                                            <p class="text-muted mb-0">طلبات مقبولة</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                            <h4 class="mb-0">
                                                <?php
                                                $sql = "SELECT COUNT(*) as count FROM tender_applications ta 
                                                        JOIN tenders t ON ta.tender_id = t.id 
                                                        WHERE ta.user_id = ? AND t.status = 'CLOSED'";
                                                if ($stmt = mysqli_prepare($link, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                                                    echo $row['count'];
                                                    mysqli_stmt_close($stmt);
                                                }
                                                ?>
                                            </h4>
                                            <p class="text-muted mb-0">طلبات مرفوضة</p>
                                        </div>
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
    
    <script>
        // Toggle password visibility
        $('#toggleCurrentPassword').on('click', function() {
            const password = $('#current_password');
            const icon = $(this).find('i');
            
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        $('#toggleNewPassword').on('click', function() {
            const password = $('#new_password');
            const icon = $(this).find('i');
            
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        $('#toggleConfirmPassword').on('click', function() {
            const password = $('#confirm_password');
            const icon = $(this).find('i');
            
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Password strength for new password
        $('#new_password').on('input', function() {
            const password = $(this).val();
            const strengthBar = $('.password-strength');
            const strengthText = $('#password-strength-text');
            
            if (password.length === 0) {
                strengthBar.removeClass().addClass('password-strength');
                strengthText.text('');
                return;
            }
            
            let strength = 0;
            
            // Check length
            if (password.length >= 8) strength++;
            
            // Check for uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Check for special characters
            if (/[!@#$%^&*]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.removeClass();
            strengthBar.addClass('password-strength');
            
            if (strength === 1) {
                strengthBar.addClass('strength-weak');
                strengthText.text('ضعيف').css('color', '#dc3545');
            } else if (strength === 2) {
                strengthBar.addClass('strength-medium');
                strengthText.text('متوسط').css('color', '#ffc107');
            } else if (strength === 3) {
                strengthBar.addClass('strength-strong');
                strengthText.text('قوي').css('color', '#28a745');
            }
        });
    </script>
</body>
</html>

