<?php
class q2a_gmail_register
{
    public function match_request($request)
    {
        return $request === 'e-register';
    }

    public function process_request($request)
    {
        require_once QA_INCLUDE_DIR . 'app/users-edit.php';
        require_once QA_INCLUDE_DIR . 'app/captcha.php';
        require_once QA_INCLUDE_DIR . 'app/limits.php';
        require_once QA_INCLUDE_DIR . 'db/users.php';
        require_once QA_INCLUDE_DIR . 'app/emails.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $status_msg = '';
        $errors = [];

        // Expire email verification after 3 minutes
        if (isset($_SESSION['email_verified']) && isset($_SESSION['email_verified_time'])) {
            if (time() - $_SESSION['email_verified_time'] > 180) {
                unset($_SESSION['email_verified']);
                unset($_SESSION['email_verified_time']);
                unset($_SESSION['email_code']);
                unset($_SESSION['verify_email']);
                unset($_SESSION['email_sent']);
                $status_msg = 'Time over ! Please input email again';                
            }
        }

        $content = qa_content_prepare();
        $content['title'] = 'New user Registration';

        ob_start();

        // Registration logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
            $handle = qa_post_text('handle');
            $email = qa_post_text('email');
            $password1 = qa_post_text('password');
            $password2 = qa_post_text('password2');

            if (!qa_check_form_security_code('register', qa_post_text('code'))) {
                $status_msg = '❌ Email code wrong !';
            } elseif ($password1 !== $password2) {
                $status_msg = '❌ Password does not match !';
            } else {
                if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
                    $errors['email'] = 'Only Gmail is acceptable.';
                }

                if (!isset($_POST['terms'])) {
                    $errors['terms'] = 'You have to be agreed to Terms.';
                }

                $errors = array_merge(
                    $errors,
                    qa_handle_email_filter($handle, $email),
                    qa_password_validate($password1)
                );

                if (empty($errors)) {
                    $userid = qa_create_new_user($email, $password1, $handle);
                    
                    //set flags to 1 and clear emailcode
                    qa_db_query_sub('UPDATE ^users SET flags = #, emailcode = "" WHERE userid = #', 1, $userid);

                    qa_set_logged_in_user($userid, $handle);
                    session_destroy();
                    qa_redirect('');
                }
            }
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

                $subject = 'Your Email Code';
                $body = "Your verification code: $code";

                $params = [
                    'fromemail' => qa_opt('from_email'),
                    'fromname' => qa_opt('site_title'),
                    'toemail' => $email,
                    'toname' => '',
                    'subject' => $subject,
                    'body' => $body,
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

        // Verify submitted code
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
            if ($_POST['email_code'] == ($_SESSION['email_code'] ?? '')) {
                $_SESSION['email_verified'] = true;
                $_SESSION['email_verified_time'] = time();
                $status_msg = 'Email verified successfully !';
            } else {
                $status_msg = '❌ Your code is wrong !';
            }
        }
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

<?php if (!isset($_SESSION['email_verified'])) : ?>

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

<?php else : ?>

    <p style="color: red;">Fill up the form carefully and Register.</p>

    <form method="post">
        <label for="handle">User Name:</label>
        <input type="text" name="handle" class="qa-form-tall-text" required>

        <label for="email">Email:</label>
        <input type="email" name="email" class="qa-form-tall-text" readonly value="<?= qa_html($_SESSION['verify_email']) ?>">

        <label for="password">Password:</label>
        <input type="password" name="password" class="qa-form-tall-text" required>

        <label for="password2">Password again:</label>
        <input type="password" name="password2" class="qa-form-tall-text" required>

        <div style="margin-bottom: 10px;">
            <label><input type="checkbox" name="terms"> <?= qa_opt('register_terms') ?></label>
        </div>

        <input type="hidden" name="register_user" value="1">
        <input type="hidden" name="code" value="<?= qa_get_form_security_code('register') ?>">

        <input type="submit" value="Register" class="qa-form-tall-button">
    </form>

<?php endif; ?>

<?php if (!empty($errors)) : ?>
    <ul style="color: red;">
        <?php foreach ($errors as $err) : ?>
            <li><?= qa_html($err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
        $content['custom'] = ob_get_clean();
        return $content;
    }
}
