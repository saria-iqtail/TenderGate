<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "جميع الحقول مطلوبة";
    } elseif (!validateEmail($email)) {
        $error = "البريد الإلكتروني غير صحيح";
    } elseif (!validatePassword($password)) {
        $error = "كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد";
    } elseif ($password !== $confirm_password) {
        $error = "كلمات المرور غير متطابقة";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    $error = "البريد الإلكتروني مستخدم بالفعل";
                } else {
                    // Insert new user
                    $hashed_password = hashPassword($password);
                    $insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
                    
                    if ($insert_stmt = mysqli_prepare($link, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, "sss", $name, $email, $hashed_password);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $success = "تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول";
                            
                            // Send welcome email
                            $subject = "مرحباً بك في TenderGate";
                            $message = "
                                <h2>مرحباً $name</h2>
                                <p>تم إنشاء حسابك بنجاح في منصة TenderGate.</p>
                                <p>يمكنك الآن تسجيل الدخول وتصفح العطاءات المتاحة.</p>
                                <p>مع تحيات فريق TenderGate</p>
                            ";
                            sendEmail($email, $subject, $message);
                            
                        } else {
                            $error = "حدث خطأ أثناء إنشاء الحساب";
                        }
                        
                        mysqli_stmt_close($insert_stmt);
                    }
                }
            } else {
                $error = "حدث خطأ في النظام";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - TenderGate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-4">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">TenderGate</h2>
                            <p class="text-muted">إنشاء حساب جديد</p>
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

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">الاسم الكامل</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <div id="email-feedback" class="form-text"></div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور</label>
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

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    أوافق على <a href="#" class="text-decoration-none">الشروط والأحكام</a>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>
                                إنشاء الحساب
                            </button>
                        </form>

                        <hr>

                        <div class="text-center">
                            <p class="mb-0">لديك حساب بالفعل؟</p>
                            <a href="login.php" class="btn btn-outline-primary w-100 mt-2">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                تسجيل الدخول
                            </a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-right me-1"></i>
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

