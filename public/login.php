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
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "جميع الحقول مطلوبة";
    } else {
        // Check user credentials
        $sql = "SELECT id, name, email, password, role, isActive FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    
                    if (!$row['isActive']) {
                        $error = "حسابك غير مفعل. يرجى التواصل مع الإدارة";
                    } elseif (verifyPassword($password, $row['password'])) {
                        // Password is correct, start session
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['name'] = $row['name'];
                        $_SESSION['email'] = $row['email'];
                        $_SESSION['role'] = $row['role'];
                        
                        // Redirect based on role
                        if ($row['role'] === 'admin') {
                            header("Location: ../admin/dashboard.php");
                        } else {
                            header("Location: ../user/dashboard.php");
                        }
                        exit();
                    } else {
                        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
                    }
                } else {
                    $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
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
    <title>تسجيل الدخول - TenderGate</title>
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
                            <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">TenderGate</h2>
                            <p class="text-muted">تسجيل الدخول إلى حسابك</p>
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
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required>
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
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    تذكرني
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                تسجيل الدخول
                            </button>

                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none">
                                    نسيت كلمة المرور؟
                                </a>
                            </div>
                        </form>

                        <hr>

                        <div class="text-center">
                            <p class="mb-0">ليس لديك حساب؟</p>
                            <a href="register.php" class="btn btn-outline-primary w-100 mt-2">
                                <i class="fas fa-user-plus me-2"></i>
                                إنشاء حساب جديد
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
    </script>
</body>
</html>

