/**
 * 升級版：美化登入頁面 - 更透明、更有質感、按鈕更簡潔
 */
public function beautify_login_page() {
    echo '<style>
        body.login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            background-attachment: fixed !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            position: relative !important;
        }
        
        body.login::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.03"%3E%3Ccircle cx="7" cy="7" r="7"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") !important;
            z-index: -1 !important;
        }
        
        .login #loginform {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            padding: 45px !important;
            margin-top: 30px !important;
            position: relative !important;
        }
        
        .login #loginform::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            border-radius: 20px;
            z-index: -1;
        }
        
        .login form .input, 
        .login input[type=text], 
        .login input[type=password] {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 12px !important;
            color: #333 !important;
            font-size: 16px !important;
            font-weight: 400 !important;
            padding: 15px 20px !important;
            height: 50px !important;
            box-sizing: border-box !important;
            margin-bottom: 20px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
        }
        
        .login form .input:focus, 
        .login input[type=text]:focus, 
        .login input[type=password]:focus {
            background: rgba(255, 255, 255, 0.95) !important;
            border-color: rgba(102, 126, 234, 0.8) !important;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15), 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
            outline: none !important;
            transform: translateY(-2px) !important;
        }
        
        .login .button-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            border-radius: 12px !important;
            color: #fff !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            padding: 15px 30px !important;
            height: 50px !important;
            text-shadow: none !important;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            width: 100% !important;
            margin-top: 15px !important;
            cursor: pointer !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .login .button-primary::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .login .button-primary:hover::before {
            left: 100%;
        }
        
        .login .button-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
        }
        
        .login .button-primary:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.3) !important;
        }
        
        .login label {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            margin-bottom: 8px !important;
            display: block !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        }
        
        .login #backtoblog a, 
        .login #nav a {
            color: rgba(255, 255, 255, 0.8) !important;
            text-decoration: none !important;
            transition: all 0.3s ease !important;
            font-size: 14px !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        }
        
        .login #backtoblog a:hover, 
        .login #nav a:hover {
            color: rgba(255, 255, 255, 1) !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5) !important;
        }
        
        .login .message, 
        .login .notice {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 12px !important;
            color: #333 !important;
            backdrop-filter: blur(15px) !important;
            -webkit-backdrop-filter: blur(15px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
        }
        
        .login h1 {
            text-align: center !important;
            margin-bottom: 40px !important;
        }
        
        .login h1 a {
            background-image: none !important;
            color: rgba(255, 255, 255, 0.95) !important;
            font-size: 28px !important;
            font-weight: 300 !important;
            text-decoration: none !important;
            text-align: center !important;
            display: block !important;
            width: auto !important;
            height: auto !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        }
        
        .login form .forgetmenot {
            color: rgba(255, 255, 255, 0.8) !important;
            font-size: 14px !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        }
        
        .login form .forgetmenot input[type=checkbox] {
            margin-right: 10px !important;
            transform: scale(1.1) !important;
        }
        
        @media screen and (max-width: 768px) {
            .login #loginform {
                margin: 20px auto !important;
                padding: 35px !important;
                border-radius: 16px !important;
            }
            
            .login form .input, 
            .login input[type=text], 
            .login input[type=password] {
                font-size: 16px !important;
                padding: 12px 18px !important;
                height: 46px !important;
            }
            
            .login .button-primary {
                height: 46px !important;
                padding: 12px 25px !important;
            }
        }
    </style>';
}
