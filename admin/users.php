<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$admin_name = $_SESSION['name'];
$error = '';
$success = '';

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_user'])) {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $role = sanitizeInput($_POST['role']);
        
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            $error = "جميع الحقول مطلوبة";
        } elseif (!validateEmail($email)) {
            $error = "البريد الإلكتروني غير صحيح";
        } elseif (!validatePassword($password)) {
            $error = "كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد";
        } else {
            // Check if email already exists
            $check_sql = "SELECT id FROM users WHERE email = ?";
            if ($check_stmt = mysqli_prepare($link, $check_sql)) {
                mysqli_stmt_bind_param($check_stmt, "s", $email);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error = "البريد الإلكتروني مستخدم بالفعل";
                } else {
                    // Insert new user
                    $hashed_password = hashPassword($password);
                    $insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                    
                    if ($insert_stmt = mysqli_prepare($link, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $hashed_password, $role);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $success = "تم إضافة المستخدم بنجاح";
                            
                            // Send welcome email
                            $subject = "مرحباً بك في TenderGate";
                            $message = "
                                <h2>مرحباً $name</h2>
                                <p>تم إنشاء حسابك في منصة TenderGate بواسطة الإدارة.</p>
                                <p>بيانات الدخول:</p>
                                <p>البريد الإلكتروني: $email</p>
                                <p>كلمة المرور: $password</p>
                                <p>يرجى تغيير كلمة المرور بعد تسجيل الدخول.</p>
                                <p>مع تحيات فريق TenderGate</p>
                            ";
                            sendEmail($email, $subject, $message);
                            
                        } else {
                            $error = "حدث خطأ أثناء إضافة المستخدم";
                        }
                        
                        mysqli_stmt_close($insert_stmt);
                    }
                }
                
                mysqli_stmt_close($check_stmt);
            }
        }
    } elseif (isset($_POST['toggle_status'])) {
        $user_id = (int)$_POST['user_id'];
        $new_status = (int)$_POST['new_status'];
        
        $update_sql = "UPDATE users SET isActive = ? WHERE id = ? AND role = 'user'";
        if ($update_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($update_stmt, "ii", $new_status, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = $new_status ? "تم تفعيل المستخدم" : "تم إلغاء تفعيل المستخدم";
            } else {
                $error = "حدث خطأ أثناء تحديث حالة المستخدم";
            }
            
            mysqli_stmt_close($update_stmt);
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        $delete_sql = "DELETE FROM users WHERE id = ? AND role = 'user'";
        if ($delete_stmt = mysqli_prepare($link, $delete_sql)) {
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $success = "تم حذف المستخدم بنجاح";
            } else {
                $error = "حدث خطأ أثناء حذف المستخدم";
            }
            
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// Get all users
$users = array();
$sql = "SELECT * FROM users WHERE role = 'user' ORDER BY createdAt DESC";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $users[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - TenderGate</title>
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
                            <a class="nav-link active" href="users.php">
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
                    <h1 class="h2">إدارة المستخدمين</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-user-plus me-1"></i>
                                إضافة مستخدم
                            </button>
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

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            قائمة المستخدمين (<?php echo count($users); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h4>لا يوجد مستخدمون</h4>
                                <p class="text-muted">لم يتم تسجيل أي مستخدمين بعد</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-user-plus me-2"></i>
                                    إضافة أول مستخدم
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الاسم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>تاريخ التسجيل</th>
                                            <th>الحالة</th>
                                            <th>عدد الطلبات</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle me-2">
                                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php echo date('Y-m-d', strtotime($user['createdAt'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('H:i', strtotime($user['createdAt'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($user['isActive']): ?>
                                                        <span class="badge bg-success">مفعل</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">غير مفعل</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $count_sql = "SELECT COUNT(*) as count FROM tender_applications WHERE user_id = ?";
                                                    if ($count_stmt = mysqli_prepare($link, $count_sql)) {
                                                        mysqli_stmt_bind_param($count_stmt, "i", $user['id']);
                                                        mysqli_stmt_execute($count_stmt);
                                                        $count_result = mysqli_stmt_get_result($count_stmt);
                                                        $count_row = mysqli_fetch_array($count_result, MYSQLI_ASSOC);
                                                        echo $count_row['count'];
                                                        mysqli_stmt_close($count_stmt);
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- Toggle Status -->
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="new_status" value="<?php echo $user['isActive'] ? 0 : 1; ?>">
                                                            <button type="submit" name="toggle_status" 
                                                                    class="btn btn-sm btn-<?php echo $user['isActive'] ? 'warning' : 'success'; ?>"
                                                                    title="<?php echo $user['isActive'] ? 'إلغاء التفعيل' : 'تفعيل'; ?>">
                                                                <i class="fas fa-<?php echo $user['isActive'] ? 'ban' : 'check'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Delete User -->
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="delete_user" 
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
            </main>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مستخدم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">الاسم الكامل</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">نوع الحساب</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">مستخدم</option>
                                <option value="admin">مدير</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_user" class="btn btn-primary">إضافة المستخدم</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</body>
</html>

