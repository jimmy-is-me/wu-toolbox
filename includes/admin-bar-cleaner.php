/**
 * 修改：美化登入頁面 - 半透明風格、白底、細欄位
 */
public function beautify_login_page() {
    echo '<style>
        body.login {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
        }
        
        .login #loginform {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(15px) !important;
            -webkit-backdrop-filter: blur(15px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 15px !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.12) !important;
            padding: 40px !important;
            margin-top: 20px !important;
        }
        
        .login form .input, 
        .login input[type=text], 
        .login input[type=password] {
            background: rgba(255, 255, 255, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            border-radius: 8px !important;
            color: #333 !important;
            font-size: 16px !important;
            padding: 10px 15px !important;
            height: 40px !important;
            box-sizing: border-box !important;
            margin-bottom: 15px !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(5px) !important;
            -webkit-backdrop-filter: blur(5px) !important;
        }
        
        .login form .input:focus, 
        .login input[type=text]:focus, 
        .login input[type=password]:focus {
            background: rgba(255, 255, 255, 0.95) !important;
            border-color: rgba(0, 115, 170, 0.6) !important;
            box-shadow: 0 0 15px rgba(0, 115, 170, 0.3) !important;
            outline: none !important;
            transform: translateY(-1px) !important;
        }
        
        .login form .input::placeholder,
        .login input[type=text]::placeholder,
        .login input[type=password]::placeholder {
            color: #999 !important;
        }
        
        .login .button-primary {
            background: linear-gradient(135deg, rgba(0, 115, 170, 0.9) 0%, rgba(0, 81, 119, 0.9) 100%) !important;
            border: none !important;
            border-radius: 8px !important;
            color: #fff !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            padding: 12px 30px !important;
            height: 45px !important;
            text-shadow: none !important;
            box-shadow: 0 4px 15px 0 rgba(0, 115, 170, 0.3) !important;
            transition: all 0.3s ease !important;
            width: 100% !important;
            margin-top: 10px !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
        }
        
        .login .button-primary:hover {
            background: linear-gradient(135deg, rgba(0, 81, 119, 0.95) 0%, rgba(0, 115, 170, 0.95) 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px 0 rgba(0, 115, 170, 0.4) !important;
        }
        
        .login .button-primary:focus {
            box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.3) !important;
        }
        
        .login label {
            color: #333 !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            margin-bottom: 6px !important;
            display: block !important;
        }
        
        .login #backtoblog a, 
        .login #nav a {
            color: rgba(102, 102, 102, 0.8) !important;
            text-decoration: none !important;
            transition: all 0.3s ease !important;
            font-size: 14px !important;
        }
        
        .login #backtoblog a:hover, 
        .login #nav a:hover {
            color: rgba(0, 115, 170, 0.9) !important;
        }
        
        .login .message, 
        .login .notice {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            border-radius: 8px !important;
            color: #333 !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
        }
        
        .login h1 a {
            background-image: none !important;
            color: #333 !important;
            font-size: 32px !important;
            font-weight: 300 !important;
            text-decoration: none !important;
            text-align: center !important;
            display: block !important;
            width: auto !important;
            height: auto !important;
        }
        
        .login h1 {
            text-align: center !important;
            margin-bottom: 30px !important;
        }
        
        .login form .forgetmenot {
            color: #333 !important;
            font-size: 14px !important;
        }
        
        .login form .forgetmenot input[type=checkbox] {
            margin-right: 8px !important;
        }
        
        /* 增強透明效果 */
        .login #loginform::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            z-index: -1;
        }
        
        @media screen and (max-width: 768px) {
            .login #loginform {
                margin: 20px auto !important;
                padding: 30px !important;
            }
            
            .login form .input, 
            .login input[type=text], 
            .login input[type=password] {
                font-size: 16px !important;
                padding: 8px 12px !important;
                height: 38px !important;
            }
            
            .login .button-primary {
                height: 42px !important;
                padding: 10px 25px !important;
            }
        }
    </style>';
}
