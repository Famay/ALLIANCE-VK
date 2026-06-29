<?php
require_once __DIR__ . '/send-config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$name       = trim(strip_tags($_POST['name']    ?? ''));
$phone      = trim(strip_tags($_POST['phone']   ?? ''));
$message    = trim(strip_tags($_POST['message'] ?? ''));
$source     = ($_POST['source'] ?? '') === 'chatbot' ? 'chatbot' : 'form';
$categories = $_POST['category'] ?? [];
if (is_array($categories)) {
    $categories = implode(', ', array_map('strip_tags', $categories));
}

if (!$name || !$phone) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Заполните имя и телефон']);
    exit;
}

$label = $source === 'chatbot' ? '🤖 Чат-бот' : '📋 Форма сайта';
$lines = [
    "📩 *Новая заявка* — {$label}",
    "",
    "👤 Имя: {$name}",
    "📞 Телефон: {$phone}",
];
if ($categories) $lines[] = "🪑 Категория: {$categories}";
if ($message)    $lines[] = "💬 {$message}";
$lines[] = "";
$lines[] = "🕐 " . date('d.m.Y H:i') . " (мск)";
$text = implode("\n", $lines);

$errors = [];

// Telegram
$tgRes = @file_get_contents(
    "https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage",
    false,
    stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query(['chat_id' => TG_CHAT_ID, 'text' => $text, 'parse_mode' => 'Markdown']),
        'timeout' => 5,
    ]])
);
if (!$tgRes || !(json_decode($tgRes)->ok ?? false)) {
    $errors[] = 'telegram';
}

// Email
$subj    = "=?UTF-8?B?" . base64_encode("Новая заявка: {$name}") . "?=";
$headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
if (!mail(MAIL_TO, $subj, $text, $headers)) {
    $errors[] = 'email';
}

echo json_encode(['success' => true, 'errors' => $errors]);
