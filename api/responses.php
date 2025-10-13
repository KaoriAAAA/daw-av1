<?php
session_start();
header('Content-Type: application/json');

$responsesFile = __DIR__ . '/../respostas.txt';
$questionsFile = __DIR__ . '/../perguntas.txt';

function getAllQuestions() {
    global $questionsFile;
    $rows = @file($questionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $qs = [];
    foreach ($rows as $i => $r) {
        if ($i === 0) continue;
        $c = explode(';', $r);
        while (count($c) < 7) $c[] = '';
        $qs[] = [
            'enunciado' => trim($c[0]),
            'tipo' => trim($c[1]),
            'opA' => trim($c[2]),
            'opB' => trim($c[3]),
            'opC' => trim($c[4]),
            'opD' => trim($c[5]),
            'opcaocerta' => trim($c[6])
        ];
    }
    return $qs;
}

function appendResponse($username, $enunciado, $resposta) {
    global $responsesFile;
    $timestamp = date('Y-m-d H:i:s');
    $safe = str_replace(["\n", "\r", ";"], [' ', ' ', ' '], $resposta);
    file_put_contents($responsesFile, "{$username};{$enunciado};{$safe};{$timestamp}\n", FILE_APPEND);
}

function getResponsesOfUser($username) {
    global $responsesFile;
    $rows = @file($responsesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $out = [];
    foreach ($rows as $r) {
        $c = explode(';', $r);
        if (count($c) < 4) continue;
        if (trim($c[0]) === $username) {
            $out[] = [
                'username' => $c[0],
                'enunciado' => $c[1],
                'resposta' => $c[2],
                'data' => $c[3]
            ];
        }
    }
    return $out;
}

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $enunciado = $input['enunciado'] ?? '';
    $resposta = $input['resposta'] ?? '';
    $qs = getAllQuestions();
    $found = false;
    foreach ($qs as $q) {
        if ($q['enunciado'] === $enunciado) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        http_response_code(400);
        echo json_encode(['error' => 'Pergunta não encontrada']);
        exit;
    }
    appendResponse($_SESSION['username'], $enunciado, $resposta);
    echo json_encode(['success' => true]);
}
elseif ($method === 'GET') {
    $user = $_SESSION['username'];
    if (isset($_GET['user'])) {
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }
        $user = $_GET['user'];
    }
    $res = getResponsesOfUser($user);
    echo json_encode($res);
}
else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
