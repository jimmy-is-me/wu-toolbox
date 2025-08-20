<?php
if (!defined('ABSPATH')) exit;

/* === 後台子選單 === */
function moving_mode_menu() {
    add_submenu_page(
        'wu-toolbox',
        'Moving Mode',
        'Moving Mode',
        'manage_options',
        'moving-mode',
        'moving_mode_settings_page'
    );
}
add_action('admin_menu','moving_mode_menu');

/* === 後台設定頁 === */
function moving_mode_settings_page() {
    if (isset($_POST['moving_mode_save']) && check_admin_referer('moving_mode_save','moving_mode_nonce')) {
        update_option('moving_mode_status', sanitize_text_field($_POST['moving_mode_status']));
        update_option('moving_mode_title', sanitize_text_field($_POST['moving_mode_title']));
        update_option('moving_mode_message', sanitize_textarea_field($_POST['moving_mode_message']));
        update_option('moving_mode_emoji', sanitize_text_field($_POST['moving_mode_emoji']));
        echo '<div class="updated"><p>維護模式設定已更新 ✅</p></div>';
    }

    $status  = get_option('moving_mode_status','off');
    $title   = get_option('moving_mode_title','網站維護中');
    $message = get_option('moving_mode_message','我們正在為您帶來更好的體驗，請稍後再回來 🙏');
    $emoji   = get_option('moving_mode_emoji','🛠️');
    ?>
    <div class="wrap">
        <h1>Wumetax 維護模式設定</h1>
        <div style="display:flex;gap:40px;flex-wrap:wrap;align-items:flex-start;">

            <!-- 設定表單 -->
            <form method="post" style="flex:1;min-width:300px;">
                <?php wp_nonce_field('moving_mode_save','moving_mode_nonce'); ?>
                <h2>功能開關</h2>
                <p>
                    <label>
                        <input type="radio" name="moving_mode_status" value="on" <?php checked($status,'on'); ?>>
                        開啟維護模式<br>
                        （前台訪客將會看到維護頁，僅管理員可登入並使用網站）
                    </label><br><br>
                    <label>
                        <input type="radio" name="moving_mode_status" value="off" <?php checked($status,'off'); ?>>
                        關閉維護模式<br>
                        （網站將正常顯示給所有訪客）
                    </label>
                </p>

                <h2>維護頁面內容</h2>
                <p>標題：<input type="text" id="moving_mode_title" name="moving_mode_title" value="<?php echo esc_attr($title); ?>" class="regular-text"></p>
                <p>描述：<textarea id="moving_mode_message" name="moving_mode_message" rows="3" class="large-text"><?php echo esc_textarea($message); ?></textarea></p>
                <p>Emoji（即時預覽圖示）：<input type="text" id="moving_mode_emoji" name="moving_mode_emoji" value="<?php echo esc_attr($emoji); ?>" class="regular-text"></p>
                <p><input type="submit" name="moving_mode_save" class="button-primary" value="儲存設定"></p>
            </form>

            <!-- 即時預覽 -->
            <div style="flex:1;max-width:450px;">
                <h2>即時預覽</h2>
                <div id="preview-box" style="border:1px solid #ccc;border-radius:10px;padding:30px;background:#ffffff;color:#111;text-align:center;">
                    <div id="preview-emoji" style="font-size:4em;margin-bottom:20px;"><?php echo esc_html($emoji); ?></div>
                    <h3 id="preview-title" style="color:#111;font-size:1.5em;"><?php echo esc_html($title); ?></h3>
                    <p id="preview-message" style="color:#555;"><?php echo nl2br(esc_html($message)); ?></p>
                    <button style="margin-top:15px;padding:8px 16px;background:#111;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:bold;">管理員登入</button>
                </div>
            </div>

        </div>
    </div>

    <script>
    const titleField = document.getElementById("moving_mode_title");
    const messageField = document.getElementById("moving_mode_message");
    const emojiField = document.getElementById("moving_mode_emoji");

    const previewTitle = document.getElementById("preview-title");
    const previewMessage = document.getElementById("preview-message");
    const previewEmoji = document.getElementById("preview-emoji");

    titleField.addEventListener("input",()=>previewTitle.innerText=titleField.value||"網站維護中");
    messageField.addEventListener("input",()=>previewMessage.innerHTML=(messageField.value||"我們正在為您帶來更好的體驗，請稍後再回來 🙏").replace(/\n/g,"<br>"));
    emojiField.addEventListener("input",()=>previewEmoji.innerText=emojiField.value||"🛠️");
    </script>
    <?php
}

/* === 前台維護頁 === */
function moving_mode_output() {
    if (current_user_can('manage_options')) return;
    if (get_option('moving_mode_status','off') !== 'on') return;

    $title = get_option('moving_mode_title','網站維護中');
    $message = get_option('moving_mode_message','我們正在為您帶來更好的體驗，請稍後再回來 🙏');
    $emoji   = get_option('moving_mode_emoji','🛠️');

    nocache_headers();
    status_header(503);
    header('Retry-After: 3600');

    echo '<!DOCTYPE html><html lang="zh-Hant"><head><meta charset="UTF-8"><title>'.esc_html($title).'</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    html,body{height:100%;margin:0;padding:0;}
    body{display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#111;}
    h1{font-size:2.2em;margin:.5em 0;color:#111;}
    p{font-size:1.2em;color:#555;white-space:pre-wrap;}
    .emoji{font-size:4em;margin-bottom:20px;}
    #login-btn{position:fixed;top:15px;right:15px;background:#111;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;z-index:9999;font-size:14px;font-weight:bold;}
    #login-box{display:none;position:fixed;top:15%;left:50%;transform:translateX(-50%);background:#fff;border-radius:16px;padding:25px;z-index:9999;width:90%;max-width:340px;box-shadow:0 10px 25px rgba(0,0,0,.3);text-align:center;color:#111;}
    #login-box h2{margin-top:0;font-weight:600;color:#111;}
    #login-box label{display:block;margin-top:10px;font-size:.9em;color:#333;text-align:left;}
    #login-box input[type=text],#login-box input[type=password]{width:100%;padding:10px;margin-top:5px;border:1px solid #ccc;border-radius:8px;background:#f7f7f7;color:#111;text-align:left;}
    #close-login{position:absolute;top:10px;right:15px;cursor:pointer;font-size:18px;color:#aaa;}
    body.blurred>*:not(#login-box):not(#login-btn){filter:blur(5px);}
    @media(max-width:768px){h1{font-size:1.8em;}p{font-size:1em;}#login-box{top:10%;padding:20px;}}
    </style></head>
    <body>
    <button id="login-btn">管理員登入</button>
    <div class="emoji">'.$emoji.'</div>
    <h1>'.esc_html($title).'</h1>
    <p>'.nl2br(esc_html($message)).'</p>
    <div id="login-box">
        <span id="close-login">✖</span>
        <h2>管理員登入</h2>
        <form id="login-form" action="'.esc_url(wp_login_url()).'" method="post">
            <label for="user_login">帳號 / 電子郵件</label>
            <input type="text" name="log" id="user_login" required>
            <label for="user_pass">密碼</label>
            <input type="password" name="pwd" id="user_pass" required>
            <br><br>
            <button type="submit" style="width:100%;padding:10px;background:#111;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;font-weight:bold;">登入</button>
        </form>
    </div>
    <script>
    const loginBtn=document.getElementById("login-btn");
    const loginBox=document.getElementById("login-box");
    const closeBtn=document.getElementById("close-login");
    const userField=document.getElementById("user_login");
    const passField=document.getElementById("user_pass");
    const loginForm=document.getElementById("login-form");

    loginBtn.addEventListener("click",()=>{document.body.classList.add("blurred");loginBox.style.display="block";userField.focus();});
    closeBtn.addEventListener("click",()=>{document.body.classList.remove("blurred");loginBox.style.display="none";});
    loginForm.addEventListener("submit",function(e){if(userField.value.trim()==="" || passField.value.trim()===""){e.preventDefault();alert("請輸入帳號與密碼！");}});
    [userField, passField].forEach(el=>{el.addEventListener("keypress",e=>{if(e.key==="Enter"){e.preventDefault();loginForm.querySelector("button[type=submit]").click();}});});
    </script>
    </body></html>';
    exit;
}
add_action('template_redirect','moving_mode_output');
