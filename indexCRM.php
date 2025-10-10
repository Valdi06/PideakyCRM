<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pideaky CRM</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Estilos locales -->
  <link rel="stylesheet" href="styles.css">
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

  <!-- Bootstrap Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SortableJS -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <!-- App -->
  <script src="functions.js"></script>
</body>
</html>