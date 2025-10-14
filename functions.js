
const API = {
  ACCOUNTS: './api/accounts.php',
  PIPELINES: './api/pipelines.php',     // list/create pipelines
  STAGES: './api/stages.php',           // list/create/reorder + createPipeline
  STAGE_NUMBERS: './api/stage_numbers.php', // list/assign/move/move_to_pipeline
};

const DEFAULT_ACCOUNT_ID = 1;

// DOM
const $board      = document.getElementById('board');
const $formCol    = document.getElementById('form-col');
const $formCard   = document.getElementById('form-card');
const $btnRefresh = document.getElementById('btn-refresh');

// Estado
let state = {
  accountId: DEFAULT_ACCOUNT_ID,
  pipelines: [],
  pipelineId: null,
  stages: [],           // [{id,name,sort_order}]
  cardsByStage: {},     // stageId -> [cards]
};

async function apiGet(url){ const r=await fetch(url); if(!r.ok) throw new Error(`GET ${r.status}`); return r.json(); }
async function apiPost(url, data){
  const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  if(!r.ok) throw new Error(`POST ${r.status}`); return r.json();
}

function setPipelineName(name){ const el=document.getElementById('current-pipeline-name'); if(el) el.textContent=name; }
function escapeHtml(s){ if(!s) return s; return s.replace(/[&<>\"']/g, ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch])); }

function render() {
  $board.innerHTML = '';

  state.stages.forEach((stage, idx) => {
    const column = document.createElement('section');
    column.className = 'stage flex-grow-1';
    column.dataset.id = stage.id;

    const cards = state.cardsByStage[stage.id] || [];
    const isEntrada = (idx === 0); // primera columna = Entrada

    column.innerHTML = `
      <div class="stage-header">
        <div class="d-flex align-items-center gap-2">
          <div class="stage-title">${isEntrada ? 'Entrada' : escapeHtml(stage.name)}</div>
          <span class="badge text-bg-primary">${cards.length}</span>
        </div>
      </div>

      <div class="mb-2 kanban-search">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" placeholder="Buscar…" oninput="filterCards(this)">
        </div>
      </div>

      <div class="stage-body">
        <div class="cards" data-col="${stage.id}" data-is-entrada="${isEntrada ? '1':'0'}"></div>
      </div>

    `;

    $board.appendChild(column);

    const list = column.querySelector('.cards');
    cards.forEach(card => list.appendChild(renderCard(card)));

    new Sortable(list, {
      group: 'cards',
      animation: 150,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      onEnd: async (evt) => {
        const toStageId = evt.to.dataset.col;
        const fromStageId = evt.from.dataset.col;
        const toIndex = evt.newIndex;
        const fromIndex   = evt.oldIndex;
        const chatId = Number(evt.item.dataset.chatId);
        const fromIsEntrada = evt.from.dataset.isEntrada === '1';
        const toIsEntrada   = evt.to.dataset.isEntrada === '1';
        const isVirtualFrom = evt.item.dataset.virtual === '1';

        try {
            if (toStageId === fromStageId && toIndex === fromIndex) {
                return;
            }
          if (fromIsEntrada && !toIsEntrada) {
            // Entrada (puede ser virtual) -> Columna real  => assign
            await apiPost(`${API.STAGE_NUMBERS}?action=assign`, {
              stage_id: toStageId,
              chat_id: chatId,
              account_id: state.accountId,
            });
          } else if (!fromIsEntrada && toIsEntrada) {
            // Columna -> Entrada (misma pipeline) => move a la PRIMERA columna (Entrada real)
            await apiPost(`${API.STAGE_NUMBERS}?action=move`, {
              chat_id: chatId,
              to_stage_id: toStageId,
              to_index: toIndex
            });
          } else if (!fromIsEntrada && !toIsEntrada) {
            // Columna -> Columna
            await apiPost(`${API.STAGE_NUMBERS}?action=move`, {
              chat_id: chatId,
              to_stage_id: toStageId,
              to_index: toIndex
            });
          } else {
            // Entrada -> Entrada (reordenar visual, sin persistencia)
            // no-op
          }
        } catch (err) {
          console.error('DnD error:', err);
        } finally {
          await loadBoard();
        }
      }
    });
  });

  // drag de columnas
  new Sortable($board, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.stage-header',
    onEnd: async () => {
      const order = Array.from($board.children).map(el => el.dataset.id);
      try {
        await apiPost(`${API.STAGES}?action=reorder`, { order });
      } catch (e) {
        console.warn('Reordenar columnas no persistido');
      }
    },
  });

  // abrir modal "Agregar número"
  if ($formCard) {
    $board.querySelectorAll('.add-card').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const stageId = e.currentTarget.dataset.col;
        $formCard.reset();
        $formCard.column_id.value = stageId;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCard')).show();
      });
    });
  }
}

function renderCard(card) {
  const el = document.createElement('div');
  el.className = 'chat-card';
  el.dataset.chatId = card.chat_id;
  if (card.is_virtual) el.dataset.virtual = '1';

  const title = card.profile_name && card.profile_name.trim()
    ? `${escapeHtml(card.profile_name)}`
    : escapeHtml(card.fromnumber || '');

  const ts = card.last_ts ? new Date(card.last_ts).toLocaleString() : '';
  let last = card.last_text || '';
  let source_phone = card.source_phone || '';
  if (card.message_type === 'file' && last && !last.startsWith('[Archivo]')) last = `[Archivo] ${last}`;

  el.innerHTML = `
    <div class="d-flex justify-content-between align-items-start gap-2">
      <div class="flex-grow-1">
        <div class="d-flex justify-content-between">
          <div class="chat-title">${title}</div>
          <span class="chip">${ts}</span>
        </div>
        ${last ? `<div class="text-muted small mt-1">${last}</div>` : ''}
        <b><div class="text-muted small mt-1">${card.fromnumber} -> ${source_phone}</div></b>
      </div>
      <div class="dropdown ms-2">
        <button class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="dropdown" aria-expanded="false" title="Mover a otro pipeline (Entrada)">
          <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end move-menu" data-chat-id="${card.chat_id}">
          ${renderPipelinesDropdownItems()}
        </ul>
      </div>
    </div>
  `;
  return el;
}

function renderPipelinesDropdownItems() {
  const items = (state.pipelines || [])
    .filter(p => String(p.id) !== String(state.pipelineId))
    .map(p => `<li><a class="dropdown-item move-to-pipeline" data-pipeline="${p.id}" href="#">${escapeHtml(p.name)}</a></li>`)
    .join('');
  return items || '<li><span class="dropdown-item-text text-muted">No hay otros pipelines</span></li>';
}

document.addEventListener('click', async (e) => {
  const a = e.target.closest('.move-to-pipeline');
  if (!a) return;
  e.preventDefault();

  const pipelineId = parseInt(a.dataset.pipeline, 10);
  const menu = a.closest('ul.move-menu');
  const chatId = Number(menu?.dataset.chatId);
  if (!pipelineId || !chatId) return;

  try {
    // Mueve a la PRIMERA columna del pipeline destino
    await apiPost(`${API.STAGE_NUMBERS}?action=move_to_pipeline`, {
      to_pipeline_id: pipelineId,
      chat_id: chatId
    });
    await loadBoard();
  } catch (err) {
    console.error('No se pudo mover al pipeline destino', err);
  }
});

window.filterCards = function (input) {
  const q = (input.value || '').toLowerCase();
  const list = input.closest('.stage').querySelector('.cards');
  Array.from(list.children).forEach((cardEl) => {
    cardEl.style.display = cardEl.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
};

async function loadPipelines(accountId = DEFAULT_ACCOUNT_ID) {
  const data = await apiGet(`${API.PIPELINES}?action=list&account_id=${accountId}`);
  const rows = data.rows || [];
  state.pipelines = rows;
  if (!state.pipelineId && rows[0]) state.pipelineId = rows[0].id;

  const menu = document.getElementById('pipeline-menu');
  if (menu) {
    menu.innerHTML =
      '<li><h6 class="dropdown-header">Embudo</h6></li>' +
      rows.map(r => `<li><a class="dropdown-item pipeline-opt" data-id="${r.id}" href="#">${r.name}</a></li>`).join('') +
      '<li><hr class="dropdown-divider"></li>' +
      '<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalPipeline"><i class="bi bi-plus-circle me-2"></i>Nuevo pipeline</a></li>';

    menu.querySelectorAll('.pipeline-opt').forEach(a => {
      a.addEventListener('click', async (e) => {
        e.preventDefault();
        state.pipelineId = e.currentTarget.dataset.id;
        setPipelineName(e.currentTarget.textContent.trim());
        await loadBoard();
      });
    });
  }
  const current = rows.find(r => String(r.id) === String(state.pipelineId));
  if (current) setPipelineName(current.name);
}

async function loadBoard() {
  if (!state.pipelineId) return;

  // 1) stages
  const st = await apiGet(`${API.STAGES}?action=list&pipeline_id=${state.pipelineId}`);
  state.stages = st.rows || [];

  const nums = await apiGet(`${API.STAGE_NUMBERS}?action=list&pipeline_id=${state.pipelineId}`);
  state.cardsByStage = {};
  (nums.rows || []).forEach((r) => {
    (state.cardsByStage[r.pipelinestage_id] ||= []).push({
      chat_id: r.chat_id,
      fromnumber: r.fromnumber,
      source_phone: r.source_phone,
      last_text: r.last_text,
      last_ts: r.last_ts,
      profile_name: r.profile_name,
      origin: r.origin,
      message_type: r.message_type,
      is_virtual: r.is_virtual === 1 || r.is_virtual === '1',
    });
  });

  render();
}

// crear columna
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

// crear tarjeta manual
if ($formCard) {
  $formCard.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData($formCard);
    const stage_id = f.get('column_id');
    const chat_id  = parseInt(f.get('chat_id') || '0', 10);
    if (!stage_id || !chat_id) return;

    await apiPost(`${API.STAGE_NUMBERS}?action=assign`, {
      stage_id,
      chat_id,
      account_id: state.accountId,
    });

    bootstrap.Modal.getInstance(document.getElementById('modalCard')).hide();
    await loadBoard();
  });
}

if ($btnRefresh) $btnRefresh.addEventListener('click', loadBoard);

/* Boot */
loadPipelines(DEFAULT_ACCOUNT_ID).then(loadBoard).catch(console.error);

/* ---------- Crear pipeline ---------- */
// Crear pipeline
const formPipeline = document.getElementById('form-pipeline');
if (formPipeline) {
  formPipeline.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(formPipeline);
    const name = (f.get('name') || '').trim();
    if (!name) return;

    await apiPost(`${API.STAGES}?action=createPipeline`, {
      account_id: state.accountId,
      name,
    });

    bootstrap.Modal.getInstance(document.getElementById('modalPipeline')).hide();
    formPipeline.reset();
    await loadPipelines(state.accountId);
    await loadBoard();
  });
}


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

    bootstrap.Modal.getInstance(document.getElementById('modalAccount')).hide();
    formAccount.reset();

    await loadPipelines(state.accountId);
    await loadAccounts();
  });
}

/* ---------- Asignar número a cuenta (modalAccountNumber) ---------- */
const formAccountNumber = document.getElementById('form-accountnumber');
if (formAccountNumber) {
  document.getElementById('modalAccountNumber')?.addEventListener('show.bs.modal', async () => {
    await loadAccounts();
  });

  formAccountNumber.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(formAccountNumber);
    const account_id = parseInt(f.get('account_id'), 10);
    let number = (f.get('number') || '').trim();
    number = number.replace(/\s+/g, '');

    if (!account_id || !number) return;

    await apiPost('./api/accountnumbers.php?action=create', {
      account_id,
      number
    });

    bootstrap.Modal.getInstance(document.getElementById('modalAccountNumber')).hide();
    formAccountNumber.reset();
  });
}