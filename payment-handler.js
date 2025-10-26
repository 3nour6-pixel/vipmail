

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    const errorText = document.getElementById('error-message-text');
    errorText.textContent = message;
    errorDiv.style.display = 'block';
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

function hideError() {
    const errorDiv = document.getElementById('error-message');
    errorDiv.style.display = 'none';
}

function validateForm(formData, formElement) {
    const email = formData.get('email');
    const desiredEmail = formData.get('desired_email');
    const phone = formData.get('phone');
    const screenshot = formData.get('screenshot');
    
    
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!email || !emailRegex.test(email)) {
        showError(currentLang === 'ar' ? 'البريد الإلكتروني غير صالح' : 'Invalid email address');
        return false;
    }
    
    
    const desiredEmailRegex = /^[a-z0-9._\-]+$/;
    if (!desiredEmail || !desiredEmailRegex.test(desiredEmail)) {
        showError(currentLang === 'ar' ? 'عنوان البريد المطلوب غير صالح. استخدم حروف صغيرة وأرقام ونقاط وشرطات فقط' : 'Invalid desired email. Use only lowercase letters, numbers, dots, underscores and hyphens');
        return false;
    }
    
    
    if (desiredEmail.length < 3) {
        showError(currentLang === 'ar' ? 'عنوان البريد يجب أن يكون 3 أحرف على الأقل' : 'Email address must be at least 3 characters');
        return false;
    }
    
    
    if (desiredEmail.length > 64) {
        showError(currentLang === 'ar' ? 'عنوان البريد طويل جداً (الحد الأقصى 64 حرف)' : 'Email address too long (max 64 characters)');
        return false;
    }
    
    
    if (desiredEmail.includes('..')) {
        showError(currentLang === 'ar' ? 'لا يمكن استخدام نقطتين متتاليتين' : 'Consecutive dots are not allowed');
        return false;
    }
    
    
    if (/^[.-]|[.-]$/.test(desiredEmail)) {
        showError(currentLang === 'ar' ? 'لا يمكن أن يبدأ أو ينتهي البريد بنقطة أو شرطة' : 'Email cannot start or end with dot or hyphen');
        return false;
    }
    
    
    if (!phone || phone.length < 10) {
        showError(currentLang === 'ar' ? 'رقم الهاتف غير صالح' : 'Invalid phone number');
        return false;
    }
    
    
    if (!screenshot || screenshot.size === 0) {
        showError(currentLang === 'ar' ? 'يرجى رفع صورة التحويل' : 'Please upload payment screenshot');
        return false;
    }
    
    
    if (screenshot.size > 5 * 1024 * 1024) {
        showError(currentLang === 'ar' ? 'حجم الصورة كبير جداً (الحد الأقصى 5 ميجابايت)' : 'Image size too large (max 5MB)');
        return false;
    }
    
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(screenshot.type)) {
        showError(currentLang === 'ar' ? 'نوع الملف غير مدعوم. يرجى رفع صورة' : 'Unsupported file type. Please upload an image');
        return false;
    }
    
    
    const termsCheckbox = formElement.querySelector('input[name="terms_accepted"]');
    if (!termsCheckbox || !termsCheckbox.checked) {
        
        const checkboxLabel = formElement.querySelector('.terms-checkbox');
        if (checkboxLabel) {
            checkboxLabel.classList.add('error');
            setTimeout(() => {
                checkboxLabel.classList.remove('error');
            }, 500);
        }
        
        showError(currentLang === 'ar' ? 'يجب الموافقة على الشروط والأحكام' : 'You must accept the terms and conditions');
        
        
        if (termsCheckbox) {
            termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        return false;
    }
    
    return true;
}

async function handleFormSubmit(event, formId) {
    event.preventDefault();
    hideError();
    
    const form = event.target;
    const submitBtn = form.querySelector('.btn-submit');
    const originalBtnText = submitBtn.textContent;
    
    
    const hcaptchaResponse = form.querySelector('.h-captcha textarea')?.value;
    
    if (!hcaptchaResponse) {
        showError(currentLang === 'ar' ? 'يرجى التحقق من أنك لست روبوت' : 'Please complete the captcha verification');
        return;
    }
    
    
    const formData = new FormData(form);
    formData.append('h-captcha-response', hcaptchaResponse);
    
    
    if (!validateForm(formData, form)) {
        return;
    }
    
    
    const termsAccepted = form.querySelector('input[name="terms_accepted"]').checked;
    formData.append('terms_accepted', termsAccepted ? 'true' : 'false');
    
    
    submitBtn.disabled = true;
    submitBtn.textContent = currentLang === 'ar' ? 'جاري الإرسال...' : 'Submitting...';
    submitBtn.style.opacity = '0.6';
    submitBtn.style.cursor = 'not-allowed';
    
    try {
        
        const response = await fetch('api/apiv1.php', {
            method: 'POST',
            body: formData
        });
        
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        
        const responseText = await response.text();
        
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Response is not JSON:', responseText);
            throw new Error('Invalid response from server');
        }
        
        if (result.success) {
            
            document.getElementById(formId).style.display = 'none';
            
            
            const successMessage = document.getElementById('success-message');
            successMessage.style.display = 'block';
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            
            form.reset();
            if (typeof hcaptcha !== 'undefined') {
                hcaptcha.reset();
            }
        } else {
            
            showError(result.message || (currentLang === 'ar' ? 'حدث خطأ. يرجى المحاولة مرة أخرى.' : 'An error occurred. Please try again.'));
            
            
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
            
            
            if (typeof hcaptcha !== 'undefined') {
                hcaptcha.reset();
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showError(currentLang === 'ar' ? 'حدث خطأ في الاتصال. يرجى التحقق من الإنترنت والمحاولة مرة أخرى.' : 'Connection error. Please check your internet and try again.');
        
        
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        
        
        if (typeof hcaptcha !== 'undefined') {
            hcaptcha.reset();
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    const paypalForm = document.getElementById('paypal-booking-form');
    if (paypalForm) {
        paypalForm.addEventListener('submit', function(e) {
            handleFormSubmit(e, 'paypal-form');
        });
    }
    
    
    const instapayForm = document.getElementById('instapay-booking-form');
    if (instapayForm) {
        instapayForm.addEventListener('submit', function(e) {
            handleFormSubmit(e, 'instapay-form');
        });
    }
    
    
    const desiredEmailInputs = document.querySelectorAll('input[name="desired_email"]');
    desiredEmailInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = e.target.value.toLowerCase();
        });
    });
});

