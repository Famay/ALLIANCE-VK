<?php
require_once __DIR__ . '/send-config.php';

echo "<pre>\n";
echo "=== CONFIG ===\n";
echo "TG_TOKEN:   " . substr(TG_TOKEN, 0, 12) . "...\n";
echo "TG_CHAT_ID: " . TG_CHAT_ID . "\n";
echo "VK_USER_ID: " . VK_USER_ID . "\n";
echo "VK_TOKEN:   " . substr(VK_TOKEN, 0, 12) . "...\n\n";

// Test Telegram
echo "=== TELEGRAM ===\n";
$ch = curl_init("https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['chat_id' => TG_CHAT_ID, 'text' => 'Тест заявки с сайта альянсвк.рф'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
echo "cURL error: " . ($err ?: 'нет') . "\n";
echo "Response:   " . $res . "\n\n";

// Test VK
echo "=== VK ===\n";
$ch = curl_init('https://api.vk.com/method/messages.send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'user_id'      => VK_USER_ID,
        'message'      => 'Тест заявки с сайта альянсвк.рф',
        'random_id'    => rand(1, 2147483647),
        'access_token' => VK_TOKEN,
        'v'            => '5.199',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
echo "cURL error: " . ($err ?: 'нет') . "\n";
echo "Response:   " . $res . "\n";
echo "</pre>\n";

// Удали этот файл после проверки!
