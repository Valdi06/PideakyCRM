
const API = {
  ACCOUNTS: './api/accounts.php',
  PIPELINES: './api/pipelines.php',
  STAGES: './api/stages.php',
  STAGE_NUMBERS: './api/stage_numbers.php',
};

// 1 como predeterminado
const DEFAULT_ACCOUNT_ID = 1;

const $board     = document.getElementById('board');
const $formCol   = document.getElementById('form-col');
const $formCard  = document.getElementById('form-card');
const $btnRefresh = document.getElementById('btn-refresh');

// Estado de la app en memoria
let state = {
  accountId: DEFAULT_ACCOUNT_ID,
  pipelineId: null,     
  stages: [],           
  cardsByStage: {}, 
};

/* ----------------------------- Utils HTTP ----------------------------- */
async function apiGet(url) {
  const r = await fetch(url);
  if (!r.ok) throw new Error(`HTTP ${r.status} en GET ${url}`);
  return r.json();
}

async function apiPost(url, data) {
  const r = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data),
  });
  if (!r.ok) throw new Error(`HTTP ${r.status} en POST ${url}`);
  return r.json();
}

/* ---------------------------- Render helpers -------------------------- */
function setPipelineName(name) {
  const el = document.getElementById('current-pipeline-name'); 
  if (el) el.textContent = name;
}

function escapeHtml(s) {
  if (!s) return s;
  return s.replace(/[&<>\"']/g, (ch) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[ch]));
}

/* ------------------------------- Render UI ---------------------------- */
function render() {
  // Limpiar board
  $board.innerHTML = '';

  // Construir cada columna (stage)
  state.stages.forEach((stage) => {
    const column = document.createElement('section');
    column.className = 'stage flex-grow-1';
    column.dataset.id = stage.id;

    column.innerHTML = `
      <div class="stage-header">
        <div class="d-flex align-items-center gap-2">
          <div class="stage-title">${stage.name}</div>
          <span class="badge text-bg-primary">${(state.cardsByStage[stage.id] || []).length}</span>
        </div>
      </div>

      <div class="mb-2 kanban-search">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" placeholder="Buscar…" oninput="filterCards(this)">
        </div>
      </div>

      <div class="stage-body">
        <div class="cards" data-col="${stage.id}"></div>
      </div>

      <div class="mt-2">
        <button class="btn btn-outline-secondary w-100 add-card" data-col="${stage.id}">
          <i class="bi bi-chat"></i> Agregar número
        </button>
      </div>
    `;

    $board.appendChild(column);

    // Render de tarjetas (numbers) dentro de la columna
    const list = column.querySelector('.cards');
    (state.cardsByStage[stage.id] || []).forEach((card) => {
      list.appendChild(renderCard(card));
    });

    // Drag & drop de tarjetas entre columnas
    new Sortable(list, {
      group: 'cards',
      animation: 150,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      onEnd: async (evt) => {
        const fromnumber = evt.item.dataset.fromnumber;
        const toStageId  = evt.to.dataset.col;
        const toIndex    = evt.newIndex;

        
        moveCardLocal(fromnumber, toStageId, toIndex);

        // Persistir en backend
        try {
          await apiPost(`${API.STAGE_NUMBERS}?action=move`, {
            fromnumber,
            to_stage_id: toStageId,
            to_index: toIndex,
          });
        } catch (err) {
          console.error('Error moviendo tarjeta:', err);
          // Si algo falla, recarga desde el servidor para corregir el estado
          await loadBoard();
        }
      },
    });
  });

  // Drag & drop de columnas
  new Sortable($board, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.stage-header',
    onEnd: async () => {
      const order = Array.from($board.children).map((el) => el.dataset.id);
      try {
        await apiPost(`${API.STAGES}?action=reorder`, { order });
      } catch (e) {
        
        console.warn('No se persistió el orden de columnas (falta sort_order en BD).');
      }
    },
  });

  // Listeners para abrir modal de "Nueva tarjeta" (assign number)
  $board.querySelectorAll('.add-card').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const stageId = e.currentTarget.dataset.col;
      $formCard.reset();
      
      $formCard.column_id.value = stageId;
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCard')).show();
    });
  });

  // Actualizar contador total (opcional)
  const total = Object.values(state.cardsByStage)
    .reduce((acc, arr) => acc + arr.length, 0);

  const $badgeTotal = document.getElementById('badge-total');
  if ($badgeTotal) $badgeTotal.textContent = total;

  const $badgeInd = document.getElementById('badge-individuales');
  if ($badgeInd) $badgeInd.textContent = total;
}

function renderCard(card) {
  // card = { fromnumber, last_text?, last_ts? }
  const el = document.createElement('div');
  el.className = 'chat-card';
  el.dataset.fromnumber = card.fromnumber;

  const ts = card.last_ts ? new Date(card.last_ts).toLocaleString() : '';
  const textHtml = card.last_text
    ? `<div class="text-muted small mt-1">${card.last_text}</div>`
    : '';
  const phonenumber = card.fromnumber
    ? `<span>${card.fromnumber}</span>`
    : '';
  const source_phone = card.source_phone
    ? `<span>${card.source_phone}</span>`
    : '';

  el.innerHTML = `
    <div class="d-flex justify-content-between">
      <div class="chat-title">${card.profile_name}</div>
      <span class="chip">${ts}</span>
    </div>
    ${textHtml}
    <b>${phonenumber} -> ${source_phone} </b>
  `;

  return el;
}


function moveCardLocal(fromnumber, toStageId, toIndex) {
  
  let cardObj = null;

  for (const sid of Object.keys(state.cardsByStage)) {
    const arr = state.cardsByStage[sid] || [];
    const i = arr.findIndex((c) => c.fromnumber === fromnumber);
    if (i > -1) {
      cardObj = arr.splice(i, 1)[0];
      break;
    }
  }

  if (!cardObj) return;

  // Insertar en el destino
  const dest = state.cardsByStage[toStageId] || (state.cardsByStage[toStageId] = []);
  const index = Math.max(0, Math.min(Number(toIndex), dest.length));
  dest.splice(index, 0, cardObj);

  
  render();
}

/* --------------------------- Buscador por columna ---------------------- */
window.filterCards = function (input) {
  const q = (input.value || '').toLowerCase();
  const list = input.closest('.stage').querySelector('.cards');
  Array.from(list.children).forEach((cardEl) => {
    cardEl.style.display = cardEl.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
};

/* --------------------------- Carga de datos ---------------------------- */

async function loadPipelines(accountId = DEFAULT_ACCOUNT_ID) {
  const data = await apiGet(`${API.PIPELINES}?action=list&account_id=${accountId}`);
  const rows = data.rows || [];

  
  if (!state.pipelineId && rows[0]) {
    state.pipelineId = rows[0].id;
  }

  
  const menu = document.getElementById('pipeline-menu');
  if (menu) {
    menu.innerHTML =
      '<li><h6 class="dropdown-header">Embudo</h6></li>' +
      rows.map((r) =>
        `<li>
           <a class="dropdown-item pipeline-opt" data-id="${r.id}" href="#">${r.name}</a>
         </li>`
      ).join('') +
      '<li><hr class="dropdown-divider"></li>' +
      '<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalPipeline" id="btn-new-pipeline"><i class="bi bi-plus-circle me-2"></i>Nuevo pipeline</a></li>';

    // Cambiar de pipeline
    menu.querySelectorAll('.pipeline-opt').forEach((a) => {
      a.addEventListener('click', async (e) => {
        e.preventDefault();
        state.pipelineId = e.currentTarget.dataset.id;
        setPipelineName(e.currentTarget.textContent.trim());
        await loadBoard();
      });
    });

  }

  const current = rows.find((r) => String(r.id) === String(state.pipelineId));
  if (current) setPipelineName(current.name);
}


async function loadBoard() {
  if (!state.pipelineId) return;

  // 1) Stages
  const st = await apiGet(`${API.STAGES}?action=list&pipeline_id=${state.pipelineId}`);
  state.stages = st.rows || [];

  // 2) Tarjetas por stage + último mensaje
  const nums = await apiGet(`${API.STAGE_NUMBERS}?action=list&pipeline_id=${state.pipelineId}`);
  state.cardsByStage = {};
  (nums.rows || []).forEach((r) => {
    if (!state.cardsByStage[r.pipelinestage_id]) {
      state.cardsByStage[r.pipelinestage_id] = [];
    }
    state.cardsByStage[r.pipelinestage_id].push({
      fromnumber: r.fromnumber,
      last_text: r.last_text,
      last_ts: r.last_ts,
      profile_name: r.profile_name,
      source_phone: r.source_phone,
    });
  });

  render();
}

/* ------------------------------- Eventos ------------------------------- */
// Crear columna (stage)
if ($formCol) {
  $formCol.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData($formCol);
    const name = (f.get('name') || '').trim();
    if (!name) return;

    await apiPost(`${API.STAGES}?action=create`, {
      pipeline_id: state.pipelineId,
      name,
    });

    bootstrap.Modal.getInstance(document.getElementById('modalCol')).hide();
    $formCol.reset();
    await loadBoard();
  });
}

// Crear tarjeta (assign number a un stage)
if ($formCard) {
  $formCard.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData($formCard);
    const stage_id   = f.get('column_id');         
    const title      = (f.get('title') || '').trim();
    const fromnumber = title.replace(/\s+/g, '');  

    if (!stage_id || !fromnumber) return;

    await apiPost(`${API.STAGE_NUMBERS}?action=assign`, {
      stage_id,
      fromnumber,
      account_id: state.accountId,
    });

    bootstrap.Modal.getInstance(document.getElementById('modalCard')).hide();
    await loadBoard();
  });
}

// Botón de refrescar tablero manualmente
if ($btnRefresh) {
  $btnRefresh.addEventListener('click', loadBoard);
}

/* --------------------------------- Boot -------------------------------- */
loadPipelines(DEFAULT_ACCOUNT_ID)
  .then(loadBoard)
  .catch(console.error);

  // Crear pipeline (desde modal)
const formPipeline = document.getElementById('form-pipeline');
if (formPipeline) {
  formPipeline.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(formPipeline);
    const name = (f.get('name') || '').trim();
    if (!name) return;

    await apiPost(`${API.PIPELINES}?action=save`, {
      account_id: state.accountId,
      name,
    });

    bootstrap.Modal.getInstance(document.getElementById('modalPipeline')).hide();
    formPipeline.reset();
    await loadPipelines(state.accountId);
    await loadBoard();
  });
}


// ***********
/* ---------- Utils de cuentas ---------- */
async function loadAccounts() {
  const data = await apiGet(`${API.ACCOUNTS}?action=list`);
  const rows = data.rows || [];

  const sel = document.getElementById('select-account');
  if (sel) {
    sel.innerHTML = rows.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
  }

  return rows;
}

/* ---------- Crear cuenta (modalAccount) ---------- */
const formAccount = document.getElementById('form-account');
if (formAccount) {
  formAccount.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(formAccount);
    const name = (f.get('name') || '').trim();
    if (!name) return;

    await apiPost(`${API.ACCOUNTS}?action=create`, { name });

    // cerrar modal + refrescar listas
    bootstrap.Modal.getInstance(document.getElementById('modalAccount')).hide();
    formAccount.reset();

    // refresca menú de pipelines y select de cuentas
    await loadPipelines(state.accountId);
    await loadAccounts();
  });
}

/* ---------- Asignar número a cuenta (modalAccountNumber) ---------- */
const formAccountNumber = document.getElementById('form-accountnumber');
if (formAccountNumber) {
  // cuando se abra el modal, carga las cuentas en el select
  document.getElementById('modalAccountNumber')?.addEventListener('show.bs.modal', async () => {
    await loadAccounts();
  });

  formAccountNumber.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(formAccountNumber);
    const account_id = parseInt(f.get('account_id'), 10);
    let number = (f.get('number') || '').trim();
    number = number.replace(/\s+/g, ''); // normaliza espacios

    if (!account_id || !number) return;

    await apiPost('./api/accountnumbers.php?action=create', {
      account_id,
      number
    });

    bootstrap.Modal.getInstance(document.getElementById('modalAccountNumber')).hide();
    formAccountNumber.reset();

  });
}
