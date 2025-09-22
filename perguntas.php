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
  $mensagem = "";

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["acao"])) {
      $acao = $_POST["acao"];

      if ($acao == "criarpergunta") {
          $enunciado = $_POST["enunciado"];
          $tipo = $_POST["tipo"];

          if ($tipo == 1) {
              echo "
              <form method=\"POST\">
                  <p><strong>Configuração da Pergunta (Múltipla Escolha):</strong></p>

                  <input type=\"hidden\" name=\"acao\" value=\"salvaropcoes\">
                  <input type=\"hidden\" name=\"enunciado\" value=\"$enunciado\">
                  <input type=\"hidden\" name=\"tipo\" value=\"$tipo\">

                  <label>a):</label><br>
                  <input type=\"text\" name=\"opcao1\" required><br><br>

                  <label>b):</label><br>
                  <input type=\"text\" name=\"opcao2\" required><br><br>

                  <label>c):</label><br>
                  <input type=\"text\" name=\"opcao3\" required><br><br>

                  <label>d):</label><br>
                  <input type=\"text\" name=\"opcao4\" required><br><br>

                  <label>Opção correta:</label><br>
                  <select name=\"opcaocerta\" required>
                      <option value=\"a)\">a)</option>
                      <option value=\"b)\">b)</option>
                      <option value=\"c)\">c)</option>
                      <option value=\"d)\">d)</option>
                  </select>
                  <br><br>

                  <input type=\"submit\" value=\"Salvar Pergunta\">
              </form>
              <hr>
              ";
          } else {
              if (!file_exists($arquivo)) {
                  file_put_contents($arquivo, "enunciado;tipo;opcao A;opcao B;opcao C;opcao D;opcaocerta\n");
              }

              file_put_contents($arquivo, "$enunciado;Discursiva;;;;;;\n", FILE_APPEND);
              $mensagem = "<p style='color: green;'>Pergunta discursiva salva com sucesso!</p>";
          }
      }

      if ($acao == "salvaropcoes") {
          $enunciado = $_POST["enunciado"];
          $tipo = $_POST["tipo"];

          $opcao1 = $_POST["opcao1"];
          $opcao2 = $_POST["opcao2"];
          $opcao3 = $_POST["opcao3"];
          $opcao4 = $_POST["opcao4"];
          $opcaocerta = $_POST["opcaocerta"];

          if (!file_exists($arquivo)) {
              file_put_contents($arquivo, "enunciado;tipo;opcao A;opcao B;opcao C;opcao D;opcaocerta\n");
          }

          file_put_contents($arquivo, "$enunciado;Multipla Escolha;$opcao1;$opcao2;$opcao3;$opcao4;$opcaocerta\n", FILE_APPEND);
          $mensagem = "<p style='color: green;'>Pergunta de múltipla escolha salva com sucesso!</p>";
      }
  }

  echo $mensagem;
  ?>

  <form method="POST">
      <input type="hidden" name="acao" value="criarpergunta">

      <label for="enunciado">Enunciado da Pergunta:</label><br>
      <input type="text" id="enunciado" name="enunciado" required><br><br>

      <label for="tipo">Tipo da Pergunta:</label><br>
      <select id="tipo" name="tipo">
          <option value="1">Múltipla Escolha</option>
          <option value="2">Discursiva</option>
      </select><br><br>

      <input type="submit" value="Continuar">
  </form>
  <hr>
  <h3>Apagar Pergunta</h3>
  <form method="POST">
      <input type="hidden" name="acao" value="apagar">
      <label for="enunciadoapag">Enunciado da pergunta a ser apagada:</label><br>
      <input type="text" id="enunciadoapag" name="enunciadoapag" required><br><br>
      <br><br>

      <input type="submit" value="Continuar">
  </form>

</body>
</html>
