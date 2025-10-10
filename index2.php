<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CRM Kanban — Plantilla Reutilizable</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SortableJS for drag & drop real -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <style>
    :root{
      --col-new: #e9f1ff;      /* light blue */
      --col-scheduling:#fdeed9;/* light peach */
      --col-booked:#eaf2ff;    /* light blue-ish */
      --col-assistant:#eaf2ff; /* same as screenshot */
      --col-resched:#eff5ff;   /* very light */
      --card-radius: 16px;
    }

    body { background:#fafbff; }

    /* Board */
    .board{ gap:1rem; }

    .stage{
      min-width: 280px;
      border-radius: var(--card-radius);
      padding: .75rem; 
      height: 70vh;
      display:flex; flex-direction:column;
      background:var(--bg, #edf2ff);
    }
    .stage-header{ display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.5rem; }
    .stage-title{ font-weight:700; font-size:1.05rem; }
    .stage-body{ background:white; border-radius: var(--card-radius); padding:1rem; flex:1; overflow:auto; border:1px solid rgba(0,0,0,.05); }

    .chat-card{ border: 1px solid rgba(0,0,0,.06); border-radius: 14px; background:#fff; padding:.85rem; margin-bottom:.75rem; box-shadow: 0 1px 0 rgba(0,0,0,.03); }
    .chat-title{ font-weight:700; display:flex; align-items:center; gap:.5rem; }
    .chip{ background:#eaf5ff; border-radius:999px; font-size:.8rem; padding:.15rem .5rem; font-weight:700; color:#1e6cff; }
    .kanban-search .form-control{ border-radius: 12px; }

    .board-controls{ position: sticky; top:0; z-index: 1030; background:#fafbff; padding:.75rem 0; }

    @media (max-width: 991.98px){ .board-wrapper{ overflow-x:auto; } .stage{ width: 320px; height: 68vh; } }

    /* Drag ghosts */
    .sortable-ghost{ opacity:.6; }
    .sortable-chosen{ transform: rotate(.5deg); }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-2">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
        <i class="bi bi-chat-left-text"></i> Pideaky CRM
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link active fw-semibold" aria-current="page" href="#">Sales Funnel</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Agenda</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Plantillas</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Envíos</a></li>
        </ul>

        <form class="d-none d-lg-flex me-3" role="search" style="width: 340px;">
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" placeholder="Buscar chats, contactos…" aria-label="Buscar">
          </div>
        </form>
        <span class="badge rounded-pill text-bg-success">Conectado</span>
      </div>
    </div>
  </nav>

  <main class="container-fluid mt-3">
    <div class="board-controls">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="dropdown me-2">
          <button class="btn btn-light border d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <i class="bi bi-funnel"></i>
            <span class="fw-semibold">Sales Funnel</span>
            <i class="bi bi-caret-down-fill small"></i>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item active" href="#">Sales Funnel</a></li>
            <li><a class="dropdown-item" href="#">Soporte</a></li>
          </ul>
        </div>

        <span class="badge rounded-pill text-bg-light d-flex align-items-center gap-2">
          <i class="bi bi-people"></i> Individuales <span class="badge text-bg-success" id="badge-individuales">0</span>
        </span>
        <span class="badge rounded-pill text-bg-light d-flex align-items-center gap-2">
          <i class="bi bi-person-lines-fill"></i> Total: <span id="badge-total">0</span> contactos
        </span>

        <div class="ms-auto d-flex align-items-center gap-2">
          <button id="btn-refresh" class="btn btn-light"><i class="bi bi-arrow-repeat"></i> Actualizar</button>
          <button id="btn-new-col" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalCol"><i class="bi bi-plus-circle"></i> Nueva Columna</button>
          <button class="btn btn-primary"><i class="bi bi-envelope"></i> Envío Masivo</button>
        </div>
      </div>
    </div>

    <!-- Board -->
    <div class="board-wrapper">
      <div id="board" class="board d-flex flex-nowrap"></div>
    </div>
  </main>

  <!-- Modal: Nueva Columna -->
  <div class="modal fade" id="modalCol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-col">
        <div class="modal-header">
          <h5 class="modal-title">Nueva columna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Color de fondo (opcional)</label>
            <input type="color" class="form-control form-control-color" name="bg" value="#eaf2ff">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Crear</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Nueva Tarjeta -->
  <div class="modal fade" id="modalCard" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-card">
        <div class="modal-header">
          <h5 class="modal-title">Nueva tarjeta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="column_id">
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" class="form-control" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" rows="3" name="desc"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Crear</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    /**
     * FRONTEND REUTILIZABLE con Drag & Drop real (SortableJS)
     * Persistencia por API (endpoints ejemplo al final del archivo en comentarios)
     * Si la API falla, usa localStorage como fallback.
     */

    const API = {
      BOARD: '/api/board',               // GET (obtener board)
      CREATE_COL: '/api/columns',        // POST {name, bg}
      REORDER_COLS: '/api/reorder-columns', // POST {order:[colId]}
      CREATE_CARD: '/api/cards',         // POST {column_id, title, desc}
      MOVE_CARD: '/api/move-card',       // POST {card_id, to_column_id, to_index}
    };

    const $board = document.getElementById('board');
    const $formCol = document.getElementById('form-col');
    const $formCard = document.getElementById('form-card');
    let boardState = { columns: [] };

    // Utilidades fetch con fallback
    async function apiGet(url){
      try{ const r = await fetch(url); if(!r.ok) throw 0; return await r.json(); }
      catch{ return JSON.parse(localStorage.getItem('boardState')||'{}'); }
    }
    async function apiPost(url, data){
      try{
        const r = await fetch(url,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
        if(!r.ok) throw 0; return await r.json();
      }catch(e){ console.warn('API offline, guardando en localStorage', e); persistLocal(); return {ok:true, offline:true}; }
    }
    function persistLocal(){ localStorage.setItem('boardState', JSON.stringify(boardState)); }

    // Renderizado
    function render(){
      $board.innerHTML = '';
      document.getElementById('badge-total').textContent = boardState.columns.reduce((a,c)=>a+c.cards.length,0);
      document.getElementById('badge-individuales').textContent = document.getElementById('badge-total').textContent;

      boardState.columns.forEach(col => {
        const colEl = document.createElement('section');
        colEl.className = 'stage flex-grow-1';
        colEl.style.setProperty('--bg', col.bg || '#eaf2ff');
        colEl.dataset.id = col.id;
        colEl.innerHTML = `
          <div class="stage-header">
            <div class="d-flex align-items-center gap-2">
              <div class="stage-title">${col.name}</div>
              <span class="badge text-bg-primary">${col.cards.length}</span>
            </div>
            <div class="dropdown">
              <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item add-card" href="#" data-col="${col.id}"><i class="bi bi-plus-circle me-2"></i>Nueva tarjeta</a></li>
              </ul>
            </div>
          </div>
          <div class="mb-2 kanban-search">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" placeholder="Buscar…" oninput="filterCards(this)">
            </div>
          </div>
          <div class="stage-body">
            <div class="cards" data-col="${col.id}"></div>
          </div>
          <div class="mt-2">
            <button class="btn btn-outline-secondary w-100 add-card" data-col="${col.id}"><i class="bi bi-chat"></i> Iniciar Conversación</button>
          </div>`;

        $board.appendChild(colEl);

        // Render cards
        const list = colEl.querySelector('.cards');
        col.cards.forEach(card => list.appendChild(renderCard(card)));

        // Sortable cards (cross-column)
        new Sortable(list, {
          group: 'cards',
          animation: 150,
          ghostClass: 'sortable-ghost',
          chosenClass: 'sortable-chosen',
          onEnd: async (evt)=>{
            const cardId = evt.item.dataset.id;
            const toColId = evt.to.dataset.col;
            const toIndex = evt.newIndex;
            moveCardInState(cardId, toColId, toIndex);
            persistLocal();
            await apiPost(API.MOVE_CARD, { card_id: cardId, to_column_id: toColId, to_index: toIndex });
            updateBadges();
          }
        });
      });

      // Sortable columns
      new Sortable($board, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        handle: '.stage-header',
        onEnd: async ()=>{
          // actualizar orden en estado
          const order = Array.from($board.children).map(el=>el.dataset.id);
          boardState.columns.sort((a,b)=> order.indexOf(a.id) - order.indexOf(b.id));
          persistLocal();
          await apiPost(API.REORDER_COLS, { order });
        }
      });

      // Listeners para agregar tarjeta
      $board.querySelectorAll('.add-card').forEach(btn => btn.addEventListener('click', (e)=>{
        e.preventDefault();
        const colId = e.currentTarget.dataset.col;
        $formCard.reset();
        $formCard.column_id.value = colId;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCard')).show();
      }));
    }

    function renderCard(card){
      const el = document.createElement('div');
      el.className = 'chat-card';
      el.dataset.id = card.id;
      el.innerHTML = `
        <div class="d-flex justify-content-between">
          <div class="chat-title">${card.title}</div>
          <span class="chip">${card.meta || ''}</span>
        </div>
        ${card.desc ? `<div class="text-muted small mt-1">${card.desc}</div>`:''}
      `;
      return el;
    }

    function moveCardInState(cardId, toColId, toIndex){
      // quitar de su columna actual
      let card;
      boardState.columns.forEach(c=>{
        const i = c.cards.findIndex(k=>k.id==cardId);
        if(i>-1){ card = c.cards.splice(i,1)[0]; }
      });
      // insertar en destino
      const dest = boardState.columns.find(c=>c.id==toColId);
      if(!dest) return;
      dest.cards.splice(toIndex,0,card);
    }

    function updateBadges(){
      document.querySelectorAll('.stage').forEach(stage=>{
        const id = stage.dataset.id;
        const col = boardState.columns.find(c=>c.id==id);
        if(col){ stage.querySelector('.badge').textContent = col.cards.length; }
      });
      document.getElementById('badge-total').textContent = boardState.columns.reduce((a,c)=>a+c.cards.length,0);
      document.getElementById('badge-individuales').textContent = document.getElementById('badge-total').textContent;
    }

    // Filtro rápido por columna
    window.filterCards = function(input){
      const q = input.value.toLowerCase();
      const list = input.closest('.stage').querySelector('.cards');
      Array.from(list.children).forEach(card=>{
        card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    }

    // Cargar tablero
    async function loadBoard(){
      const data = await apiGet(API.BOARD);
      // Estructura esperada: { columns: [ {id, name, bg?, order?, cards:[{id,title,desc?,meta?}]} ] }
      if(data && data.columns){ boardState = data; persistLocal(); }
      else if(localStorage.getItem('boardState')){ boardState = JSON.parse(localStorage.getItem('boardState')); }
      else{
        // Estado demo inicial
        boardState = { columns: [
          { id:'new', name:'New Leads', bg: '#e9f1ff', cards: [] },
          { id:'sched', name:'Scheduling', bg: '#fdeed9', cards: [] },
          { id:'booked', name:'Booked', bg: '#eaf2ff', cards: [
            { id:'c1', title:'Mar', desc:'si', meta:'5' },
            { id:'c2', title:'Rodrigo Tejeda', desc:'Captura 2025-09-27', meta:'0' },
          ]},
          { id:'assist', name:'Assistant', bg: '#eaf2ff', cards: [] },
          { id:'resched', name:'Rescheduling', bg: '#eff5ff', cards: [ { id:'c3', title:'Oscar', desc:'Contacto via WhatsApp', meta:'' } ] },
        ] };
        persistLocal();
      }
      render();
    }

    // Crear columna
    $formCol.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const form = new FormData($formCol);
      const payload = { name: form.get('name'), bg: form.get('bg') };
      const res = await apiPost(API.CREATE_COL, payload);
      const id = res.id || crypto.randomUUID();
      boardState.columns.push({ id, name: payload.name, bg: payload.bg, cards: [] });
      persistLocal();
      bootstrap.Modal.getInstance(document.getElementById('modalCol')).hide();
      render();
      await apiPost(API.REORDER_COLS, { order: boardState.columns.map(c=>c.id) });
    });

    // Crear tarjeta
    $formCard.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const form = new FormData($formCard);
      const payload = { column_id: form.get('column_id'), title: form.get('title'), desc: form.get('desc') };
      const res = await apiPost(API.CREATE_CARD, payload);
      const id = res.id || crypto.randomUUID();
      const col = boardState.columns.find(c=>c.id==payload.column_id);
      col.cards.push({ id, title: payload.title, desc: payload.desc, meta:'' });
      persistLocal();
      bootstrap.Modal.getInstance(document.getElementById('modalCard')).hide();
      render();
    });

    // Refresh manual
    document.getElementById('btn-refresh').addEventListener('click', loadBoard);

    loadBoard();
  </script>
</body>
</html>
