<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Supervisor Monitor - Đăng nhập</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Supervisor Monitor Login">
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo base_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('css/bootstrap-responsive.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('css/custom.css'); ?>" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: #333;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <div class="login-header">
                <h2>Supervisor Monitor</h2>
                <p>Đăng nhập để truy cập hệ thống giám sát</p>
            </div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('message')): ?>
                <div class="alert alert-success">
                    <?php echo $this->session->flashdata('message'); ?>
                </div>
            <?php endif; ?>
            
            <?php echo form_open('auth/login', array('id' => 'loginForm')); ?>
                <div class="form-group">
                    <label for="username">Tên đăng nhập:</label>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           placeholder="Nhập tên đăng nhập"
                           required
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu:</label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           placeholder="Nhập mật khẩu"
                           required
                           autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn-login">
                    Đăng nhập
                </button>
            <?php echo form_close(); ?>
            
            <div class="footer-text">
                <p>Supervisor Monitor System v1.0</p>
                <p>Tài khoản mặc định: admin/admin123, supervisor/supervisor123, monitor/monitor123</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('js/jquery-1.10.1.min.js'); ?>"></script>
    <script src="<?php echo base_url('js/bootstrap.min.js'); ?>"></script>
    
    <script>
        $(document).ready(function() {
            // Focus on username field
            $('#username').focus();
            
            // Form validation
            $('#loginForm').on('submit', function(e) {
                var username = $('#username').val().trim();
                var password = $('#password').val().trim();
                
                if (username === '' || password === '') {
                    e.preventDefault();
                    alert('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!');
                    return false;
                }
            });
            
            // Enter key handling
            $('.form-control').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    $('#loginForm').submit();
                }
            });
        });
    </script>
</body>
</html>