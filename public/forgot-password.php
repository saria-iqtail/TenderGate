<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);
    
    if (empty($email)) {
        $error = "البريد الإلكتروني مطلوب";
    } elseif (!validateEmail($email)) {
        $error = "البريد الإلكتروني غير صحيح";
    } else {
        // Check if email exists
        $sql = "SELECT id, name FROM users WHERE email = ? AND isActive = 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    
                    // Generate reset token
                    $token = generateToken();
                    
                    // Store token in database
                    $insert_sql = "INSERT INTO password_resets (email, token) VALUES (?, ?)";
                    if ($insert_stmt = mysqli_prepare($link, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, "ss", $email, $token);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            // Send reset email
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                            
                            $subject = "إعادة تعيين كلمة المرور - TenderGate";
                            $message = "
                                <h2>مرحباً {$row['name']}</h2>
                                <p>تم طلب إعادة تعيين كلمة المرور لحسابك في TenderGate.</p>
                                <p>اضغط على الرابط التالي لإعادة تعيين كلمة المرور:</p>
                                <p><a href='$reset_link'>إعادة تعيين كلمة المرور</a></p>
                                <p>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.</p>
                                <p>مع تحيات فريق TenderGate</p>
                            ";
                            
                            if (sendEmail($email, $subject, $message)) {
                                $success = "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني";
                            } else {
                                $error = "حدث خطأ أثناء إرسال البريد الإلكتروني";
                            }
                        } else {
                            $error = "حدث خطأ في النظام";
                        }
                        
                        mysqli_stmt_close($insert_stmt);
                    }
                } else {
                    $success = "إذا كان البريد الإلكتروني موجود في نظامنا، ستتلقى رسالة إعادة تعيين كلمة المرور";
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
    <title>استعادة كلمة المرور - TenderGate</title>
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
                            <i class="fas fa-key fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">استعادة كلمة المرور</h2>
                            <p class="text-muted">أدخل بريدك الإلكتروني لاستعادة كلمة المرور</p>
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

                        <?php if (empty($success)): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>
                                إرسال رابط الاستعادة
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-right me-1"></i>
                                العودة لتسجيل الدخول
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
</body>
</html>

