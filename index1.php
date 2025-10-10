<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pideaky CRM — Sales Funnel (Mock)</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

    /* Top pills */
    .status-pill{
      background:#e8fff0; color:#1a7f3b; border-radius: 999px; padding: .35rem .75rem; font-weight:600;
    }

    /* Board */
    .board{ gap:1rem; }

    .stage{
      min-width: 260px;
      border-radius: var(--card-radius);
      padding: .75rem; 
      height: 70vh;
      display:flex; flex-direction:column;
    }
    .stage-header{
      display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.5rem;
    }
    .stage-title{ font-weight:700; font-size:1.05rem; }
    .stage-body{
      background:white; border-radius: var(--card-radius); padding:1rem; flex:1; overflow:auto;
      border:1px solid rgba(0,0,0,.05);
    }

    .empty{
      display:grid; place-items:center; height:100%; color:#73839b; text-align:center; gap:.5rem;
    }
    .empty .icon{ font-size:2rem; opacity:.6; }

    .chat-card{
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 14px; background:#fff; padding:.85rem; margin-bottom:.75rem;
      box-shadow: 0 1px 0 rgba(0,0,0,.03);
    }
    .chat-title{ font-weight:700; display:flex; align-items:center; gap:.5rem; }
    .chip{
      background:#eaf5ff; border-radius:999px; font-size:.8rem; padding:.15rem .5rem; font-weight:700; color:#1e6cff;
    }
    .kanban-search .form-control{ border-radius: 12px; }

    /* Column colors similar to screenshot */
    .new-leads    { background: var(--col-new); }
    .scheduling   { background: var(--col-scheduling); }
    .booked       { background: var(--col-booked); }
    .assistant    { background: var(--col-assistant); }
    .rescheduling { background: var(--col-resched); }

    .toolbar .btn{ border-radius: 12px; }

    /* Sticky board header controls */
    .board-controls{ position: sticky; top:0; z-index: 1030; background:#fafbff; padding:.75rem 0; }

    @media (max-width: 991.98px){
      .board-wrapper{ overflow-x:auto; }
      .stage{ width: 320px; height: 68vh; }
    }
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
          <li class="nav-item"><a class="nav-link" href="#">Pideaky <span class="badge text-bg-light ms-1">BÁSICO</span></a></li>
          <li class="nav-item"><a class="nav-link active fw-semibold" aria-current="page" href="#">Sales Funnel</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Agenda</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Plantillas</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Envíos</a></li>
          <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Facturación</a></li>
        </ul>

        <!-- Right controls -->
        <form class="d-none d-lg-flex me-3" role="search" style="width: 340px;">
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" placeholder="Buscar chats, contactos…" aria-label="Buscar">
          </div>
        </form>
        <span class="status-pill me-3 d-none d-md-inline-flex"><i class="bi bi-circle-fill me-1" style="font-size: .6rem;"></i>Conectado</span>
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-pill text-bg-primary">0</span>
          <div class="dropdown">
            <a class="d-inline-flex align-items-center text-decoration-none" href="#" data-bs-toggle="dropdown">
              <span class="me-2 text-end small lh-1">
                <div class="fw-semibold">oscar</div>
                <div class="text-muted">Administrador</div>
              </span>
              <img src="https://i.pravatar.cc/40?img=12" class="rounded-circle" width="40" height="40" alt="avatar">
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#">Perfil</a></li>
              <li><a class="dropdown-item" href="#">Ajustes</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#">Cerrar sesión</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main class="container-fluid mt-3">

    <!-- Board top controls -->
    <div class="board-controls">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="dropdown me-2">
          <button class="btn btn-light border d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <i class="bi bi-funnel"></i>
            <span class="fw-semibold">Sales Funnel</span>
            <i class="bi bi-caret-down-fill small"></i>
          </button>
          <ul class="dropdown-menu">
            <li><h6 class="dropdown-header">Embudo</h6></li>
            <li><a class="dropdown-item active" href="#">Sales Funnel</a></li>
            <li><a class="dropdown-item" href="#">Soporte</a></li>
          </ul>
        </div>

        <span class="badge rounded-pill text-bg-light d-flex align-items-center gap-2">
          <i class="bi bi-people"></i> Individuales <span class="badge text-bg-success">7</span>
        </span>
        <span class="badge rounded-pill text-bg-light d-flex align-items-center gap-2">
          <i class="bi bi-person-lines-fill"></i> Total: 9 contactos
        </span>

        <div class="ms-auto toolbar d-flex align-items-center gap-2">
          <button class="btn btn-light"><i class="bi bi-bell"></i></button>
          <button class="btn btn-light"><i class="bi bi-arrow-repeat"></i> Actualizar</button>
          <button class="btn btn-light"><i class="bi bi-plus-circle"></i> Nueva Columna</button>
          <button class="btn btn-primary"><i class="bi bi-envelope"></i> Envío Masivo</button>
        </div>
      </div>
    </div>

    <!-- Board -->
    <div class="board-wrapper">
      <div class="board d-flex flex-nowrap">

        <!-- New Leads -->
        <section class="stage new-leads flex-grow-1">
          <div class="stage-header">
            <div class="d-flex align-items-center gap-2">
              <div class="stage-title">New Leads</div>
              <span class="badge text-bg-primary">0</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-gear text-muted"></i>
            </div>
          </div>
          <div class="mb-2 kanban-search">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" placeholder="Buscar chat…" />
              <button class="btn btn-light"><i class="bi bi-funnel"></i></button>
            </div>
          </div>
          <div class="stage-body">
            <div class="empty">
              <i class="bi bi-window-dock icon"></i>
              <div>
                <div class="fw-semibold">No hay chats en esta etapa</div>
                <small class="text-muted">Arrastra chats aquí para organizarlos</small>
              </div>
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-chat"></i> Iniciar Conversación</button>
          </div>
        </section>

        <!-- Scheduling -->
        <section class="stage scheduling flex-grow-1">
          <div class="stage-header">
            <div class="d-flex align-items-center gap-2">
              <div class="stage-title">Scheduling</div>
              <span class="badge text-bg-warning">0</span>
            </div>
            <i class="bi bi-gear text-muted"></i>
          </div>
          <div class="mb-2 kanban-search">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" placeholder="Buscar chat…" />
              <button class="btn btn-light"><i class="bi bi-funnel"></i></button>
            </div>
          </div>
          <div class="stage-body">
            <div class="empty">
              <i class="bi bi-window-dock icon"></i>
              <div>
                <div class="fw-semibold">No hay chats en esta etapa</div>
                <small class="text-muted">Arrastra chats aquí para organizarlos</small>
              </div>
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-chat"></i> Iniciar Conversación</button>
          </div>
        </section>

        <!-- Booked -->
        <section class="stage booked flex-grow-1">
          <div class="stage-header">
            <div class="d-flex align-items-center gap-2">
              <div class="stage-title">Booked</div>
              <span class="badge text-bg-success">5</span>
              <span class="badge text-bg-primary">2</span>
            </div>
            <i class="bi bi-gear text-muted"></i>
          </div>
          <div class="mb-2 kanban-search">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" placeholder="Buscar chat…" />
              <button class="btn btn-light"><i class="bi bi-funnel"></i></button>
            </div>
          </div>
          <div class="stage-body">
            <div class="chat-card">
              <div class="d-flex justify-content-between">
                <div class="chat-title">Mar <span class="">🪄</span></div>
                <span class="chip">5</span>
              </div>
              <div class="text-muted small mt-1">si</div>
              <div class="d-flex align-items-center gap-2 mt-2 small text-muted">
                <i class="bi bi-whatsapp"></i>
                <span>5215552503314</span>
              </div>
            </div>
            <div class="chat-card">
              <div class="d-flex justify-content-between">
                <div class="chat-title">Rodrigo Tejeda</div>
                <span class="chip">0</span>
              </div>
              <div class="small text-muted mt-1"><i class="bi bi-image"></i> Captura de pantalla 2025-09-27 11</div>
              <div class="d-flex align-items-center gap-2 mt-2 small text-muted">
                <i class="bi bi-whatsapp"></i>
                <span>5218781154474</span>
              </div>
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-chat"></i> Iniciar Conversación</button>
          </div>
        </section>

        <!-- Assistant -->
        <section class="stage assistant flex-grow-1">
          <div class="stage-header">
            <div class="d-flex align-items-center gap-2">
              <div class="stage-title">Asistant</div>
              <span class="badge text-bg-primary">0</span>
            </div>
            <i class="bi bi-gear text-muted"></i>
          </div>
          <div class="mb-2 kanban-search">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" placeholder="Buscar chat…" />
              <button class="btn btn-light"><i class="bi bi-funnel"></i></button>
            </div>
          </div>
          <div class="stage-body">
            <div class="empty">
              <i class="bi bi-window-dock icon"></i>
              <div>
                <div class="fw-semibold">No hay chats en esta etapa</div>
                <small class="text-muted">Arrastra chats aquí para organizarlos</small>
              </div>
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-chat"></i> Iniciar Conversación</button>
          </div>
        </section>

        <!-- Rescheduling -->
        <section class="stage rescheduling flex-grow-1">
          <div class="stage-header">
            <div class="d-flex align-items-center gap-2">
              <div class="stage-title">Rescheduling</div>
              <span class="badge text-bg-primary">1</span>
            </div>
            <i class="bi bi-gear text-muted"></i>
          </div>
          <div class="mb-2 kanban-search">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" placeholder="Buscar chat…" />
              <button class="btn btn-light"><i class="bi bi-funnel"></i></button>
            </div>
          </div>
          <div class="stage-body">
            <div class="chat-card">
              <div class="chat-title">Oscar</div>
              <div class="d-flex align-items-center gap-2 mt-2 small text-muted">
                <i class="bi bi-whatsapp"></i>
                <span>brinnandebil…</span>
              </div>
              <div class="d-flex align-items-center gap-2 mt-2 small text-muted">
                <i class="bi bi-telephone"></i>
                <span>5215560100…</span>
              </div>
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-chat"></i> Iniciar Conversación</button>
          </div>
        </section>

      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
