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
    "📩 Новая заявка — {$label}",
    "",
    "👤 Имя: {$name}",
    "📞 Телефон: {$phone}",
];
if ($categories) $lines[] = "🪑 Категория: {$categories}";
if ($message)    $lines[] = "💬 {$message}";
$lines[] = "";
$lines[] = "🕐 " . date('d.m.Y H:i') . " (мск)";
$text = implode("\n", $lines);

$results = [];

// --- Telegram ---
$ch = curl_init("https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['chat_id' => TG_CHAT_ID, 'text' => $text],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$tgRes = curl_exec($ch);
$tgErr = curl_error($ch);
curl_close($ch);
$tgOk = $tgRes && (json_decode($tgRes)->ok ?? false);
$results['telegram'] = $tgOk ? 'ok' : ('fail: ' . ($tgErr ?: $tgRes));

// --- VK ---
$ch = curl_init('https://api.vk.com/method/messages.send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'user_id'      => VK_USER_ID,
        'message'      => $text,
        'random_id'    => rand(1, 2147483647),
        'access_token' => VK_TOKEN,
        'v'            => '5.199',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$vkRes = curl_exec($ch);
$vkErr = curl_error($ch);
curl_close($ch);
$vkData = json_decode($vkRes, true);
$vkOk   = isset($vkData['response']);
$results['vk'] = $vkOk ? 'ok' : ('fail: ' . ($vkErr ?: $vkRes));

// Debug log (удалить после проверки)
@file_put_contents(
    __DIR__ . '/../send_debug.log',
    date('Y-m-d H:i:s') . " | {$name} {$phone} [{$source}] | " . json_encode($results) . "\n",
    FILE_APPEND
);

echo json_encode(['success' => $tgOk || $vkOk, 'results' => $results]);
