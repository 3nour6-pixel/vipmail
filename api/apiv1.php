<?php

require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

function verifyHCaptcha($token) {
    if (empty(HCAPTCHA_SECRET_KEY)) {
        error_log('hCaptcha secret key is not configured');
        return false;
    }
    
    $data = [
        'secret' => HCAPTCHA_SECRET_KEY,
        'response' => $token
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents('https:
    
    if ($result === false) {
        error_log('Failed to verify hCaptcha: connection error');
        return false;
    }
    
    $response = json_decode($result);
    return $response && $response->success === true;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

function validateDesiredEmail($desiredEmail) {
    
    if (empty($desiredEmail)) {
        return false;
    }
    
    
    if (strlen($desiredEmail) < 3 || strlen($desiredEmail) > 64) {
        return false;
    }
    
    
    if (!preg_match('/^[a-z0-9._-]+$/', $desiredEmail)) {
        return false;
    }
    
    
    if (strpos($desiredEmail, '..') !== false) {
        return false;
    }
    
    
    if (preg_match('/^[.-]|[.-]$/', $desiredEmail)) {
        return false;
    }
    
    return true;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sendToTelegram($tempPhotoPath, $originalFilename, $email, $desiredEmail, $phone, $paymentMethod, $paymentType = null) {
    
    if (empty(TELEGRAM_BOT_TOKEN) || empty(TELEGRAM_CHAT_ID)) {
        error_log('Telegram credentials are not configured');
        return false;
    }
    
    
    if (!file_exists($tempPhotoPath)) {
        error_log('Temporary photo file does not exist: ' . $tempPhotoPath);
        return false;
    }
    
    $url = "https:
    
    
    $caption = "🎉 *New VIP Mail Payment Request*\n\n";
    $caption .= "💳 *Payment Method:* " . ucfirst($paymentMethod) . "\n";
    
    if ($paymentType) {
        
        $caption .= "📋 *Payment Type:* " . ucfirst(str_replace('_', ' ', $paymentType)) . "\n";
    }
    
    
    $caption .= "📧 *Email (Contact):* " . $email . "\n";
    $caption .= "✉️ *Desired Email:* " . $desiredEmail . "@vipm.org" . "\n"; 
    $caption .= "*Accepted Terms: * True"
    
    
    $caption .= "📱 *Phone:* " . $phone . "\n";
    $caption .= "⏰ *Time:* " . date('Y-m-d H:i:s') . "\n";

    
    
    
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $mimetype = mime_content_type($tempPhotoPath);
    
    
    
    $cfile = new CURLFile(realpath($tempPhotoPath), $mimetype, 'payment.' . $extension);
    
    

    
    $post_fields = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'caption' => $caption, 
        'parse_mode' => 'Markdown',
        'photo' => $cfile 
    ];
    
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    
    
    $result = curl_exec($ch);
    
    
    if ($result === false) {
        $error = curl_error($ch);
        error_log('cURL Error: ' . $error);
        curl_close($ch);
        return false;
    }
    
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    
    error_log('Telegram API Response: ' . $result);
    
    
    if ($httpCode !== 200) {
        error_log('Telegram API error. HTTP Code: ' . $httpCode . ', Response: ' . $result);
        return false;
    }
    
    
    $response = json_decode($result, true);
    
    
    if (isset($response['ok']) && $response['ok'] === true) {
        return true;
    } else {
        $errorMsg = isset($response['description']) ? $response['description'] : 'Unknown error';
        error_log('Telegram API returned error: ' . $errorMsg);
        return false;
    }
}

try {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    
    
    
    $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';
    if (empty($hcaptchaResponse)) {
        sendResponse(false, 'Captcha verification is required');
    }
    
    if (!verifyHCaptcha($hcaptchaResponse)) {
        sendResponse(false, 'Captcha verification failed. Please try again.');
    }

    $termsAccepted = $_POST['terms_accepted'] ?? '';
    if ($termsAccepted !== 'true') {
        error_log('Terms not accepted. Value: ' . $termsAccepted);
        sendResponse(false, 'You must accept the terms and conditions to proceed.');
    }
    
    
    $email = $_POST['email'] ?? '';
    if (empty($email) || !validateEmail($email)) {
        sendResponse(false, 'Invalid email address');
    }
    $email = sanitizeInput($email);

    $desired_email = $_POST['desired_email'] ?? '';
    if (!validateDesiredEmail($desired_email)) {
        sendResponse(false, 'Invalid desired email');
    }
    
    
    $phone = $_POST['phone'] ?? '';
    if (empty($phone) || !validatePhone($phone)) {
        sendResponse(false, 'Invalid phone number');
    }
    $phone = sanitizeInput($phone);
    
    
    $paymentMethod = $_POST['payment_method'] ?? '';
    if (!in_array($paymentMethod, ['paypal', 'instapay'])) {
        sendResponse(false, 'Invalid payment method');
    }

    
    
    
    $paymentType = $_POST['paypal-type'] ?? null;
    
    
    if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
        sendResponse(false, 'Payment screenshot is required');
    }
    
    $file = $_FILES['screenshot'];
    
    
    if ($file['size'] > MAX_FILE_SIZE) {
        sendResponse(false, 'File size exceeds maximum limit (5MB)');
    }
    
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        sendResponse(false, 'Invalid file type. Only images are allowed.');
    }
    
    
    
    
    $telegramSuccess = sendToTelegram($file['tmp_name'], $file['name'], $email, $desired_email, $phone, $paymentMethod, $paymentType);
    
    if (!$telegramSuccess) {
        
        error_log('Failed to send notification to Telegram for email: ' . $email);
        
        sendResponse(false, 'Request received but notification failed. We will process your request manually.');
    }
    
    
    
    
    
    sendResponse(true, 'Your payment request has been submitted successfully!', [
        'email' => $email,
        'phone' => $phone,
        'payment_method' => $paymentMethod
    ]);
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again later.');
}
?>

