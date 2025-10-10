<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pideaky CRM</title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Estilos propios -->
  <link rel="stylesheet" href="./css/styles.css">
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
            <!-- <span class="input-group-text bg-white"><i class="bi bi-search"></i></span> -->
            <!-- <input class="form-control" type="search" placeholder="Buscar chats, contactos…" aria-label="Buscar"> -->
          </div>
        </form>

        <span class="badge rounded-pill text-bg-success">Conectado</span>
      </div>
    </div>
  </nav>

  <main class="container-fluid mt-3">
    <!-- Controles superiores del tablero -->
    <div class="board-controls">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Menú de Pipelines (Embudos) -->
        <div class="dropdown me-2">
          <button class="btn btn-light border d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <i class="bi bi-diagram-3"></i>
            <span class="fw-semibold" id="current-pipeline-name"></span>
            <i class="bi bi-caret-down-fill small"></i>
          </button>
          <ul class="dropdown-menu" id="pipeline-menu">
            <!-- Se llena dinámicamente por app.v2.js -->
          </ul>
        </div>

        <span class="badge rounded-pill text-bg-light d-flex align-items-center gap-2">
          <i class="bi bi-people"></i> Individuales
          <span class="badge text-bg-success" id="badge-individuales">0</span>
        </span>

        <span class="badge rounded-pill text-bg-light d-flex align-items-center gap-2">
          <i class="bi bi-person-lines-fill"></i> Total: <span id="badge-total">0</span> contactos
        </span>

        <div class="ms-auto d-flex align-items-center gap-2">
          <button id="btn-refresh" class="btn btn-light"><i class="bi bi-arrow-repeat"></i> Actualizar</button>
          <button id="btn-new-col" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalCol">
            <i class="bi bi-plus-circle"></i> Nueva Columna
          </button>

          <button id="btn-new-account" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAccount">
            <i class="bi bi-building"></i> Nueva Cuenta
          </button>

          <button id="btn-assign-number" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAccountNumber">
            <i class="bi bi-hash"></i> Asignar Número
          </button>

          <button class="btn btn-primary"><i class="bi bi-envelope"></i> Envío Masivo</button>
        </div>
      </div>
    </div>

    <!-- Board -->
    <div class="board-wrapper mt-2">
      <div id="board" class="board d-flex flex-nowrap"><!-- Columnas se renderizan aquí --></div>
    </div>
  </main>

  <!-- Modal: Nueva Columna -->
  <div class="modal fade" id="modalCol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-col">
        <div class="modal-header">
          <h5 class="modal-title">Nueva columna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="name" placeholder="Ej. New Leads" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Crear</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Nuevo Pipeline -->
  <div class="modal fade" id="modalPipeline" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-pipeline">
        <div class="modal-header">
          <h5 class="modal-title">Nuevo pipeline</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre del pipeline</label>
            <input type="text" class="form-control" name="name" placeholder="Ej. Clientes nuevos" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Crear</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Nueva Tarjeta / Número -->
  <div class="modal fade" id="modalCard" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-card">
        <div class="modal-header">
          <h5 class="modal-title">Agregar número</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="column_id">
          <div class="mb-3">
            <label class="form-label">Número o identificador del chat</label>
            <input type="text" class="form-control" name="title" placeholder="Ej. 5215551234567" required>
          </div>
          <div class="text-muted small">Se asignará este número a la columna seleccionada.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Nueva Cuenta -->
  <div class="modal fade" id="modalAccount" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-account">
        <div class="modal-header">
          <h5 class="modal-title">Nueva cuenta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre de la cuenta</label>
            <input type="text" class="form-control" name="name" placeholder="Ej. Pideaky" required>
          </div>
          <div class="text-muted small">Crea una organización/tenant para agrupar pipelines y números.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Crear</button>
        </div>
      </form>
    </div>
  </div>

    <!-- Modal: Asignar Número a Cuenta -->
  <div class="modal fade" id="modalAccountNumber" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="form-accountnumber">
        <div class="modal-header">
          <h5 class="modal-title">Asignar número a cuenta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Cuenta</label>
            <select class="form-select" name="account_id" id="select-account" required>
              <!-- Se llena dinámicamente desde JS -->
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Número</label>
            <input type="text" class="form-control" name="number" placeholder="Ej. 5215551234567" required>
          </div>
          <div class="text-muted small">
            Solo registra el número en la cuenta. (Para ponerlo en una columna, usa el modal “Agregar número” del pipeline o arrástralo luego.)
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>



  <!-- JS: Bootstrap Bundle + SortableJS + App -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script src="functions.js"></script>
</body>
</html>
