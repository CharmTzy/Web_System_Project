#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Repositories\ChatRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\ChatService;
use App\Support\Database;

require dirname(__DIR__) . '/app/Support/helpers.php';

load_env(dirname(__DIR__) . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Singapore'));

$config = [
    'app' => require dirname(__DIR__) . '/config/app.php',
    'database' => require dirname(__DIR__) . '/config/database.php',
];

$database = new Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    fwrite(STDERR, "Unable to connect to the database for chat websocket server.\n");
    exit(1);
}

$chatService = new ChatService(
    new ChatRepository($connection),
    new ProductRepository($connection),
    new UserRepository($connection),
);

$host = (string) ($config['app']['chat_server_host'] ?? '0.0.0.0');
$port = (int) ($config['app']['chat_server_port'] ?? 8080);
$path = '/' . ltrim((string) ($config['app']['chat_websocket_path'] ?? '/ws/chat'), '/');
$server = @stream_socket_server("tcp://{$host}:{$port}", $errorCode, $errorMessage);

if (!$server) {
    fwrite(STDERR, sprintf("Unable to start websocket server on %s:%d (%s).\n", $host, $port, $errorMessage));
    exit(1);
}

stream_set_blocking($server, false);

fwrite(STDOUT, sprintf("NovaMarket chat websocket server listening on %s:%d%s\n", $host, $port, $path));

$clients = [];

while (true) {
    $readSockets = [$server];

    foreach ($clients as $client) {
        $readSockets[] = $client['socket'];
    }

    $write = null;
    $except = null;
    $changed = @stream_select($readSockets, $write, $except, 1);

    if ($changed === false) {
        continue;
    }

    foreach ($readSockets as $socket) {
        if ($socket === $server) {
            $clientSocket = @stream_socket_accept($server, 0);

            if ($clientSocket) {
                stream_set_blocking($clientSocket, false);
                $clients[(int) $clientSocket] = [
                    'socket' => $clientSocket,
                    'buffer' => '',
                    'handshake' => false,
                    'user' => null,
                    'subscriptions' => [],
                ];
            }

            continue;
        }

        $clientId = (int) $socket;

        if (!isset($clients[$clientId])) {
            continue;
        }

        $chunk = @fread($socket, 8192);

        if (($chunk === '' && feof($socket)) || $chunk === false) {
            disconnectClient($clients, $clientId);
            continue;
        }

        $clients[$clientId]['buffer'] .= $chunk;

        if (!$clients[$clientId]['handshake']) {
            if (!str_contains($clients[$clientId]['buffer'], "\r\n\r\n")) {
                continue;
            }

            if (!performHandshake($clients, $clientId, $path, $chatService)) {
                disconnectClient($clients, $clientId);
            }

            continue;
        }

        while ($frame = popWebSocketFrame($clients[$clientId]['buffer'])) {
            if (($frame['opcode'] ?? 0) === 0x8) {
                disconnectClient($clients, $clientId);
                continue 2;
            }

            if (($frame['opcode'] ?? 0) === 0x9) {
                sendFrame($socket, $frame['payload'], 0xA);
                continue;
            }

            if (($frame['opcode'] ?? 0) !== 0x1) {
                continue;
            }

            handleClientMessage($clients, $clientId, (string) $frame['payload'], $chatService);
        }
    }
}

function performHandshake(array &$clients, int $clientId, string $expectedPath, ChatService $chatService): bool
{
    $socket = $clients[$clientId]['socket'];
    $request = $clients[$clientId]['buffer'];
    $clients[$clientId]['buffer'] = '';
    $lines = preg_split("/\r\n/", trim($request)) ?: [];
    $requestLine = array_shift($lines);

    if (!$requestLine || !preg_match('#^GET\s+(\S+)#', $requestLine, $matches)) {
        sendHttpError($socket, 400, 'Bad Request');
        return false;
    }

    $requestPath = parse_url((string) $matches[1], PHP_URL_PATH) ?: '/';

    if ($requestPath !== $expectedPath) {
        sendHttpError($socket, 404, 'Not Found');
        return false;
    }

    $headers = [];

    foreach ($lines as $line) {
        if (!str_contains($line, ':')) {
            continue;
        }

        [$name, $value] = explode(':', $line, 2);
        $headers[strtolower(trim($name))] = trim($value);
    }

    $key = $headers['sec-websocket-key'] ?? null;

    if (!is_string($key) || $key === '') {
        sendHttpError($socket, 400, 'Missing WebSocket Key');
        return false;
    }

    $user = authenticateChatUser((string) ($headers['cookie'] ?? ''));

    if ($user === null) {
        sendHttpError($socket, 401, 'Unauthorized');
        return false;
    }

    $accept = base64_encode(
        sha1(trim($key) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)
    );

    $response = implode("\r\n", [
        'HTTP/1.1 101 Switching Protocols',
        'Upgrade: websocket',
        'Connection: Upgrade',
        'Sec-WebSocket-Accept: ' . $accept,
        "\r\n",
    ]);

    @fwrite($socket, $response);

    $clients[$clientId]['handshake'] = true;
    $clients[$clientId]['user'] = $user;

    sendJson($socket, [
        'type' => 'hello',
        'user' => $user,
        'unreadTotal' => $chatService->unreadCount((int) $user['id'], (string) $user['role']),
    ]);

    return true;
}

function authenticateChatUser(string $cookieHeader): ?array
{
    $cookies = [];

    foreach (explode(';', $cookieHeader) as $cookiePair) {
        if (!str_contains($cookiePair, '=')) {
            continue;
        }

        [$name, $value] = explode('=', trim($cookiePair), 2);
        $cookies[$name] = urldecode($value);
    }

    $sessionName = session_name();
    $sessionId = $cookies[$sessionName] ?? null;

    if (!is_string($sessionId) || $sessionId === '') {
        return null;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $_SESSION = [];
    session_id($sessionId);
    session_start(['read_and_close' => true]);

    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['user_role'] ?? null;
    $name = $_SESSION['user_name'] ?? null;

    session_write_close();
    $_SESSION = [];
    session_id('');

    if (!$userId || !in_array($role, ['customer', 'seller', 'admin'], true)) {
        return null;
    }

    return [
        'id' => (int) $userId,
        'role' => (string) $role,
        'name' => (string) ($name ?: 'NovaMarket User'),
    ];
}

function handleClientMessage(array &$clients, int $clientId, string $json, ChatService $chatService): void
{
    $socket = $clients[$clientId]['socket'];
    $user = $clients[$clientId]['user'];
    $payload = json_decode($json, true);

    if (!is_array($payload)) {
        sendJson($socket, [
            'type' => 'error',
            'message' => 'Malformed chat payload.',
        ]);
        return;
    }

    $type = (string) ($payload['type'] ?? '');

    try {
        if ($type === 'subscribe') {
            $conversationId = (int) ($payload['conversationId'] ?? 0);

            if ($conversationId > 0) {
                $chatService->conversationForViewer($conversationId, (int) $user['id'], (string) $user['role']);
                $clients[$clientId]['subscriptions'][$conversationId] = true;
            }

            return;
        }

        if ($type === 'subscribe_many') {
            foreach ((array) ($payload['conversationIds'] ?? []) as $conversationId) {
                $conversationId = (int) $conversationId;

                if ($conversationId < 1) {
                    continue;
                }

                try {
                    $chatService->conversationForViewer($conversationId, (int) $user['id'], (string) $user['role']);
                    $clients[$clientId]['subscriptions'][$conversationId] = true;
                } catch (Throwable) {
                    continue;
                }
            }

            return;
        }

        if ($type === 'mark_read') {
            $conversationId = (int) ($payload['conversationId'] ?? 0);

            if ($conversationId > 0) {
                $chatService->markRead($conversationId, (int) $user['id'], (string) $user['role']);
                sendJson($socket, [
                    'type' => 'read_receipt',
                    'conversation_id' => $conversationId,
                    'unreadTotal' => $chatService->unreadCount((int) $user['id'], (string) $user['role']),
                ]);
            }

            return;
        }

        if ($type === 'send_message') {
            $conversationId = (int) ($payload['conversationId'] ?? 0);
            $body = (string) ($payload['body'] ?? '');
            $result = $chatService->sendMessage($conversationId, (int) $user['id'], (string) $user['role'], $body);

            broadcastConversationEvent($clients, $conversationId, [
                'type' => 'message_created',
                'conversation_id' => $conversationId,
                'message' => $result['message'],
            ]);

            return;
        }

        sendJson($socket, [
            'type' => 'error',
            'message' => 'Unknown chat event.',
        ]);
    } catch (Throwable $exception) {
        report_exception($exception, 'chat.websocket');
        sendJson($socket, [
            'type' => 'error',
            'message' => safe_exception_message($exception, 'Chat is temporarily unavailable.'),
        ]);
    }
}

function broadcastConversationEvent(array $clients, int $conversationId, array $payload): void
{
    foreach ($clients as $client) {
        if (empty($client['handshake']) || empty($client['subscriptions'][$conversationId])) {
            continue;
        }

        sendJson($client['socket'], $payload);
    }
}

function sendJson($socket, array $payload): void
{
    sendFrame($socket, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');
}

function sendFrame($socket, string $payload, int $opcode = 0x1): void
{
    $frameHead = [];
    $payloadLength = strlen($payload);
    $frameHead[0] = 0x80 | ($opcode & 0x0F);

    if ($payloadLength <= 125) {
        $frameHead[1] = $payloadLength;
    } elseif ($payloadLength <= 65535) {
        $frameHead[1] = 126;
        $frameHead[2] = ($payloadLength >> 8) & 0xFF;
        $frameHead[3] = $payloadLength & 0xFF;
    } else {
        $frameHead[1] = 127;
        for ($index = 7; $index >= 0; $index--) {
            $frameHead[$index + 2] = ($payloadLength >> ($index * 8)) & 0xFF;
        }
    }

    $frame = '';

    foreach ($frameHead as $byte) {
        $frame .= chr($byte);
    }

    $frame .= $payload;
    @fwrite($socket, $frame);
}

function popWebSocketFrame(string &$buffer): ?array
{
    $bufferLength = strlen($buffer);

    if ($bufferLength < 2) {
        return null;
    }

    $firstByte = ord($buffer[0]);
    $secondByte = ord($buffer[1]);
    $opcode = $firstByte & 0x0F;
    $isMasked = ($secondByte & 0x80) === 0x80;
    $payloadLength = $secondByte & 0x7F;
    $offset = 2;

    if ($payloadLength === 126) {
        if ($bufferLength < 4) {
            return null;
        }

        $payloadLength = unpack('nlength', substr($buffer, 2, 2))['length'];
        $offset = 4;
    } elseif ($payloadLength === 127) {
        if ($bufferLength < 10) {
            return null;
        }

        $parts = unpack('Nhigh/Nlow', substr($buffer, 2, 8));
        $payloadLength = ($parts['high'] << 32) | $parts['low'];
        $offset = 10;
    }

    $maskingKey = '';

    if ($isMasked) {
        if ($bufferLength < $offset + 4) {
            return null;
        }

        $maskingKey = substr($buffer, $offset, 4);
        $offset += 4;
    }

    if ($bufferLength < $offset + $payloadLength) {
        return null;
    }

    $payload = substr($buffer, $offset, $payloadLength);
    $buffer = substr($buffer, $offset + $payloadLength);

    if ($isMasked) {
        $unmasked = '';

        for ($index = 0; $index < $payloadLength; $index++) {
            $unmasked .= $payload[$index] ^ $maskingKey[$index % 4];
        }

        $payload = $unmasked;
    }

    return [
        'opcode' => $opcode,
        'payload' => $payload,
    ];
}

function sendHttpError($socket, int $statusCode, string $statusText): void
{
    $response = sprintf(
        "HTTP/1.1 %d %s\r\nContent-Length: 0\r\nConnection: close\r\n\r\n",
        $statusCode,
        $statusText
    );

    @fwrite($socket, $response);
}

function disconnectClient(array &$clients, int $clientId): void
{
    if (!isset($clients[$clientId])) {
        return;
    }

    @fclose($clients[$clientId]['socket']);
    unset($clients[$clientId]);
}
