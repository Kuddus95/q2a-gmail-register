<?php
function qa_get_request_content()
{
    $request = qa_request();

    if ($request === 'register') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $ok = !empty($_SESSION['email_verified'])
            && !empty($_SESSION['verify_email'])
            && (time() - ($_SESSION['email_verified_time'] ?? 0) <= 300);

        if (!$ok) {
            qa_redirect('e-register');
        }
    }

    return qa_get_request_content_base();
}
