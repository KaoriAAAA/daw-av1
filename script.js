
async function api(endpoint, method = 'GET', body = null) {
  const opts = { method, headers: {} };
  if (body !== null) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  const resp = await fetch('api/' + endpoint, opts);
  let data;
  try {
    data = await resp.json();
  } catch (e) {
    throw { error: 'Invalid JSON response' };
  }
  if (!resp.ok) {
    throw data;
  }
  return data;
}

async function renderHeader() {
  const ui = document.getElementById('user-info');
  try {
    const who = await api('login.php', 'GET');
    if (who.logged) {
      ui.innerHTML = `Logado: ${who.nome} (${who.role}) <button id="btnLogout">Sair</button>`;
      document.getElementById('btnLogout').onclick = async () => {
        await api('login.php', 'POST', { action: 'logout' });
        loadApp();
      };
      return who;
    } else {
      ui.innerHTML = `Não logado`;
      return { logged: false };
    }
  } catch (err) {
    ui.innerHTML = `Erro ao verificar login`;
    return { logged: false };
  }
}

function setupMenu(who) {
  const menu = document.getElementById('menu');
  menu.innerHTML = '';
  if (!who.logged) return;
  const li1 = document.createElement('a');
  li1.textContent = 'Home';
  li1.onclick = () => showHome(who);
  menu.appendChild(li1);

  if (who.role === 'admin') {
    const a = document.createElement('a');
    a.textContent = 'Usuários';
    a.onclick = showUsersPage;
    menu.appendChild(a);

    const b = document.createElement('a');
    b.textContent = 'Perguntas';
    b.onclick = showQuestionsPage;
    menu.appendChild(b);
  }

  const c = document.createElement('a');
  c.textContent = 'Responder';
  c.onclick = showAnswerPage;
  menu.appendChild(c);

  const d = document.createElement('a');
  d.textContent = 'Minhas Respostas';
  d.onclick = showMyResponses;
  menu.appendChild(d);
}

function showHome(who) {
  const c = document.getElementById('content');
  c.innerHTML = `<h3>Bem-vindo, ${who.nome}!</h3>`;
}

function showLogin() {
  const c = document.getElementById('content');
  c.innerHTML = `
    <h3>Login</h3>
    <form id="loginForm">
      <label>Usuário:</label><br>
      <input type="text" name="username" required><br><br>
      <label>Senha:</label><br>
      <input type="password" name="password" required><br><br>
      <button type="submit">Entrar</button>
    </form>
    <div id="loginMsg" class="error"></div>
  `;
  document.getElementById('loginForm').onsubmit = async e => {
    e.preventDefault();
    const f = e.target;
    const username = f.username.value;
    const password = f.password.value;
    try {
      await api('login.php', 'POST', { username, password, action: 'login' });
      loadApp();
    } catch (err) {
      document.getElementById('loginMsg').innerText = err.message || err.error || 'Erro';
    }
  };
}

async function showUsersPage() {
  const c = document.getElementById('content');
  c.innerHTML = `
    <h3>Gerenciar Usuários</h3>
    <button id="btnNewUser">Novo Usuário</button>
    <div id="usersList"></div>
    <div id="userForm"></div>
    <div id="userMsg"></div>
  `;
  document.getElementById('btnNewUser').onclick = () => renderUserForm();

  async function loadList() {
    try {
      const users = await api('users.php', 'GET');
      const ul = document.getElementById('usersList');
      ul.innerHTML = '';
      users.forEach(u => {
        const div = document.createElement('div');
        div.innerHTML = `${u.username} — ${u.nome} — ${u.role}
          <button data-user="${u.username}" class="btnEdit">Editar</button>
          <button data-user="${u.username}" class="btnDel">Apagar</button>`;
        ul.appendChild(div);
      });
      document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.onclick = () => renderUserForm(btn.dataset.user);
      });
      document.querySelectorAll('.btnDel').forEach(btn => {
        btn.onclick = async () => {
          if (!confirm('Apagar usuário?')) return;
          try {
            await api('users.php', 'DELETE', { username: btn.dataset.user });
            document.getElementById('userMsg').innerText = 'Apagado com sucesso';
            loadList();
          } catch (err) {
            document.getElementById('userMsg').innerText = err.error || 'Erro';
          }
        };
      });
    } catch (err) {
      document.getElementById('usersList').innerText = err.error || 'Erro ao listar';
    }
  }

  function renderUserForm(username = null) {
    const fdiv = document.getElementById('userForm');
    let mode = username ? 'edit' : 'create';
    fdiv.innerHTML = `
      <h4>${mode === 'edit' ? 'Editar' : 'Criar'} usuário</h4>
      <form id="frmUser">
        ${mode === 'edit' ? `<input type="hidden" name="original" value="${username}">` : ''}
        <label>Usuário:</label><br>
        <input type="text" name="username" ${mode === 'edit' ? `value="${username}" readonly` : ''} required><br>
        <label>Senha:</label><br>
        <input type="password" name="senha" ${mode === 'edit' ? '' : 'required'}><br>
        <label>Nome:</label><br>
        <input type="text" name="nome" required><br>
        <label>Role:</label><br>
        <select name="role">
          <option value="funcionario">funcionario</option>
          <option value="admin">admin</option>
        </select><br><br>
        <button type="submit">${mode === 'edit' ? 'Salvar' : 'Criar'}</button>
      </form>
    `;
    document.getElementById('frmUser').onsubmit = async e => {
      e.preventDefault();
      const fm = e.target;
      const obj = {
        username: fm.username.value,
        senha: fm.senha.value,
        nome: fm.nome.value,
        role: fm.role.value
      };
      if (mode === 'edit') obj.original = fm.original.value;
      try {
        await api('users.php', mode === 'edit' ? 'PUT' : 'POST', obj);
        document.getElementById('userMsg').innerText = 'Sucesso';
        fdiv.innerHTML = '';
        loadList();
      } catch (err) {
        document.getElementById('userMsg').innerText = err.error || 'Erro';
      }
    };
  }

  loadList();
}

async function showQuestionsPage() {
  const c = document.getElementById('content');
  c.innerHTML = `
    <h3>Gerenciar Perguntas</h3>
    <button id="btnNewQ">Nova Pergunta</button>
    <div id="questionsList"></div>
    <div id="questionForm"></div>
    <div id="questionMsg"></div>
  `;
  document.getElementById('btnNewQ').onclick = () => renderQuestionForm();

  async function loadList() {
    try {
      const qs = await api('questions.php', 'GET');
      const ql = document.getElementById('questionsList');
      ql.innerHTML = '';
      qs.forEach(q => {
        const div = document.createElement('div');
        div.className = 'question-box';
        div.innerHTML = `<strong>${q.enunciado}</strong> (${q.tipo}) <br>
          <button data-q="${q.enunciado}" class="btnEditQ">Editar</button>
          <button data-q="${q.enunciado}" class="btnDelQ">Apagar</button>`;
        ql.appendChild(div);
      });
      document.querySelectorAll('.btnEditQ').forEach(b => {
        b.onclick = () => renderQuestionForm(b.dataset.q);
      });
      document.querySelectorAll('.btnDelQ').forEach(b => {
        b.onclick = async () => {
          if (!confirm('Apagar pergunta?')) return;
          try {
            await api('questions.php', 'DELETE', { enunciado: b.dataset.q });
            document.getElementById('questionMsg').innerText = 'Pergunta apagada';
            loadList();
          } catch (err) {
            document.getElementById('questionMsg').innerText = err.error || 'Erro';
          }
        };
      });
    } catch (err) {
      document.getElementById('questionsList').innerText = err.error || 'Erro listar';
    }
  }

  function renderQuestionForm(enunciado = null) {
    const fdiv = document.getElementById('questionForm');
    let mode = enunciado ? 'edit' : 'create';
    let qdata = null;
    if (mode === 'edit') {
      api('questions.php', 'GET').then(lst => {
        qdata = lst.find(q => q.enunciado === enunciado);
        buildForm();
      }).catch(err => {
        document.getElementById('questionMsg').innerText = 'Erro carregar';
      });
    } else {
      buildForm();
    }

    function buildForm() {
      const isDisc = (qdata && qdata.tipo === 'Discursiva');
      fdiv.innerHTML = `
        <h4>${mode === 'edit' ? 'Editar' : 'Nova'} Pergunta</h4>
        <form id="frmQ">
          ${mode === 'edit' ? `<input type="hidden" name="original" value="${qdata.enunciado}">` : ''}
          <label>Enunciado:</label><br>
          <input type="text" name="enunciado" value="${qdata ? qdata.enunciado : ''}" required><br><br>
          <label>Tipo:</label><br>
          <select name="tipo" id="selTipo">
            <option value="Multipla Escolha" ${isDisc ? '' : 'selected'}>Múltipla Escolha</option>
            <option value="Discursiva" ${isDisc ? 'selected' : ''}>Discursiva</option>
          </select><br><br>
          <div id="mcqFields" style="display: ${isDisc ? 'none' : 'block'};">
            <label>a):</label><br><input type="text" name="opA" value="${qdata ? qdata.opA : ''}" ><br>
            <label>b):</label><br><input type="text" name="opB" value="${qdata ? qdata.opB : ''}" ><br>
            <label>c):</label><br><input type="text" name="opC" value="${qdata ? qdata.opC : ''}" ><br>
            <label>d):</label><br><input type="text" name="opD" value="${qdata ? qdata.opD : ''}" ><br>
            <label>Opção correta:</label><br>
            <select name="opcaocerta">
              <option value="a)" ${qdata && qdata.opcaocerta==='a)' ? 'selected' : ''}>a)</option>
              <option value="b)" ${qdata && qdata.opcaocerta==='b)' ? 'selected' : ''}>b)</option>
              <option value="c)" ${qdata && qdata.opcaocerta==='c)' ? 'selected' : ''}>c)</option>
              <option value="d)" ${qdata && qdata.opcaocerta==='d)' ? 'selected' : ''}>d)</option>
            </select><br><br>
          </div>
          <button type="submit">${mode === 'edit' ? 'Salvar' : 'Criar'}</button>
        </form>
      `;
      const selTipo = document.getElementById('selTipo');
      selTipo.onchange = () => {
        const mcq = document.getElementById('mcqFields');
        mcq.style.display = selTipo.value === 'Discursiva' ? 'none' : 'block';
      };

      document.getElementById('frmQ').onsubmit = async e => {
        e.preventDefault();
        const fm = e.target;
        const obj = {
          enunciado: fm.enunciado.value,
          tipo: fm.tipo.value
        };
        if (fm.tipo.value !== 'Discursiva') {
          obj.opA = fm.opA.value;
          obj.opB = fm.opB.value;
          obj.opC = fm.opC.value;
          obj.opD = fm.opD.value;
          obj.opcaocerta = fm.opcaocerta.value;
        }
        if (mode === 'edit') obj.original = fm.original.value;
        try {
          await api('questions.php', mode === 'edit' ? 'PUT' : 'POST', obj);
          document.getElementById('questionMsg').innerText = 'Operação bem sucedida';
          fdiv.innerHTML = '';
          loadList();
        } catch (err) {
          document.getElementById('questionMsg').innerText = err.error || 'Erro';
        }
      };
    }
  }

  loadList();
}

async function showAnswerPage() {
  const c = document.getElementById('content');
  c.innerHTML = `<h3>Responder Perguntas</h3><div id="qsToAnswer"></div><div id="answerMsg"></div>`;
  try {
    const qs = await api('questions.php', 'GET');
    const container = document.getElementById('qsToAnswer');
    container.innerHTML = '';
    qs.forEach(q => {
      const div = document.createElement('div');
      div.className = 'question-box';
      div.innerHTML = `<strong>${q.enunciado}</strong><br>`;
      const form = document.createElement('form');
      form.dataset.enunciado = q.enunciado;
      if (q.tipo === 'Multipla Escolha') {
        ['a)', 'b)', 'c)', 'd)'].forEach(opt => {
          const label = document.createElement('label');
          label.innerHTML = `<input type="radio" name="resposta" value="${opt}" required> ${opt} ${q['op' + opt.charAt(0).toUpperCase()]}<br>`;
          form.appendChild(label);
        });
        form.appendChild(document.createElement('br'));
      } else {
        const ta = document.createElement('textarea');
        ta.name = 'resposta';
        ta.rows = 3;
        ta.cols = 60;
        ta.required = true;
        form.appendChild(ta);
        form.appendChild(document.createElement('br'));
      }
      const btn = document.createElement('button');
      btn.type = 'submit';
      btn.textContent = 'Enviar';
      form.appendChild(btn);

      form.onsubmit = async e => {
        e.preventDefault();
        const fm = e.target;
        const obj = {
          enunciado: fm.dataset.enunciado,
          resposta: fm.resposta.value
        };
        try {
          await api('responses.php', 'POST', obj);
          document.getElementById('answerMsg').innerText = 'Resposta enviada';
        } catch (err) {
          document.getElementById('answerMsg').innerText = err.error || 'Erro';
        }
      };

      div.appendChild(form);
      container.appendChild(div);
    });
  } catch (err) {
    container.innerText = err.error || 'Erro ao carregar';
  }
}

async function showMyResponses() {
  const c = document.getElementById('content');
  c.innerHTML = `<h3>Respostas</h3><div id="respList"></div><div id="respMsg"></div>`;
  try {
    let url = 'responses.php';
    const resps = await api(url, 'GET');
    const rl = document.getElementById('respList');
    rl.innerHTML = '';
    if (resps.length === 0) {
      rl.innerText = 'Nenhuma resposta registrada';
    } else {
      const tbl = document.createElement('table');
      tbl.border = "1";
      tbl.cellPadding = "6";
      const hdr = document.createElement('tr');
      hdr.innerHTML = '<th>Usuário</th><th>Pergunta</th><th>Resposta</th><th>Data</th>';
      tbl.appendChild(hdr);
      resps.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.username}</td><td>${r.enunciado}</td><td>${r.resposta}</td><td>${r.data}</td>`;
        tbl.appendChild(tr);
      });
      rl.appendChild(tbl);
    }
  } catch (err) {
    document.getElementById('respMsg').innerText = err.error || 'Erro';
  }
}

async function loadApp() {
  const who = await renderHeader();
  setupMenu(who);
  if (!who.logged) {
    showLogin();
  } else {
    showHome(who);
  }
}

window.onload = () => {
  loadApp().catch(e => console.error('Erro app:', e));
};
