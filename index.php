<?php
session_start();

// ------------------- Arquivos -------------------
$usersFile = __DIR__.'/users.txt';
$perguntasFile = __DIR__.'/perguntas.txt';
$respostasFile = __DIR__.'/respostas.txt';

// ------------------- Inicialização -------------------
if(!file_exists($usersFile)){
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    file_put_contents($usersFile,"username;password_hash;role;nome\nadmin;{$hash};admin;Administrador\n");
}
if(!file_exists($perguntasFile)){
    file_put_contents($perguntasFile,"enunciado;tipo;opcaoA;opcaoB;opcaoC;opcaoD;opcaocerta\n");
}
if(!file_exists($respostasFile)){
    file_put_contents($respostasFile,"username;enunciado;resposta;data\n");
}

// ------------------- Funções -------------------
function getUsers(){
    global $usersFile;
    $rows = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = [];
    foreach($rows as $i=>$r){
        if($i==0) continue;
        $c = explode(';',$r);
        $users[]=['username'=>trim($c[0]),'password_hash'=>trim($c[1]),'role'=>trim($c[2]),'nome'=>trim($c[3])];
    }
    return $users;
}

function findUser($username){
    foreach(getUsers() as $u) if($u['username']===$username) return $u;
    return null;
}

function requireLogin(){
    if(!isset($_SESSION['username'])){
        echo json_encode(['sucesso'=>false,'mensagem'=>'Login necessário']);
        exit;
    }
}

function requireAdmin(){
    requireLogin();
    if($_SESSION['role']!=='admin'){
        echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado']);
        exit;
    }
}

// ------------------- Processa ações via AJAX -------------------
if(isset($_POST['acao'])){
    $acao = $_POST['acao'];

    if($acao==='login'){
        $user = findUser($_POST['username']??'');
        if($user && password_verify($_POST['password']??'',$user['password_hash'])){
            $_SESSION['username']=$user['username'];
            $_SESSION['role']=$user['role'];
            $_SESSION['nome']=$user['nome'];
            echo json_encode(['sucesso'=>true,'mensagem'=>'Login realizado']);
        }else{
            echo json_encode(['sucesso'=>false,'mensagem'=>'Usuário ou senha inválidos']);
        }
        exit;
    }

    if($acao==='logout'){
        session_destroy();
        echo json_encode(['sucesso'=>true,'mensagem'=>'Logout realizado']);
        exit;
    }

    // ----------------- Usuários -----------------
    if($acao==='listar_usuarios'){ requireAdmin(); echo json_encode(['sucesso'=>true,'usuarios'=>getUsers()]); exit; }
    if($acao==='criar_usuario'){ 
        requireAdmin();
        $username = trim($_POST['username']);
        $senha = $_POST['senha'];
        $role = $_POST['role'];
        $nome = $_POST['nome'];
        if(findUser($username)){
            echo json_encode(['sucesso'=>false,'mensagem'=>'Usuário já existe']);
        }else{
            $hash = password_hash($senha,PASSWORD_DEFAULT);
            file_put_contents($usersFile,"{$username};{$hash};{$role};{$nome}\n", FILE_APPEND);
            echo json_encode(['sucesso'=>true,'mensagem'=>'Usuário criado']);
        }
        exit;
    }
    if($acao==='apagar_usuario'){ 
        requireAdmin();
        $u = $_POST['username_del'];
        $rows = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new = [];
        foreach($rows as $i=>$r){
            if($i==0){$new[]=$r;continue;}
            $c = explode(';',$r);
            if(trim($c[0])===$u) continue;
            $new[]=$r;
        }
        file_put_contents($usersFile, implode("\n",$new)."\n");
        echo json_encode(['sucesso'=>true,'mensagem'=>'Usuário apagado']);
        exit;
    }
    if($acao==='editar_usuario'){
        requireAdmin();
        $original=$_POST['original'];
        $username=trim($_POST['username']);
        $senha=$_POST['senha'];
        $role=$_POST['role'];
        $nome=$_POST['nome'];
        $rows=file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new=[];
        foreach($rows as $i=>$r){
            if($i==0){$new[]=$r;continue;}
            $c=explode(';',$r);
            if(trim($c[0])===$original){
                $hash=$c[1]; if($senha!=='') $hash=password_hash($senha,PASSWORD_DEFAULT);
                $new[]="{$username};{$hash};{$role};{$nome}";
            }else{ $new[]=$r; }
        }
        file_put_contents($usersFile, implode("\n",$new)."\n");
        echo json_encode(['sucesso'=>true,'mensagem'=>'Usuário alterado']);
        exit;
    }

    // ----------------- Perguntas -----------------
    if($acao==='listar_perguntas'){
        requireLogin();
        $linhas = file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $perguntas=[];
        foreach($linhas as $i=>$l){
            if($i==0) continue;
            $c=explode(';',$l);
            $opcoes=($c[1]==='Discursiva')?'N/A':($c[2].' '.$c[3].' '.$c[4].' '.$c[5]);
            $perguntas[]= ['enunciado'=>$c[0],'tipo'=>$c[1],'opcoes'=>$opcoes,'opcaocerta'=>$c[6]];
        }
        echo json_encode(['sucesso'=>true,'perguntas'=>$perguntas]);
        exit;
    }
    if($acao==='criarpergunta'){
        requireAdmin();
        $enunciado=trim($_POST['enunciado']);
        $tipo=$_POST['tipo'];
        $linhas=file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $existe=false; foreach($linhas as $i=>$l){ if($i==0) continue; $c=explode(';',$l); if(trim($c[0])==$enunciado){$existe=true; break;}}
        if($existe){ echo json_encode(['sucesso'=>false,'mensagem'=>'Pergunta já existe']); exit;}
        if($tipo==='Discursiva'){ file_put_contents($perguntasFile,"{$enunciado};Discursiva;;;;;;\n",FILE_APPEND);}
        else{
            $op1=$_POST['opcao1']; $op2=$_POST['opcao2']; $op3=$_POST['opcao3']; $op4=$_POST['opcao4']; $opcaocerta=$_POST['opcaocerta'];
            file_put_contents($perguntasFile,"{$enunciado};Multipla Escolha;{$op1};{$op2};{$op3};{$op4};{$opcaocerta}\n",FILE_APPEND);
        }
        echo json_encode(['sucesso'=>true,'mensagem'=>'Pergunta criada']); exit;
    }
    if($acao==='apagar_pergunta'){
        requireAdmin();
        $enunciadoApag=$_POST['enunciadoapag'];
        $linhas=file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $nova=[];
        foreach($linhas as $i=>$l){if($i==0){$nova[]=$l;continue;} $c=explode(';',$l); if(trim($c[0])===$enunciadoApag) continue; $nova[]=$l;}
        file_put_contents($perguntasFile, implode("\n",$nova)."\n");
        echo json_encode(['sucesso'=>true,'mensagem'=>'Pergunta apagada']); exit;
    }

    if($acao==='alterar_pergunta'){
        requireAdmin();
        $original=$_POST['original']; $novo=trim($_POST['novoenunciado']); $tipo=$_POST['tipo'];
        $linhas=file($perguntasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); $nova=[];
        foreach($linhas as $i=>$l){
            if($i==0){$nova[]=$l; continue;}
            $c=explode(';',$l);
            if(trim($c[0])===$original){
                if($tipo==='Multipla Escolha'){
                    $op1=$_POST['opcao1']; $op2=$_POST['opcao2']; $op3=$_POST['opcao3']; $op4=$_POST['opcao4']; $opcaocerta=$_POST['opcaocerta'];
                    $nova[]="{$novo};Multipla Escolha;{$op1};{$op2};{$op3};{$op4};{$opcaocerta}";
                }else{ $nova[]="{$novo};Discursiva;;;;;;"; }
            }else{ $nova[]=$l; }
        }
        file_put_contents($perguntasFile, implode("\n",$nova)."\n");
        echo json_encode(['sucesso'=>true,'mensagem'=>'Pergunta alterada']); exit;
    }

    // ----------------- Responder -----------------
    if($acao==='responder'){
        requireLogin();
        $enunciado=$_POST['enunciado']??''; $resposta=$_POST['resposta']??'';
        $user=$_SESSION['username']; $ts=date('Y-m-d H:i:s');
        file_put_contents($respostasFile,"{$user};{$enunciado};".str_replace(["\n","\r",";"],[' ',' ',' '],$resposta).";{$ts}\n",FILE_APPEND);
        echo json_encode(['sucesso'=>true,'mensagem'=>'Resposta registrada']); exit;
    }
    if($acao==='ver_respostas'){
        requireLogin();
        $username=$_POST['username_view']??$_SESSION['username'];
        $rows=file($respostasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $res=[];
        foreach($rows as $r){ $c=explode(';',$r); if(trim($c[0])===$username) $res[]= ['usuario'=>$c[0],'pergunta'=>$c[1],'resposta'=>$c[2],'data'=>$c[3]];}
        echo json_encode(['sucesso'=>true,'respostas'=>$res]); exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Sistema Game - Water Falls</title>
<style>
body{font-family:Arial,sans-serif;}
#conteudo{margin-top:20px;}
button{margin:2px;}
table{border-collapse:collapse;width:100%;}
th,td{border:1px solid #ccc;padding:6px;}
</style>
</head>
<body>
<h2>Game Corporativo - Water Falls</h2>
<div id="mensagem"></div>

<?php if(isset($_SESSION['username'])): ?>
<p>Logado como: <span id="usuarioNome"><?php echo htmlspecialchars($_SESSION['nome']); ?></span> (<?php echo $_SESSION['role']; ?>)
<button onclick="logout()">Sair</button></p>
<div>
    <button onclick="loadHome()">Home</button>
    <?php if($_SESSION['role']==='admin'): ?>
        <button onclick="loadUsuarios()">CRUD Usuários</button>
        <button onclick="loadPerguntas()">Gerenciar Perguntas</button>
        <button onclick="listarTodas()">Listar Perguntas</button>
    <?php endif; ?>
    <?php if($_SESSION['role']==='funcionario' || $_SESSION['role']==='admin'): ?>
        <button onclick="responderPerguntas()">Responder Perguntas</button>
        <button onclick="verMinhasRespostas()">Minhas Respostas</button>
    <?php endif; ?>
</div>
<?php else: ?>
<div id="loginForm">
    <h3>Login</h3>
    <input type="text" id="loginUser" placeholder="Usuário">
    <input type="password" id="loginPass" placeholder="Senha">
    <button onclick="login()">Entrar</button>
</div>
<?php endif; ?>

<div id="conteudo"></div>

<script>
// ------------------- Funções AJAX -------------------
async function ajaxPost(data){
    const response = await fetch("",{method:'POST',body:new URLSearchParams(data)});
    return response.json();
}

function showMsg(msg,ok=true){ document.getElementById('mensagem').innerHTML="<p style='color:"+(ok?'green':'red')+"'>"+msg+"</p>"; }

// ------------------- Login/Logout -------------------
async function login(){
    const user=document.getElementById('loginUser').value;
    const pass=document.getElementById('loginPass').value;
    const res=await ajaxPost({acao:'login',username:user,password:pass});
    showMsg(res.mensagem,res.sucesso);
    if(res.sucesso) location.reload();
}

async function logout(){
    const res=await ajaxPost({acao:'logout'});
    showMsg(res.mensagem,res.sucesso);
    if(res.sucesso) location.reload();
}

// ------------------- Home -------------------
function loadHome(){ document.getElementById('conteudo').innerHTML="<p>Selecione uma ação no menu.</p>"; }

// ------------------- Usuários -------------------
async function loadUsuarios(){
    const res = await ajaxPost({acao:'listar_usuarios'});
    if(res.sucesso){
        let html="<h3>CRUD Usuários</h3>";
        html+="<h4>Criar Usuário</h4>";
        html+="<input id='novoUser' placeholder='Usuário'> <input id='novoPass' placeholder='Senha'> <input id='novoNome' placeholder='Nome'>";
        html+="<select id='novoRole'><option value='funcionario'>funcionario</option><option value='admin'>admin</option></select>";
        html+="<button onclick='criarUsuario()'>Criar</button>";
        html+="<h4>Lista</h4><table><tr><th>Usuário</th><th>Nome</th><th>Role</th><th>Ações</th></tr>";
        res.usuarios.forEach(u=>{
            html+="<tr><td>"+u.username+"</td><td>"+u.nome+"</td><td>"+u.role+"</td>";
            html+="<td><button onclick='editarUsuario(\""+u.username+"\")'>Editar</button>";
            html+="<button onclick='apagarUsuario(\""+u.username+"\")'>Apagar</button></td></tr>";
        });
        html+="</table>";
        document.getElementById('conteudo').innerHTML=html;
    }
}

async function criarUsuario(){
    const user=document.getElementById('novoUser').value;
    const pass=document.getElementById('novoPass').value;
    const nome=document.getElementById('novoNome').value;
    const role=document.getElementById('novoRole').value;
    const res=await ajaxPost({acao:'criar_usuario',username:user,senha:pass,nome:nome,role:role});
    showMsg(res.mensagem,res.sucesso);
    if(res.sucesso) loadUsuarios();
}

async function apagarUsuario(username){
    if(!confirm('Apagar usuário?')) return;
    const res=await ajaxPost({acao:'apagar_usuario',username_del:username});
    showMsg(res.mensagem,res.sucesso);
    if(res.sucesso) loadUsuarios();
}

// ------------------- Perguntas -------------------
async function loadPerguntas(){
    let html="<h3>Gerenciar Perguntas</h3>";
    html+="<button onclick='criarPerguntaForm()'>Criar Pergunta</button> ";
    html+="<button onclick='listarTodas()'>Listar Todas</button>";
    document.getElementById('conteudo').innerHTML=html;
}

async function listarTodas(){
    const res=await ajaxPost({acao:'listar_perguntas'});
    if(res.sucesso){
        let html="<h4>Perguntas</h4><table><tr><th>Enunciado</th><th>Tipo</th><th>Opções</th><th>Correta</th></tr>";
        res.perguntas.forEach(p=>{
            html+="<tr><td>"+p.enunciado+"</td><td>"+p.tipo+"</td><td>"+p.opcoes+"</td><td>"+p.opcaocerta+"</td></tr>";
        });
        html+="</table>";
        document.getElementById('conteudo').innerHTML=html;
    }
}

function criarPerguntaForm(){
    let html="<h4>Criar Pergunta</h4>";
    html+="<input id='pergEnunciado' placeholder='Enunciado'> <select id='pergTipo'><option value='Multipla Escolha'>Múltipla Escolha</option><option value='Discursiva'>Discursiva</option></select><br>";
    html+="<div id='opcoesDiv'></div><button onclick='criarPergunta()'>Salvar</button>";
    document.getElementById('conteudo').innerHTML=html;
    document.getElementById('pergTipo').addEventListener('change',()=>{
        const tipo=document.getElementById('pergTipo').value;
        const div=document.getElementById('opcoesDiv'); div.innerHTML='';
        if(tipo==='Multipla Escolha'){
            ['1','2','3','4'].forEach((i)=>{ div.innerHTML+="<input id='op"+i+"' placeholder='Opção "+i+"'> ";});
            div.innerHTML+="<select id='opCerta'><option value='a)'>a)</option><option value='b)'>b)</option><option value='c)'>c)</option><option value='d)'>d)</option></select>";
        }
    });
    document.getElementById('pergTipo').dispatchEvent(new Event('change'));
}

async function criarPergunta(){
    const enunciado=document.getElementById('pergEnunciado').value;
    const tipo=document.getElementById('pergTipo').value;
    let data={acao:'criarpergunta',enunciado:enunciado,tipo:tipo};
    if(tipo==='Multipla Escolha'){
        data.opcao1=document.getElementById('op1').value;
        data.opcao2=document.getElementById('op2').value;
        data.opcao3=document.getElementById('op3').value;
        data.opcao4=document.getElementById('op4').value;
        data.opcaocerta=document.getElementById('opCerta').value;
    }
    const res=await ajaxPost(data);
    showMsg(res.mensagem,res.sucesso);
    if(res.sucesso) listarTodas();
}

// ------------------- Responder perguntas -------------------
async function responderPerguntas(){
    const res=await ajaxPost({acao:'listar_perguntas'});
    if(res.sucesso){
        let html="<h4>Responder Perguntas</h4>";
        res.perguntas.forEach(p=>{
            html+="<div style='border:1px solid #ccc;padding:6px;margin:6px 0'><strong>"+p.enunciado+"</strong><br>";
            if(p.tipo==='Multipla Escolha'){
                ['a)','b)','c)','d)'].forEach((op,i)=>{
                    html+="<label><input type='radio' name='"+p.enunciado+"' value='"+op+"'> "+p.opcoes.split(' ')[i]+"</label><br>";
                });
                html+="<button onclick='responder(\""+p.enunciado+"\")'>Enviar</button>";
            }else{
                html+="<textarea id='resp_"+p.enunciado+"'></textarea><br><button onclick='responder(\""+p.enunciado+"\")'>Enviar</button>";
            }
            html+="</div>";
        });
        document.getElementById('conteudo').innerHTML=html;
    }
}

async function responder(enunciado){
    let resposta='';
    const radios=document.getElementsByName(enunciado);
    if(radios.length>0){ for(const r of radios) if(r.checked){resposta=r.value;break;} }
    else{ resposta=document.getElementById('resp_'+enunciado).value; }
    const res=await ajaxPost({acao:'responder',enunciado:enunciado,resposta:resposta});
    showMsg(res.mensagem,res.sucesso);
}

// ------------------- Ver respostas -------------------
async function verMinhasRespostas(){
    const res=await ajaxPost({acao:'ver_respostas',username_view:'<?php echo $_SESSION['username'] ?? ''; ?>'});
    if(res.sucesso){
        let html="<h4>Minhas Respostas</h4><table><tr><th>Pergunta</th><th>Resposta</th><th>Data</th></tr>";
        res.respostas.forEach(r=>{html+="<tr><td>"+r.pergunta+"</td><td>"+r.resposta+"</td><td>"+r.data+"</td></tr>";});
        html+="</table>";
        document.getElementById('conteudo').innerHTML=html;
    }
}
</script>
</body>
</html>
