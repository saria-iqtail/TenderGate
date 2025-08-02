// Main JavaScript file for TenderGate

$(document).ready(function() {
    
    // Password strength checker
    $('#password').on('input', function() {
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
    
    // Confirm password validation
    $('#confirm_password').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        const feedback = $('#confirm-password-feedback');
        
        if (confirmPassword.length === 0) {
            feedback.text('');
            return;
        }
        
        if (password === confirmPassword) {
            feedback.text('كلمات المرور متطابقة').css('color', '#28a745');
        } else {
            feedback.text('كلمات المرور غير متطابقة').css('color', '#dc3545');
        }
    });
    
    // Email validation
    $('#email').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const feedback = $('#email-feedback');
        
        if (email.length === 0) {
            feedback.text('');
            return;
        }
        
        if (emailRegex.test(email)) {
            feedback.text('البريد الإلكتروني صحيح').css('color', '#28a745');
        } else {
            feedback.text('البريد الإلكتروني غير صحيح').css('color', '#dc3545');
        }
    });
    
    // File upload drag and drop
    $('.file-upload-area').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    $('.file-upload-area').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    $('.file-upload-area').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            const fileInput = $(this).find('input[type="file"]');
            fileInput[0].files = files;
            updateFileDisplay(fileInput[0]);
        }
    });
    
    // File input change
    $('input[type="file"]').on('change', function() {
        updateFileDisplay(this);
    });
    
    function updateFileDisplay(input) {
        const fileName = input.files[0] ? input.files[0].name : 'لم يتم اختيار ملف';
        $(input).siblings('.file-name').text(fileName);
    }
    
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Check password strength for registration forms
        if ($(this).find('#password').length > 0) {
            const password = $('#password').val();
            if (!validatePassword(password)) {
                isValid = false;
                $('#password').addClass('is-invalid');
                showAlert('كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير واحد، ورمز خاص واحد', 'danger');
            }
        }
        
        // Check password confirmation
        if ($(this).find('#confirm_password').length > 0) {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            if (password !== confirmPassword) {
                isValid = false;
                $('#confirm_password').addClass('is-invalid');
                showAlert('كلمات المرور غير متطابقة', 'danger');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Search functionality
    $('#search-input').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.tender-card').each(function() {
            const title = $(this).find('.card-title').text().toLowerCase();
            const description = $(this).find('.card-text').text().toLowerCase();
            
            if (title.includes(searchTerm) || description.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Filter functionality
    $('#category-filter').on('change', function() {
        const selectedCategory = $(this).val();
        $('.tender-card').each(function() {
            const category = $(this).data('category');
            
            if (selectedCategory === '' || category === selectedCategory) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Status filter
    $('#status-filter').on('change', function() {
        const selectedStatus = $(this).val();
        $('.tender-card').each(function() {
            const status = $(this).data('status');
            
            if (selectedStatus === '' || status === selectedStatus) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Auto-hide alerts
    $('.alert').delay(5000).fadeOut();
    
    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        if (!confirm('هل أنت متأكد من الحذف؟')) {
            e.preventDefault();
        }
    });
    
    // AJAX form submission
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = new FormData(this);
        const submitBtn = form.find('[type="submit"]');
        const originalText = submitBtn.text();
        
        // Show loading
        submitBtn.prop('disabled', true).text('جاري التحميل...');
        
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    showAlert(data.message, 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                } else {
                    showAlert(data.message, 'danger');
                }
            },
            error: function() {
                showAlert('حدث خطأ في الخادم', 'danger');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});

// Helper functions
function validatePassword(password) {
    return /^(?=.*[A-Z])(?=.*[!@#$%^&*])(.{8,})$/.test(password);
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alerts-container').html(alertHtml);
    $('.alert').delay(5000).fadeOut();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA');
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return 'منذ يوم واحد';
    } else if (diffDays < 7) {
        return `منذ ${diffDays} أيام`;
    } else if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return `منذ ${weeks} أسبوع`;
    } else {
        const months = Math.floor(diffDays / 30);
        return `منذ ${months} شهر`;
    }
}

