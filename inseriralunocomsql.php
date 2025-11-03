<?php
$server = "localhost";
$username = "root";
$pass = "";
$database = "sqlalunos";

$conn = new mysqli($server, $username, $pass, $database);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == "create") {
        $matricula = $_POST["matricula"];
        $nome = $_POST["nome"];
        $email = $_POST["email"];

        $stmt = $conn->prepare("INSERT INTO alunos (matricula, nome, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $matricula, $nome, $email);
        if ($stmt->execute()) {
            echo "Aluno inserido com sucesso!";
        } else {
            echo "Erro ao inserir aluno: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action == "update") {
        $matricula = $_POST["matricula"];
        $nome = $_POST["nome"];
        $email = $_POST["email"];

        $stmt = $conn->prepare("UPDATE alunos SET nome = ?, email = ? WHERE matricula = ?");
        $stmt->bind_param("sss", $nome, $email, $matricula);
        if ($stmt->execute()) {
            echo "Aluno atualizado com sucesso!";
        } else {
            echo "Erro ao atualizar aluno: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action == "delete") {
        $matricula = $_POST["matricula"];

        $stmt = $conn->prepare("DELETE FROM alunos WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);
        if ($stmt->execute()) {
            echo "Aluno removido com sucesso!";
        } else {
            echo "Erro ao remover aluno: " . $stmt->error;
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'read') {
    $sql = "SELECT matricula, nome, email FROM alunos";
    $result = $conn->query($sql);

    $alunos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $alunos[] = $row;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($alunos);
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'read_one' && isset($_GET['matricula'])) {
    $matricula = $_GET['matricula'];

    $stmt = $conn->prepare("SELECT matricula, nome, email FROM alunos WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $result = $stmt->get_result();

    $aluno = $result->fetch_assoc();

    header('Content-Type: application/json');
    echo json_encode($aluno);

    $stmt->close();
}


$conn->close();
?>
