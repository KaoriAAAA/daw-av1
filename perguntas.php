<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>CRUD Perguntas</title>
</head>
<body>
  <h2>CRUD Perguntas</h2>

  <?php

    $arquivo = "perguntas.txt";

  //se o formulario de enunciado e tipo for enviado
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["acao"]) && $_POST["acao"] == "criarpergunta") {
      $enunciado  = $_POST["enunciado"];
      $tipo = $_POST["tipo"];

      if ($tipo == 1) {
          echo "
          <p>Configuração da Pergunta:</p>
          <form method=\"POST\">
              <input type=\"hidden\" name=\"acao\" value=\"salvaropcoes\">
              <input type=\"hidden\" name=\"enunciado\" value=\"$enunciado\">
              <input type=\"hidden\" name=\"tipo\" value=\"$tipo\">

              <label for=\"opcao1\">a):</label><br>
              <input type=\"text\" id=\"opcao1\" name=\"opcao1\" required><br><br>

              <label for=\"opcao2\">b):</label><br>
              <input type=\"text\" id=\"opcao2\" name=\"opcao2\" required><br><br>

              <label for=\"opcao3\">c):</label><br>
              <input type=\"text\" id=\"opcao3\" name=\"opcao3\" required><br><br>

              <label for=\"opcao4\">d):</label><br>
              <input type=\"text\" id=\"opcao4\" name=\"opcao4\" required><br><br>

              <label for=\"opcaocerta\">Opção correta: </label><br>
              <select id=\"opcaocerta\" name=\"opcaocerta\">
                  <option value=\"a)\">a)</option>
                  <option value=\"b)\">b)</option>
                  <option value=\"c)\">c)</option>
                  <option value=\"d)\">d)</option>
              </select>
              </br></br>
              <input type=\"submit\" value=\"Salvar\">
              </br></br>
          </form>
          ";
      }
      else{
        echo "
            <input type=\"hidden\" name=\"enunciado\" value=\"$enunciado\">
            <input type=\"hidden\" name=\"tipo\" value=\"$tipo\">
        ";
      }
  }

  if (isset($_POST["acao"]) && $_POST["acao"] == "salvaropcoes") {
      $enunciado  = $_POST["enunciado"];
      $tipo = $_POST["tipo"];
      if($tipo == 1){
        $opcao1 = $_POST["opcao1"];
        $opcao2 = $_POST["opcao2"];
        $opcao3 = $_POST["opcao3"];
        $opcao4 = $_POST["opcao4"];
        $opcaocerta = $_POST["opcaocerta"];
      }

      if (!file_exists($arquivo)) {
        file_put_contents($arquivo, "enunciado;tipo;opcao A);opcao B);opcao C);opcao D);opcaocerta\n\n\n"); // Cria o cabeçalho do arquivo
      }
      if($tipo == 1){
        file_put_contents($arquivo, "$enunciado;Multipla Escolha;$opcao1;$opcao2;$opcao3;$opcao4;$opcaocerta\n", FILE_APPEND);
      }else{
        file_put_contents($arquivo, "$enunciado;$tipo;\n", FILE_APPEND);
      }

      
      echo "<p>Pergunta salva.</p>";
  }
  ?>

  <?php
  
  if (!isset($_POST["acao"]) || $_POST["acao"] != "salvaropcoes") {
      echo '
      <form method="POST">
          <input type="hidden" name="acao" value="criarpergunta">
          <label for="enunciado">Enunciado:</label><br>
          <input type="text" id="enunciado" name="enunciado" required><br><br>

          <label for="tipo">Tipo da pergunta:</label><br>
              <select id="tipo" name="tipo">
                  <option value="1">Múltipla Escolha</option>
                  <option value="2">Discursiva</option>
              </select>

          <input type="submit" value="Continuar">
      </form>';
  }
  
  ?>

</body>
</html>
