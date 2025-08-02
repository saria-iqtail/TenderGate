<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$valid_token = false;
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    // Verify token
    $sql = "SELECT email FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 1) {
                $valid_token = true;
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $email = $row['email'];
            } else {
                $error = "رابط إعادة تعيين كلمة المرور غير صحيح أو منتهي الصلاحية";
            }
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    $error = "رابط غير صحيح";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "جميع الحقول مطلوبة";
    } elseif (!validatePassword($password)) {
        $error = "كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد";
    } elseif ($password !== $confirm_password) {
        $error = "كلمات المرور غير متطابقة";
    } else {
        // Update password
        $hashed_password = hashPassword($password);
        $update_sql = "UPDATE users SET password = ? WHERE email = ?";
        
        if ($update_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $email);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Delete used token
                $delete_sql = "DELETE FROM password_resets WHERE token = ?";
                if ($delete_stmt = mysqli_prepare($link, $delete_sql)) {
                    mysqli_stmt_bind_param($delete_stmt, "s", $token);
                    mysqli_stmt_execute($delete_stmt);
                    mysqli_stmt_close($delete_stmt);
                }
                
                $success = "تم تغيير كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول";
                
                // Send confirmation email
                $subject = "تم تغيير كلمة المرور - TenderGate";
                $message = "
                    <h2>تم تغيير كلمة المرور</h2>
                    <p>تم تغيير كلمة المرور لحسابك في TenderGate بنجاح.</p>
                    <p>إذا لم تقم بهذا التغيير، يرجى التواصل معنا فوراً.</p>
                    <p>مع تحيات فريق TenderGate</p>
                ";
                sendEmail($email, $subject, $message);
                
            } else {
                $error = "حدث خطأ أثناء تحديث كلمة المرور";
            }
            
            mysqli_stmt_close($update_stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - TenderGate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">إعادة تعيين كلمة المرور</h2>
                            <p class="text-muted">أدخل كلمة المرور الجديدة</p>
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

                        <?php if ($valid_token && empty($success)): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?token=" . $token; ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
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
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
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

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-save me-2"></i>
                                حفظ كلمة المرور الجديدة
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                تسجيل الدخول
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>
                                العودة للرئيسية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Toggle password visibility
        $('#togglePassword').on('click', function() {
            const password = $('#password');
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
    </script>
</body>
</html>

