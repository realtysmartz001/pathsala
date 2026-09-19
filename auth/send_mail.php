<?php
function sendMail($toEmail, $toName, $subject, $body) {

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: RealtySmartz Pathshala <Admin@realtysmartzpathshala.in>" . "\r\n";
    $headers .= "Reply-To: Admin@realtysmartzpathshala.in" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $sent = mail($toEmail, $subject, $body, $headers);

    if (!$sent) {
        error_log("Mail failed to: " . $toEmail);
        return false;
    }

    return true;
}
?>
