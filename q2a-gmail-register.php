<?php
class q2a_gmail_register
{
    public function match_request($request)
    {
        return $request === 'e-register';
    }

    public function process_request($request)
    {
        require_once QA_INCLUDE_DIR . 'app/emails.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $status_msg = '';

        // Expire email verification after 5 minutes
        if (isset($_SESSION['email_verified']) && isset($_SESSION['email_verified_time'])) {
            if (time() - $_SESSION['email_verified_time'] > 300) {
                unset($_SESSION['email_verified']);
                unset($_SESSION['email_verified_time']);
                unset($_SESSION['email_code']);
                unset($_SESSION['verify_email']);
                unset($_SESSION['email_sent']);
                unset($_SESSION['email_code_time']);
                $status_msg = 'Time over ! Please input email again';
            }
        }

        // Expire sent code after 5 minutes if not verified
        if (isset($_SESSION['email_sent']) && !isset($_SESSION['email_verified'])) {
            if (time() - ($_SESSION['email_code_time'] ?? 0) > 300) {
                unset($_SESSION['email_code']);
                unset($_SESSION['verify_email']);
                unset($_SESSION['email_sent']);
                unset($_SESSION['email_code_time']);
                $status_msg = 'Time over ! Please input email again';
            }
        }

        // Already verified -> go to the main register page
        if (!empty($_SESSION['email_verified'])) {
            qa_redirect('register');
        }

        // Send verification code
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
            $email = trim($_POST['email']);
            if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
                $status_msg = 'Only Gmail address is acceptable.';
            } else {
                $code = rand(100000, 999999);
                $_SESSION['email_code'] = $code;
                $_SESSION['verify_email'] = $email;
                $_SESSION['email_code_time'] = time();

                $params = [
                    'fromemail' => qa_opt('from_email'),
                    'fromname' => qa_opt('site_title'),
                    'toemail' => $email,
                    'toname' => '',
                    'subject' => 'Your Email Code',
                    'body' => "Your verification code: $code. Please use this code for new user registration immediately.",
                    'html' => false,
                ];

                if (qa_send_email($params)) {
                    $_SESSION['email_sent'] = true;
                    $status_msg = '✅ Verification code has been sent successfully !';
                } else {
                    $status_msg = '❌ Failed to send code.';
                }
            }
        }

        // Verify submitted code -> redirect to the main register page
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
            if ($_POST['email_code'] == ($_SESSION['email_code'] ?? '')) {
                $_SESSION['email_verified'] = true;
                $_SESSION['email_verified_time'] = time();
                qa_redirect('register');
            } else {
                $status_msg = '❌ Your code is wrong !';
            }
        }

        $content = qa_content_prepare();
        $content['title'] = 'New user Registration';

        ob_start();
?>

<style>
    .qa-form-tall-text {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
    }
    .qa-form-tall-button {
        padding: 10px 15px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    .qa-form-tall-button:hover {
        background: #218838;
    }
</style>

<?php if (!empty($status_msg)) : ?>
    <p style="color: green; font-weight: bold;"><?= qa_html($status_msg) ?></p>
<?php endif; ?>

<?php if (!isset($_SESSION['email_sent'])) : ?>
    <form method="post">
        <input type="email" name="email" class="qa-form-tall-text" placeholder="Write your Gmail" required>
        <button type="submit" name="send_email" class="qa-form-tall-button">Send Code</button>
    </form>
<?php else : ?>
    <form method="post">
        <input type="text" name="email_code" class="qa-form-tall-text" placeholder="Write email code" maxlength="6" required>
        <button type="submit" name="verify_code" class="qa-form-tall-button">Verify</button>
    </form>
<?php endif; ?>

<?php
        $content['custom'] = ob_get_clean();
        return $content;
    }
}
