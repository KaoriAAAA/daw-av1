<?php
session_start();
header('Content-Type: application/json');

$usersFile = __DIR__ . '/../users.txt';

function getUsersFull() {
    global $usersFile;
    $rows = @file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $users = [];
    foreach ($rows as $i => $r) {
        if ($i === 0) continue;
        $c = explode(';', $r);
        if (count($c) >= 4) {
            $users[] = [
                'username' => trim($c[0]),
                'password_hash' => trim($c[1]),
                'role' => trim($c[2]),
                'nome' => trim($c[3])
            ];
        }
    }
    return $users;
}

function getUsersPublic() {
    $all = getUsersFull();
    $pub = [];
    foreach ($all as $u) {
        $pub[] = ['username' => $u['username'], 'role' => $u['role'], 'nome' => $u['nome']];
    }
    return $pub;
}

function findUser($username) {
    foreach (getUsersFull() as $u) {
        if ($u['username'] === $username) return $u;
    }
    return null;
}

function saveAllUsers($users) {
    global $usersFile;
    $lines = ["username;password_hash;role;nome"];
    foreach ($users as $u) {
        $lines[] = "{$u['username']};{$u['password_hash']};{$u['role']};{$u['nome']}";
    }
    file_put_contents($usersFile, implode("\n", $lines) . "\n");
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    echo json_encode(getUsersPublic());
}
elseif ($method === 'POST') {
    $username = $input['username'] ?? '';
    $senha = $input['senha'] ?? '';
    $role = $input['role'] ?? 'funcionario';
    $nome = $input['nome'] ?? '';
    if (findUser($username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Usuário já existe']);
        exit;
    }
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    file_put_contents($usersFile, "{$username};{$hash};{$role};{$nome}\n", FILE_APPEND);
    echo json_encode(['success' => true]);
}
elseif ($method === 'PUT') {
    $original = $input['original'] ?? '';
    $username = $input['username'] ?? '';
    $senha = $input['senha'] ?? '';
    $role = $input['role'] ?? 'funcionario';
    $nome = $input['nome'] ?? '';
    $all = getUsersFull();
    $new = [];
    foreach ($all as $u) {
        if ($u['username'] === $original) {
            $hash = $u['password_hash'];
            if ($senha !== '') {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
            }
            $new[] = [
                'username' => $username,
                'password_hash' => $hash,
                'role' => $role,
                'nome' => $nome
            ];
        } else {
            $new[] = $u;
        }
    }
    saveAllUsers($new);
    echo json_encode(['success' => true]);
}
elseif ($method === 'DELETE') {
    $username = $input['username'] ?? '';
    $all = getUsersFull();
    $new = [];
    foreach ($all as $u) {
        if ($u['username'] === $username) continue;
        $new[] = $u;
    }
    saveAllUsers($new);
    echo json_encode(['success' => true]);
}
else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
