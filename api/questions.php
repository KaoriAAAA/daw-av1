<?php
session_start();
header('Content-Type: application/json');

$questionsFile = __DIR__ . '/../perguntas.txt';

function getAllQuestions() {
    global $questionsFile;
    $rows = @file($questionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $qs = [];
    foreach ($rows as $i => $r) {
        if ($i === 0) continue;
        $c = explode(';', $r);
        while (count($c) < 7) {
            $c[] = '';
        }
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

function saveAllQuestions($qs) {
    global $questionsFile;
    $lines = ["enunciado;tipo;opcao A;opcao B;opcao C;opcao D;opcaocerta"];
    foreach ($qs as $q) {
        $lines[] = "{$q['enunciado']};{$q['tipo']};{$q['opA']};{$q['opB']};{$q['opC']};{$q['opD']};{$q['opcaocerta']}";
    }
    file_put_contents($questionsFile, implode("\n", $lines) . "\n");
}

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    echo json_encode(getAllQuestions());
}
elseif ($method === 'POST') {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $enunciado = trim($input['enunciado'] ?? '');
    $tipo = $input['tipo'] ?? '';
    $all = getAllQuestions();
    foreach ($all as $q) {
        if ($q['enunciado'] === $enunciado) {
            http_response_code(400);
            echo json_encode(['error' => 'Pergunta já existe']);
            exit;
        }
    }
    if ($tipo === 'Discursiva') {
        $newq = [
            'enunciado' => $enunciado,
            'tipo' => 'Discursiva',
            'opA' => '',
            'opB' => '',
            'opC' => '',
            'opD' => '',
            'opcaocerta' => ''
        ];
    } else {
        $newq = [
            'enunciado' => $enunciado,
            'tipo' => 'Multipla Escolha',
            'opA' => trim($input['opA'] ?? ''),
            'opB' => trim($input['opB'] ?? ''),
            'opC' => trim($input['opC'] ?? ''),
            'opD' => trim($input['opD'] ?? ''),
            'opcaocerta' => trim($input['opcaocerta'] ?? '')
        ];
    }
    $all[] = $newq;
    saveAllQuestions($all);
    echo json_encode(['success' => true]);
}
elseif ($method === 'PUT') {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $original = $input['original'] ?? '';
    $newEnunciado = trim($input['enunciado'] ?? '');
    $tipo = $input['tipo'] ?? '';
    $all = getAllQuestions();
    $newAll = [];
    foreach ($all as $q) {
        if ($q['enunciado'] === $original) {
            if ($tipo === 'Discursiva') {
                $newAll[] = [
                    'enunciado' => $newEnunciado,
                    'tipo' => 'Discursiva',
                    'opA' => '',
                    'opB' => '',
                    'opC' => '',
                    'opD' => '',
                    'opcaocerta' => ''
                ];
            } else {
                $newAll[] = [
                    'enunciado' => $newEnunciado,
                    'tipo' => 'Multipla Escolha',
                    'opA' => trim($input['opA'] ?? ''),
                    'opB' => trim($input['opB'] ?? ''),
                    'opC' => trim($input['opC'] ?? ''),
                    'opD' => trim($input['opD'] ?? ''),
                    'opcaocerta' => trim($input['opcaocerta'] ?? '')
                ];
            }
        } else {
            $newAll[] = $q;
        }
    }
    saveAllQuestions($newAll);
    echo json_encode(['success' => true]);
}
elseif ($method === 'DELETE') {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $enunciado = $input['enunciado'] ?? '';
    $all = getAllQuestions();
    $new = [];
    foreach ($all as $q) {
        if ($q['enunciado'] === $enunciado) continue;
        $new[] = $q;
    }
    saveAllQuestions($new);
    echo json_encode(['success' => true]);
}
else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
