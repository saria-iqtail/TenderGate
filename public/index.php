<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TenderGate - بوابة العطاءات الذكية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gavel me-2"></i>
                TenderGate
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tenders.php">العطاءات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">حول</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">تسجيل الدخول</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">إنشاء حساب</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary mb-4">
                        بوابة العطاءات الذكية
                    </h1>
                    <p class="lead mb-4">
                        منصة متكاملة لإدارة العطاءات والمناقصات. تصفح العطاءات المتاحة، قدم طلباتك، وتابع حالة تطبيقاتك بكل سهولة.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            إنشاء حساب
                        </a>
                        <a href="tenders.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-search me-2"></i>
                            تصفح العطاءات
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-handshake display-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="fw-bold mb-3">لماذا TenderGate؟</h2>
                    <p class="lead text-muted">نوفر لك أفضل تجربة في إدارة العطاءات</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-shield-alt fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">آمن وموثوق</h5>
                            <p class="card-text">
                                نظام أمان متقدم لحماية بياناتك ومعلوماتك الحساسة
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-clock fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">سريع وفعال</h5>
                            <p class="card-text">
                                واجهة سهلة الاستخدام تساعدك على إنجاز مهامك بسرعة
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">دعم متواصل</h5>
                            <p class="card-text">
                                فريق دعم متخصص لمساعدتك في أي وقت
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="bg-primary text-white py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-file-contract fa-2x"></i>
                    </div>
                    <h3 class="fw-bold">500+</h3>
                    <p>عطاء متاح</p>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3 class="fw-bold">1000+</h3>
                    <p>مستخدم مسجل</p>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    <h3 class="fw-bold">200+</h3>
                    <p>شركة شريكة</p>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <i class="fas fa-award fa-2x"></i>
                    </div>
                    <h3 class="fw-bold">95%</h3>
                    <p>معدل الرضا</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>TenderGate</h5>
                    <p>بوابة العطاءات الذكية - منصة متكاملة لإدارة العطاءات والمناقصات</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>تواصل معنا</h6>
                    <p>
                        <i class="fas fa-envelope me-2"></i>
                        khaleha-3la-alla@ppu.edu
                    </p>
                    <p>
                        <i class="fas fa-phone me-2"></i>
                        +97050000000
                    </p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2025 TenderGate. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>

