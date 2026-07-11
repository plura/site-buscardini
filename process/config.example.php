<?php
// Copy this file to config.php and fill in real values.
// config.php is gitignored and never committed.

return [

    // SMTP credentials used by PHPMailer to send the contact form email.
    'smtp' => [
        'host'       => '',
        'port'       => 587,
        'encryption' => 'tls', // 'tls' or 'ssl'
        'username'   => '',
        'password'   => '',
    ],

    // Where contact form submissions get sent.
    'contact' => [
        'to_email'   => '',
        'to_name'    => '',
        'from_email' => '', // usually must be a mailbox on the sending domain
        'from_name'  => 'Buscardini Website',
    ],

    // Mailchimp Marketing API.
    'mailchimp' => [
        'api_key' => '', // includes the datacenter suffix, e.g. abc123def-us21
        'list_id' => '', // Audience/List ID
    ],

];
