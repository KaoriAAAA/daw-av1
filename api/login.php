<?php
session_start();
header('Content-Type: application/json');

$usersFile = __DIR__ . '/../users.txt';

function getUsers() {
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

function findUser($username) {
    foreach (getUsers() as $u) {
        if ($u['username'] === $username) return $u;
    }
    return null;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $action = $input['action'] ?? 'login';
    if ($action === 'login') {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $user = findUser($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nome'] = $user['nome'];
            echo json_encode(['success' => true, 'role' => $user['role'], 'nome' => $user['nome']]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
        }
    }
    else if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
    }
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida']);
    }
}
else if ($method === 'GET') {
    if (isset($_SESSION['username'])) {
        echo json_encode([
            'logged' => true,
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'nome' => $_SESSION['nome']
        ]);
    } else {
        echo json_encode(['logged' => false]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
