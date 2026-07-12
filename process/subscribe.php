<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';
require __DIR__ . '/lang.php';

function respond(bool $success, string $message, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, t('Method not allowed.'), 405);
}

// The frontend posts a JSON body (Content-Type: application/json), not a
// form-encoded one, so $_POST is never populated — read php://input instead.
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Honeypot: a hidden field real visitors never fill in.
if (!empty($input['website'])) {
    respond(true, t('Thanks!'));
}

$email = trim((string) ($input['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, t('Please enter a valid email address.'), 422);
}

$apiKey = $config['mailchimp']['api_key'];
$listId = $config['mailchimp']['list_id'];

if (strpos($apiKey, '-') === false) {
    respond(false, t('Mailchimp is not configured correctly.'), 500);
}

[, $dataCenter] = explode('-', $apiKey);

$url = "https://{$dataCenter}.api.mailchimp.com/3.0/lists/{$listId}/members";

$payload = json_encode([
    'email_address' => $email,
    'status'        => 'pending', // triggers Mailchimp's double opt-in confirmation email
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('anystring:' . $apiKey),
    ],
    CURLOPT_TIMEOUT => 10,
]);

$response   = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log('Mailchimp subscribe cURL error: ' . $curlError);
    respond(false, t('Sorry, something went wrong. Please try again later.'), 500);
}

$data = json_decode($response, true);

if ($statusCode >= 200 && $statusCode < 300) {
    respond(true, t('Thanks for subscribing! Check your inbox to confirm.'));
}

// Mailchimp returns 400 with this title if the address is already on the list.
if (($data['title'] ?? '') === 'Member Exists') {
    respond(true, t("You're already subscribed!"));
}

// $data['detail'] is Mailchimp's own API error text (English, dynamic) — not
// something we can translate, so it only shows up if our own fallback misses.
error_log('Mailchimp subscribe error: ' . $response);
respond(false, $data['detail'] ?? t('Sorry, something went wrong. Please try again later.'), 500);
