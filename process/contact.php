<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function respond(bool $success, string $message, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

function getBody(array $data, string $template = __DIR__ . '/templates/contact.html'): string
{
    $html = file_get_contents($template);
    $clean = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

    return strtr($html, [
        '{{name}}'    => $clean($data['name'] ?? ''),
        '{{email}}'   => $clean($data['email'] ?? ''),
        '{{message}}' => nl2br($clean($data['message'] ?? '')),
    ]);
}

function newMailer(array $config): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config['smtp']['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp']['username'];
    $mail->Password   = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['encryption'];
    $mail->Port       = $config['smtp']['port'];
    $mail->CharSet    = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom($config['contact']['from_email'], $config['contact']['from_name']);

    return $mail;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Método não permitido.', 405);
}

// Honeypot: a hidden field real visitors never fill in. If it's set, silently
// pretend success instead of telling the bot what tripped it.
if (!empty($_POST['website'])) {
    respond(true, 'Obrigado!');
}

$name    = trim((string) ($_POST['name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    respond(false, 'Por favor preencha todos os campos.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Por favor introduza um email válido.', 422);
}

try {
    $notification = newMailer($config);
    $notification->addAddress($config['contact']['to_email'], $config['contact']['to_name']);
    $notification->addReplyTo($email, $name);
    $notification->Subject = "Novo contacto de {$name}";
    $notification->Body    = getBody($_POST, __DIR__ . '/templates/contact.html');
    $notification->send();

    $reply = newMailer($config);
    $reply->addAddress($email, $name);
    $reply->Subject = 'Recebemos a sua mensagem — Buscardini';
    $reply->Body    = getBody($_POST, __DIR__ . '/templates/contact-reply.html');
    $reply->send();

    respond(true, 'A sua mensagem foi enviada com sucesso.');
} catch (PHPMailerException $e) {
    error_log('Contact form mail error: ' . $e->getMessage());
    respond(false, 'Ocorreu um erro ao enviar a sua mensagem. Por favor tente novamente mais tarde.', 500);
}
