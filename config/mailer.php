<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Composer autoload or manual install fallback
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

function send_verification_email($to_email, $code) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dacayanan.leomarbsis2023@gmail.com';
        $mail->Password   = 'ntjfbpajmcyzlqtg'; // <- palitan mo ng bagong app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 30;

        $mail->setFrom('dacayanan.leomarbsis2023@gmail.com', 'ScholarLink');
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = 'ScholarLink - Email Verification Code';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:30px;border:1px solid #e0e0e0;border-radius:12px'>
              <h2 style='color:#166534;margin-bottom:6px'>ScholarLink</h2>
              <p style='color:#444;margin-bottom:20px'>Use the verification code below to activate your account.</p>
              <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:24px;text-align:center;margin-bottom:20px'>
                <p style='color:#166534;font-size:13px;margin-bottom:8px;text-transform:uppercase;letter-spacing:2px'>Your Verification Code</p>
                <h1 style='letter-spacing:12px;color:#15803d;font-size:40px;margin:0'>{$code}</h1>
              </div>
              <p style='color:#666;font-size:14px'>This code expires in <strong>10 minutes</strong>. Do not share this code with anyone.</p>
              <p style='color:#999;font-size:12px;margin-top:20px'>If you did not register on ScholarLink, you can safely ignore this email.</p>
            </div>
        ";

        $mail->send();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}