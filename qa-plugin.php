<?php
/*
Plugin Name: OTP Verification for Registration
Plugin URI: https://github.com/
Plugin Description: OTP verification with email before user registration.
Plugin Version: 1.0
Plugin Date: 2025-06-21
Plugin Author: Aman
Plugin License: GPLv2
*/

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('page', 'q2a-gmail-register.php', 'q2a_gmail_register', 'Gmail Register');
qa_register_plugin_overrides('q2a-gmail-redirect.php');



/*
	Omit PHP closing tag to help avoid accidental output
*/