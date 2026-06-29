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
]);
$tgRes = curl_exec($ch);
$tgErr = curl_error($ch);
curl_close($ch);
$tgOk = $tgRes && (json_decode($tgRes)->ok ?? false);
$results['telegram'] = $tgOk ? 'ok' : 'fail: ' . ($tgErr ?: $tgRes);

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
]);
$vkRes  = curl_exec($ch);
$vkErr  = curl_error($ch);
curl_close($ch);
$vkOk = $vkRes && isset(json_decode($vkRes, true)['response']);
$results['vk'] = $vkOk ? 'ok' : 'fail: ' . ($vkErr ?: $vkRes);

// --- Email via SMTP ---
function smtp_send(string $host, string $user, string $pass, string $from, string $to, string $subject, string $body): string {
    $sock = @stream_socket_client("ssl://{$host}:465", $errno, $errstr, 10);
    if (!$sock) return "connect failed: {$errstr}";
    stream_set_timeout($sock, 10);

    $read = function () use ($sock): string {
        $out = '';
        while ($line = fgets($sock, 512)) {
            $out .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $out;
    };
    $write = fn(string $s) => fwrite($sock, $s . "\r\n");

    $read();
    $write("EHLO localhost"); $read();
    $write("AUTH LOGIN"); $read();
    $write(base64_encode($user)); $read();
    $write(base64_encode($pass)); $r = $read();
    if (strpos($r, '235') === false) { fclose($sock); return "auth failed: {$r}"; }

    $write("MAIL FROM:<{$from}>"); $read();
    $write("RCPT TO:<{$to}>"); $read();
    $write("DATA"); $read();

    $msg = "From: =?UTF-8?B?" . base64_encode("Альянс ВК") . "?= <{$from}>\r\n"
         . "To: {$to}\r\n"
         . "Subject: =?UTF-8?B?" . base64_encode("Новая заявка: {$subject}") . "?=\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "Content-Transfer-Encoding: base64\r\n"
         . "\r\n"
         . chunk_split(base64_encode($body))
         . "\r\n.";
    $write($msg); $r = $read();
    $write("QUIT");
    fclose($sock);
    return strpos($r, '250') !== false ? 'ok' : "send failed: {$r}";
}

$mailResult = smtp_send(MAIL_HOST, MAIL_FROM, MAIL_PASS, MAIL_FROM, MAIL_TO, "{$name} {$phone}", $text);
$results['email'] = $mailResult;

// Debug log (удалить после проверки)
@file_put_contents(
    __DIR__ . '/../send_debug.log',
    date('Y-m-d H:i:s') . " | {$name} {$phone} [{$source}] | " . json_encode($results) . "\n",
    FILE_APPEND
);

echo json_encode(['success' => $tgOk || $vkOk, 'results' => $results]);
