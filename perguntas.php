<?php
session_start();

$usersFile = __DIR__ . '/users.txt';
$perguntasFile = __DIR__ . '/perguntas.txt';
$respostasFile = __DIR__ . '/respostas.txt';

if (!file_exists($usersFile)) {
    file_put_contents($usersFile, "username;password_hash;role;nome\n");
    $defaultAdminPass = password_hash('admin123', PASSWORD_DEFAULT);
    file_put_contents($usersFile, "admin;{$defaultAdminPass};admin;Administrador\n", FILE_APPEND);
}
if (!file_exists($perguntasFile)) {
    file_put_contents($perguntasFile, "enunciado;tipo;opcao A;opcao B;opcao C;opcao D;opcaocerta\n");
}
if (!file_exists($respostasFile)) {
    file_put_contents($respostasFile, "username;enunciado;resposta;data\n");
}

function getUsers() {
    global $usersFile;
    $rows = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];
    foreach ($rows as $i => $r) {
        if ($i == 0) continue;
        $c = explode(';', $r);
        $users[] = ['username'=>trim($c[0]), 'password_hash'=>trim($c[1]), 'role'=>trim($c[2]), 'nome'=>trim($c[3])];
    }
    return $users;
}

function saveUsers($lines) {
    global $usersFile;
    file_put_contents($usersFile, "username;password_hash;role;nome\n" . implode("\n", $lines) . "\n");
}

function findUser($username) {
    $users = getUsers();
    foreach ($users as $u) {
        if ($u['username'] === $username) return $u;
    }
    return null;
}

function requireLogin() {
    if (!isset($_SESSION['username'])) {
        header("Location: ?page=login");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        echo "<p style='color:red;'>Acesso negado.</p>";
        exit;
    }
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $user = findUser($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nome'] = $user['nome'];
            header("Location: ?page=home");
            exit;
        } else {
            $mensagem = "<p style='color:red;'>Usuário ou senha inválidos.</p>";
        }
    }

    if ($acao === 'logout') {
        session_destroy();
        header("Location: ?page=login");
        exit;
    }

    if ($acao === 'criar_usuario') {
        requireAdmin();
        $username = trim($_POST['username']);
        $senha = $_POST['senha'];
        $role = $_POST['role'];
        $nome = $_POST['nome'];
        if (findUser($username)) {
            $mensagem = "<p style='color:orange;'>Usuário já existe.</p>";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            file_put_contents($usersFile, "{$username};{$hash};{$role};{$nome}\n", FILE_APPEND);
            $mensagem = "<p style='color:green;'>Usuário criado com sucesso.</p>";
        }
    }

    if ($acao === 'apagar_usuario') {
        requireAdmin();
        $u = $_POST['username_del'];
        $rows = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new = [];
        foreach ($rows as $i => $r) {
            if ($i == 0) { $new[] = $r; continue; }
            $c = explode(';', $r);
            if (trim($c[0]) === $u) continue;
            $new[] = $r;
        }
        file_put_contents($usersFile, implode("\n", $new) . "\n");
        $mensagem = "<p style='color:green;'>Usuário apagado.</p>";
    }

    if ($acao === 'editar_usuario') {
        requireAdmin();
        $original = $_POST['original'];
        $username = trim($_POST['username']);
        $senha = $_POST['senha'];
        $role = $_POST['role'];
        $nome = $_POST['nome'];
        $rows = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new = [];
        foreach ($rows as $i => $r) {
            if ($i == 0) { $new[] = $r; continue; }
            $c = explode(';', $r);
            if (trim($c[0]) === $original) {
                $hash = $c[1];
                if ($senha !== '') $hash = password_hash($senha, PASSWORD_DEFAULT);
                $new[] = "{$username};{$hash};{$role};{$nome}";
            } else {
                $new[] = $r;
            }
        }
        file_put_contents($usersFile, implode("\n", $new) . "\n");
        $mensagem = "<p style='color:green;'>Usuário alterado.</p>";
    }

    if ($acao === 'criarpergunta') {
        requireAdmin();
        $enunciado = trim($_POST['enunciado']);
        $tipo = $_POST['tipo'];
        if ($tipo == 1) {
            echo "
            <form method='POST'>
                <input type='hidden' name='acao' value='salvaropcoes'>
                <input type='hidden' name='enunciado' value=\"".htmlspecialchars($enunciado,ENT_QUOTES)."\">
                <input type='hidden' name='tipo' value='Multipla Escolha'>
                <label>a):</label><br>
                <input type='text' name='opcao1' required><br><br>
                <label>b):</label><br>
                <input type='text' name='opcao2' required><br><br>
                <label>c):</label><br>
                <input type='text' name='opcao3' required><br><br>
                <label>d):</label><br>
                <input type='text' name='opcao4' required><br><br>
                <label>Opção correta:</label><br>
                <select name='opcaocerta' required>
                    <option value='a)'>a)</option>
                    <option value='b)'>b)</option>
                    <option value='c)'>c)</option>
                    <option value='d)'>d)</option>
                </select><br><br>
                <input type='submit' value='Salvar Pergunta'>
            </form><hr>
            ";
        } else {
            $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $existe = false;
            foreach ($linhas as $i => $linha) {
                if ($i == 0) continue;
                $col = explode(';', $linha);
                if (trim($col[0]) == $enunciado) { $existe = true; break; }
            }
            if (!$existe) {
                file_put_contents($perguntasFile, "{$enunciado};Discursiva;;;;;;\n", FILE_APPEND);
                $mensagem = "<p style='color:green;'>Pergunta discursiva salva.</p>";
            } else {
                $mensagem = "<p style='color:orange;'>Essa pergunta já existe.</p>";
            }
        }
    }

    if ($acao === 'salvaropcoes') {
        requireAdmin();
        $enunciado = trim($_POST['enunciado']);
        $op1 = trim($_POST['opcao1']);
        $op2 = trim($_POST['opcao2']);
        $op3 = trim($_POST['opcao3']);
        $op4 = trim($_POST['opcao4']);
        $opcaocerta = $_POST['opcaocerta'];
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $existe = false;
        foreach ($linhas as $i => $linha) {
            if ($i == 0) continue;
            $col = explode(';', $linha);
            if (trim($col[0]) == $enunciado) { $existe = true; break; }
        }
        if (!$existe) {
            file_put_contents($perguntasFile, "{$enunciado};Multipla Escolha;{$op1};{$op2};{$op3};{$op4};{$opcaocerta}\n", FILE_APPEND);
            $mensagem = "<p style='color:green;'>Pergunta de múltipla escolha salva.</p>";
        } else {
            $mensagem = "<p style='color:orange;'>Essa pergunta já existe.</p>";
        }
    }

    if ($acao === 'apagar_pergunta') {
        requireAdmin();
        $enunciadoApag = $_POST['enunciadoapag'];
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $nova = [];
        foreach ($linhas as $i => $linha) {
            if ($i == 0) { $nova[] = $linha; continue; }
            $col = explode(';', $linha);
            if (trim($col[0]) === $enunciadoApag) continue;
            $nova[] = $linha;
        }
        file_put_contents($perguntasFile, implode("\n", $nova) . "\n");
        $mensagem = "<p style='color:red;'>Pergunta apagada.</p>";
    }

    if ($acao === 'listartodas') {
        requireLogin();
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        echo "<table border='1' cellpadding='6' cellspacing='0'>";
        echo "<tr><th>Enunciado</th><th>Tipo</th><th>Opções</th><th>Opção Certa</th></tr>";
        foreach ($linhas as $i => $linha) {
            if ($i == 0) continue;
            $col = explode(';', $linha);
            if (count($col) < 7) continue;
            if ($col[1] == "Discursiva") {
                $col[2]=$col[3]=$col[4]=$col[5]=$col[6]="N/A";
            }
            echo "<tr><td>".htmlspecialchars($col[0])."</td><td>{$col[1]}</td><td>".htmlspecialchars($col[2]." ".$col[3]." ".$col[4]." ".$col[5])."</td><td>{$col[6]}</td></tr>";
        }
        echo "</table><br><br>";
    }

    if ($acao === 'listaruma') {
        requireLogin();
        $listar1 = $_POST['listar1'];
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        echo "<table border='1' cellpadding='6' cellspacing='0'>";
        echo "<tr><th>Enunciado</th><th>Tipo</th><th>Opções</th><th>Opção Certa</th></tr>";
        foreach ($linhas as $i => $linha) {
            if ($i == 0) continue;
            $col = explode(';', $linha);
            if (count($col) < 7) continue;
            if ($col[1] == "Discursiva") {
                $col[2]=$col[3]=$col[4]=$col[5]=$col[6]="N/A";
            }
            if ($col[0] == $listar1) {
                echo "<tr><td>".htmlspecialchars($col[0])."</td><td>{$col[1]}</td><td>".htmlspecialchars($col[2]." ".$col[3]." ".$col[4]." ".$col[5])."</td><td>{$col[6]}</td></tr>";
            }
        }
        echo "</table><br><br>";
    }

    if ($acao === 'alterar') {
        requireAdmin();
        $enunciadoAlt = $_POST['enunciadoalt'] ?? "";
        if ($enunciadoAlt == "") {
            echo "
            <form method='POST'>
                <input type='hidden' name='acao' value='alterar'>
                <label for='enunciadoalt'>Enunciado da pergunta a alterar:</label><br>
                <input type='text' id='enunciadoalt' name='enunciadoalt' required><br><br>
                <input type='submit' value='Carregar pergunta'>
            </form>
            ";
        } else {
            $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $achou = false;
            foreach ($linhas as $i => $linha) {
                if ($i == 0) continue;
                $col = explode(';', $linha);
                if ($col[0] == $enunciadoAlt) {
                    $achou = true;
                    if ($col[1] == "Multipla Escolha") {
                        $op1 = htmlspecialchars($col[2], ENT_QUOTES);
                        $op2 = htmlspecialchars($col[3], ENT_QUOTES);
                        $op3 = htmlspecialchars($col[4], ENT_QUOTES);
                        $op4 = htmlspecialchars($col[5], ENT_QUOTES);
                        $correta = $col[6];
                        echo "
                        <form method='POST'>
                            <input type='hidden' name='acao' value='salvaralteracao'>
                            <input type='hidden' name='tipo' value='Multipla Escolha'>
                            <input type='hidden' name='original' value=\"".htmlspecialchars($col[0],ENT_QUOTES)."\">
                            <label>Novo enunciado:</label><br>
                            <input type='text' name='novoenunciado' value=\"".htmlspecialchars($col[0],ENT_QUOTES)."\" required><br><br>
                            <label>a):</label><br>
                            <input type='text' name='opcao1' value=\"{$op1}\" required><br><br>
                            <label>b):</label><br>
                            <input type='text' name='opcao2' value=\"{$op2}\" required><br><br>
                            <label>c):</label><br>
                            <input type='text' name='opcao3' value=\"{$op3}\" required><br><br>
                            <label>d):</label><br>
                            <input type='text' name='opcao4' value=\"{$op4}\" required><br><br>
                            <label>Opção correta:</label><br>
                            <select name='opcaocerta' required>
                                <option value='a)' ".($correta=='a)'?'selected':'').">a)</option>
                                <option value='b)' ".($correta=='b)'?'selected':'').">b)</option>
                                <option value='c)' ".($correta=='c)'?'selected':'').">c)</option>
                                <option value='d)' ".($correta=='d)'?'selected':'').">d)</option>
                            </select><br><br>
                            <input type='submit' value='Salvar Alteração'>
                        </form>
                        ";
                    } else {
                        echo "
                        <form method='POST'>
                            <input type='hidden' name='acao' value='salvaralteracao'>
                            <input type='hidden' name='tipo' value='Discursiva'>
                            <input type='hidden' name='original' value=\"".htmlspecialchars($col[0],ENT_QUOTES)."\">
                            <label>Novo enunciado:</label><br>
                            <input type='text' name='novoenunciado' value=\"".htmlspecialchars($col[0],ENT_QUOTES)."\" required><br><br>
                            <input type='submit' value='Salvar Alteração'>
                        </form>
                        ";
                    }
                    break;
                }
            }
            if (!$achou) {
                $mensagem = "<p style='color:red;'>Pergunta não encontrada.</p>";
            }
        }
    }

    if ($acao === 'salvaralteracao') {
        requireAdmin();
        $original = $_POST['original'];
        $novo = trim($_POST['novoenunciado']);
        $tipo = $_POST['tipo'];
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $novaLista = [];
        foreach ($linhas as $i => $linha) {
            if ($i == 0) { $novaLista[] = $linha; continue; }
            $col = explode(';', $linha);
            if (trim($col[0]) === $original) {
                if ($tipo === 'Multipla Escolha') {
                    $op1 = trim($_POST['opcao1']);
                    $op2 = trim($_POST['opcao2']);
                    $op3 = trim($_POST['opcao3']);
                    $op4 = trim($_POST['opcao4']);
                    $opcaocerta = $_POST['opcaocerta'];
                    $novaLista[] = "{$novo};Multipla Escolha;{$op1};{$op2};{$op3};{$op4};{$opcaocerta}";
                } else {
                    $novaLista[] = "{$novo};Discursiva;;;;;;";
                }
            } else {
                $novaLista[] = $linha;
            }
        }
        file_put_contents($perguntasFile, implode("\n", $novaLista) . "\n");
        $mensagem = "<p style='color:green;'>Alteração salva.</p>";
    }

    if ($acao === 'responder') {
        requireLogin();
        $enunciado = $_POST['enunciado'];
        $user = $_SESSION['username'];
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $achou = false;
        foreach ($linhas as $i => $linha) {
            if ($i == 0) continue;
            $col = explode(';', $linha);
            if ($col[0] == $enunciado) { $achou = true; $tipo = $col[1]; break; }
        }
        if (!$achou) { $mensagem = "<p style='color:red;'>Pergunta não encontrada.</p>"; }
        else {
            $resposta = $_POST['resposta'] ?? '';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($respostasFile, "{$user};{$enunciado};".str_replace(["\n","\r",";"],[' ',' ',' '],$resposta).";{$timestamp}\n", FILE_APPEND);
            $mensagem = "<p style='color:green;'>Resposta registrada.</p>";
        }
    }

    if ($acao === 'ver_respostas') {
        requireLogin();
        $usernameToView = $_POST['username_view'] ?? $_SESSION['username'];
        $rows = file($respostasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        echo "<h3>Respostas de ".htmlspecialchars($usernameToView)."</h3>";
        echo "<table border='1' cellpadding='6'><tr><th>Usuário</th><th>Pergunta</th><th>Resposta</th><th>Data</th></tr>";
        foreach ($rows as $r) {
            $c = explode(';', $r);
            if (trim($c[0]) === $usernameToView) {
                echo "<tr><td>{$c[0]}</td><td>".htmlspecialchars($c[1])."</td><td>".htmlspecialchars($c[2])."</td><td>{$c[3]}</td></tr>";
            }
        }
        echo "</table><br>";
    }
}
$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Sistema Game - Water Falls</title>
</head>
<body>
<h2>Game Corporativo - Water Falls</h2>
<?php if (isset($_SESSION['username'])): ?>
<p>Logado como: <?php echo htmlspecialchars($_SESSION['nome']); ?> (<?php echo $_SESSION['role']; ?>) | <a href="?page=home">Home</a> | <form style="display:inline" method="POST"><input type="hidden" name="acao" value="logout"><button type="submit">Sair</button></form></p>
<?php else: ?>
<p><a href="?page=login">Entrar</a></p>
<?php endif; ?>

<?php echo $mensagem; ?>

<?php if ($page === 'login'): ?>
<h3>Login</h3>
<form method="POST">
    <input type="hidden" name="acao" value="login">
    <label>Usuário:</label><br>
    <input type="text" name="username" required><br><br>
    <label>Senha:</label><br>
    <input type="password" name="password" required><br><br>
    <input type="submit" value="Entrar">
</form>
<?php exit; endif; ?>

<?php if ($page === 'home'): ?>
<?php if (!isset($_SESSION['username'])): ?>
<p>Você precisa <a href="?page=login">entrar</a> para usar o sistema.</p>
<?php else: ?>
<h3>Menu</h3>
<ul>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <li><a href="?page=usuarios">CRUD Usuários</a></li>
    <li><a href="?page=perguntas">Gerenciar Perguntas</a></li>
    <li><a href="?page=listartodas">Listar Todas Perguntas</a></li>
    <?php endif; ?>
    <?php if ($_SESSION['role'] === 'funcionario' || $_SESSION['role'] === 'admin'): ?>
    <li><a href="?page=responder">Responder Perguntas</a></li>
    <li><a href="?page=minhasrespostas">Minhas Respostas</a></li>
    <?php endif; ?>
</ul>
<?php endif; ?>
<?php endif; ?>

<?php if ($page === 'usuarios' && isset($_SESSION['username'])): requireAdmin(); ?>
<h3>CRUD Usuários</h3>
<p>Criar usuário</p>
<form method="POST">
    <input type="hidden" name="acao" value="criar_usuario">
    <label>Usuário:</label><br><input type="text" name="username" required><br>
    <label>Senha:</label><br><input type="password" name="senha" required><br>
    <label>Nome:</label><br><input type="text" name="nome" required><br>
    <label>Role:</label><br>
    <select name="role"><option value="funcionario">funcionario</option><option value="admin">admin</option></select><br><br>
    <input type="submit" value="Criar">
</form>

<h4>Lista de usuários</h4>
<table border="1" cellpadding="6">
<tr><th>Usuário</th><th>Nome</th><th>Role</th><th>Ações</th></tr>
<?php foreach (getUsers() as $u): ?>
<tr>
<td><?php echo htmlspecialchars($u['username']); ?></td>
<td><?php echo htmlspecialchars($u['nome']); ?></td>
<td><?php echo htmlspecialchars($u['role']); ?></td>
<td>
<form style="display:inline" method="POST" action="?page=editar_usuario">
    <input type="hidden" name="username" value="<?php echo htmlspecialchars($u['username']); ?>">
    <button type="submit">Editar</button>
</form>
<form style="display:inline" method="POST">
    <input type="hidden" name="acao" value="apagar_usuario">
    <input type="hidden" name="username_del" value="<?php echo htmlspecialchars($u['username']); ?>">
    <button type="submit" onclick="return confirm('Apagar usuário?')">Apagar</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($page === 'editar_usuario' && isset($_SESSION['username'])): requireAdmin(); 
$username = $_POST['username'] ?? '';
$user = findUser($username);
if (!$user) { echo "<p>Usuário não encontrado.</p>"; }
else {
?>
<h3>Editar Usuário <?php echo htmlspecialchars($user['username']); ?></h3>
<form method="POST">
    <input type="hidden" name="acao" value="editar_usuario">
    <input type="hidden" name="original" value="<?php echo htmlspecialchars($user['username']); ?>">
    <label>Usuário:</label><br><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br>
    <label>Senha (deixe em branco para manter):</label><br><input type="password" name="senha"><br>
    <label>Nome:</label><br><input type="text" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required><br>
    <label>Role:</label><br>
    <select name="role">
        <option value="funcionario" <?php echo $user['role']=='funcionario'?'selected':''; ?>>funcionario</option>
        <option value="admin" <?php echo $user['role']=='admin'?'selected':''; ?>>admin</option>
    </select><br><br>
    <input type="submit" value="Salvar">
</form>
<?php } endif; ?>

<?php if ($page === 'perguntas' && isset($_SESSION['username'])): requireAdmin(); ?>
<h3>Gerenciar Perguntas</h3>

<h4>Criar Pergunta</h4>
<form method="POST">
    <input type="hidden" name="acao" value="criarpergunta">
    <label>Enunciado:</label><br><input type="text" name="enunciado" required><br>
    <label>Tipo:</label><br>
    <select name="tipo">
        <option value="1">Múltipla Escolha</option>
        <option value="2">Discursiva</option>
    </select><br><br>
    <input type="submit" value="Continuar">
</form>

<h4>Apagar Pergunta</h4>
<form method="POST">
    <input type="hidden" name="acao" value="apagar_pergunta">
    <label>Enunciado a apagar:</label><br><input type="text" name="enunciadoapag" required><br><br>
    <input type="submit" value="Apagar">
</form>

<h4>Alterar Pergunta</h4>
<form method="POST">
    <input type="hidden" name="acao" value="alterar">
    <label>Enunciado a alterar:</label><br><input type="text" name="enunciadoalt" required><br><br>
    <input type="submit" value="Carregar">
</form>

<h4>Listar todas</h4>
<form method="POST">
    <input type="hidden" name="acao" value="listartodas">
    <input type="submit" value="Listar todas as perguntas">
</form>
<?php endif; ?>

<?php if ($page === 'listartodas' && isset($_SESSION['username'])): requireLogin();
$_POST['acao'] = 'listartodas';
include __FILE__;
exit; endif; ?>

<?php if ($page === 'responder' && isset($_SESSION['username'])): requireLogin(); ?>
<h3>Responder Perguntas</h3>
<h4>Escolha uma pergunta</h4>
<?php
$linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($linhas as $i => $linha) {
    if ($i == 0) continue;
    $col = explode(';', $linha);
    echo "<div style='border:1px solid #ccc;padding:8px;margin:6px 0;'>";
    echo "<strong>".htmlspecialchars($col[0])."</strong><br>";
    if ($col[1] == 'Multipla Escolha') {
        echo "<form method='POST'><input type='hidden' name='acao' value='responder'><input type='hidden' name='enunciado' value=\"".htmlspecialchars($col[0],ENT_QUOTES)."\">";
        echo "<label><input type='radio' name='resposta' value='a)' required> a) ".htmlspecialchars($col[2])."</label><br>";
        echo "<label><input type='radio' name='resposta' value='b)'> b) ".htmlspecialchars($col[3])."</label><br>";
        echo "<label><input type='radio' name='resposta' value='c)'> c) ".htmlspecialchars($col[4])."</label><br>";
        echo "<label><input type='radio' name='resposta' value='d)'> d) ".htmlspecialchars($col[5])."</label><br><br>";
        echo "<input type='submit' value='Enviar resposta'></form>";
    } else {
        echo "<form method='POST'><input type='hidden' name='acao' value='responder'><input type='hidden' name='enunciado' value=\"".htmlspecialchars($col[0],ENT_QUOTES)."\">";
        echo "<textarea name='resposta' rows='3' cols='60' required></textarea><br><br>";
        echo "<input type='submit' value='Enviar resposta'></form>";
    }
    echo "</div>";
}
?>
<?php endif; ?>

<?php if ($page === 'minhasrespostas' && isset($_SESSION['username'])): requireLogin(); ?>
<h3>Minhas Respostas</h3>
<form method="POST">
    <input type="hidden" name="acao" value="ver_respostas">
    <input type="hidden" name="username_view" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
    <input type="submit" value="Ver minhas respostas">
</form>
<?php endif; ?>

</body>
</html>
