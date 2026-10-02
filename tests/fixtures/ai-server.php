<?php

declare(strict_types=1);

// router for `php -S`: an OpenAI-compatible chat endpoint that echoes the request back as the message
$request = json_decode((string) file_get_contents('php://input'), true);
header('Content-Type: application/json');

if (($request['model'] ?? '') === 'limited') {
    http_response_code(429);
    echo json_encode(['error' => ['message' => 'Quota exceeded']]);

    return;
}

echo json_encode([
    'id'      => 'chat-1',
    'model'   => $request['model'],
    'choices' => [[
        'message'       => ['role' => 'assistant', 'content' => json_encode([
            'path'          => $_SERVER['REQUEST_URI'],
            'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            'request'       => $request,
        ])],
        'finish_reason' => 'stop',
    ]],
    'usage'   => ['prompt_tokens' => 11, 'completion_tokens' => 4, 'total_tokens' => 25],
]);
