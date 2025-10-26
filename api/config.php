<?php

define('HCAPTCHA_SITE_KEY', getenv('HCAPTCHA_SITE_KEY') ?: '');
define('HCAPTCHA_SECRET_KEY', getenv('HCAPTCHA_SECRET_KEY') ?: '');

define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

define('MAX_FILE_SIZE', 5 * 1024 * 1024); 
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 5);
define('RATE_LIMIT_TIME_WINDOW', 3600);

define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'support@vipm.org');
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@vipm.org');

define('ENVIRONMENT', getenv('VERCEL_ENV') ?: 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

define('PAYPAL_EMAIL', getenv('PAYPAL_EMAIL') ?: '3nour6@gmail.com');

define('INSTAPAY_ACCOUNT', getenv('INSTAPAY_ACCOUNT') ?: 'nourehabfaroukhassan@instapay');
define('INSTAPAY_PHONE', getenv('INSTAPAY_PHONE') ?: '+201158720470');

date_default_timezone_set('Africa/Cairo');

function validateConfig() {
    $errors = [];
    
    if (empty(HCAPTCHA_SECRET_KEY)) {
        $errors[] = 'HCAPTCHA_SECRET_KEY is not configured';
    }
    
    if (empty(TELEGRAM_BOT_TOKEN)) {
        $errors[] = 'TELEGRAM_BOT_TOKEN is not configured';
    }
    
    if (empty(TELEGRAM_CHAT_ID)) {
        $errors[] = 'TELEGRAM_CHAT_ID is not configured';
    }
    
    return $errors;
}

if (DEBUG_MODE) {
    $configErrors = validateConfig();
    if (!empty($configErrors)) {
        error_log("Configuration Errors: " . implode(", ", $configErrors));
    }
}
?>
