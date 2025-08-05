<?php
include 'includes/session.php';
include 'includes/conn.php';
include 'includes/modals/indexmodal.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Gestor de Archivos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
  <style>
    body, html { height: 100%; margin: 0; }
    .d-flex { height: 100vh; overflow: hidden; }
    .dropzone { border: 2px dashed #007bff; border-radius: 6px; background: #f8f9fa; padding: 30px; }
    .folder-item:hover, .file-row:hover { background-color: #e9ecef; cursor: pointer; }
    .breadcrumb-item a { text-decoration: none; }
    .action-buttons button { margin-right: 0.3rem; }
      .selected-filename {
    color: blue !important;
    font-weight: bold;
  }
  </style>
</head>
<body>
  <!-- HABILITAR CUANDO YA ESTE HOSTEADO EN CASO CONTRARIO DA ERRORES INDESEADOS -->
<!--  <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=URL_DEL_ARCHIVO" width="100%" height="600px" frameborder="0"></iframe> -->

<?php include 'includes/navbar.php'; ?>
<div class="d-flex">
  <?php include 'includes/sidebar.php'; ?>
  <main class="p-4 flex-grow-1 overflow-auto">
    <h3>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h3>
    <nav aria-label="breadcrumb" class="mb-3">
      <ol id="breadcrumb-container" class="breadcrumb"></ol>
    </nav>
    <div class="mb-4">
      <form id="create-folder-form" style="display: inline-flex; gap: 0.5rem; align-items: center;">
        <input type="text" name="folder_name" class="form-control form-control-sm" placeholder="Nombre carpeta" required style="max-width: 200px;" />
        <button type="submit" class="btn btn-sm btn-success">Crear</button>
      </form>
    </div>
    <section class="mb-4">
      <h5>Subir archivos</h5>
      <form action="upload_files.php" class="dropzone" id="my-dropzone" enctype="multipart/form-data">
        <input type="hidden" name="targetFolder" value="" />
      </form>
    </section>
    <hr />
    <section class="mb-3">
      <h5>Carpetas</h5>
      <ul id="folder-list" class="list-group"></ul>
    </section>
    <section>
    <h5>Archivos
      <button id="delete-selected" class="btn btn-danger btn-sm ms-3" disabled>Eliminar seleccionados</button>
      <button id="download-selected" class="btn btn-primary btn-sm ms-1" disabled>Descargar seleccionados</button>
      <button id="copy-selected" class="btn btn-primary btn-sm ms-1" disabled>Copiar</button>
      <button id="cut-selected" class="btn btn-warning btn-sm ms-1" disabled>Cortar</button>
      <button id="paste-files" class="btn btn-success btn-sm ms-1" disabled>Pegar</button>
    </h5>
    <div id="file-list"></div>
  </section>
  </main>
</div>
<?php include 'includes/footer.php'; ?>

<!-- Agrega jQuery antes que los demás -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
let currentFolder = '';

function normalizePath(path) {
  path = path.replace(/\/+/g, '/');   // elimina barras repetidas
  path = path.replace(/^\/+/, '');    // elimina slash al inicio
  path = path.replace(/\/+$/, '');    // elimina slash al final
  return path;
}

// Estado global para copiar/cortar
let clipboardFiles = [];
let clipboardAction = null; // 'copy' o 'cut'

function updateClipboardButtons() {
  document.getElementById('paste-files').disabled = clipboardFiles.length === 0;
}

// Obtener archivos seleccionados
function getSelectedFiles() {
  return Array.from(document.querySelectorAll('.file-checkbox:checked')).map(chk => chk.dataset.path);
}

// Copiar archivos seleccionados
document.getElementById('copy-selected').addEventListener('click', () => {
  clipboardFiles = getSelectedFiles();
  if (clipboardFiles.length === 0) return;
  clipboardAction = 'copy';
  updateClipboardButtons();
  toastr.info(`Copiado ${clipboardFiles.length} archivo(s) para pegar.`);
});

// Cortar archivos seleccionados
document.getElementById('cut-selected').addEventListener('click', () => {
  clipboardFiles = getSelectedFiles();
  if (clipboardFiles.length === 0) return;
  clipboardAction = 'cut';
  updateClipboardButtons();
  toastr.info(`Cortado ${clipboardFiles.length} archivo(s) para mover.`);
});

// Pegar archivos en carpeta actual
document.getElementById('paste-files').addEventListener('click', () => {
  if (clipboardFiles.length === 0 || !clipboardAction) return;

  fetch('paste_files.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      source: clipboardFiles,
      target: currentFolder,
      action: clipboardAction
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (data.warning) {
      toastr.warning(data.warning);
    } else {
      toastr.success(data.message || 'Operación completada');
    }
      clipboardFiles = [];
      clipboardAction = null;
      updateClipboardButtons();
      loadFolder(currentFolder);
    } else {
      toastr.error(data.error || 'Error al pegar archivos');
    }
  })
  .catch(() => toastr.error('Error en la petición'));
});


function loadFolder(folder) {
  folder = decodeURIComponent(folder);
  folder = normalizePath(folder);

  fetch('list_files.php?folder=' + encodeURIComponent(folder))
    .then(res => res.json())
    .then(data => {
      currentFolder = data.current_folder;

      // Breadcrumbs
      const breadcrumbContainer = document.getElementById('breadcrumb-container');
      breadcrumbContainer.innerHTML = data.breadcrumbs.map((crumb, idx) => {
        const visualPath = crumb.path.replace(/\//g, '\\');  // <-- Aquí reemplazas para mostrar barras invertidas
        if (idx === data.breadcrumbs.length - 1) {  
          return `<li class="breadcrumb-item active" aria-current="page">${crumb.name}</li>`;
        } else {
          return `<li class="breadcrumb-item"><a href="#" data-folder="${encodeURIComponent(crumb.path)}">${crumb.name}</a></li>`;
        }
      }).join('');

      // Carpeta
      const folderList = document.getElementById('folder-list');
      folderList.innerHTML = data.folders.length
        ? `<table class="table table-hover align-middle shadow-sm border rounded">
            <thead class="table-light">
              <tr>
                <th>📁 Carpeta</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>` +
            data.folders.map(folder => `
              <tr class="folder-row" data-folder-id="${folder.id}">
                <td>
                  <span class="folder-name fw-semibold text-primary" style="cursor:pointer;" onclick="loadFolder('${encodeURIComponent(folder.path)}')">
                    ${folder.name}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="rename_folder.php?folder_id=${folder.id}" class="btn btn-sm btn-secondary rounded-2">Renombrar</a>
                    <button onclick="deleteFolder(${folder.id})" class="btn btn-sm btn-danger ms-2 rounded-2">Eliminar</button>
                  </div>
                </td>
              </tr>
            `).join('') +
            `</tbody></table>`
        : '<p class="text-muted">No hay carpetas.</p>';

      // Archivos (sin cambios)
      const fileList = document.getElementById('file-list');
      fileList.innerHTML = data.files.length
        ? `<table class="table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th><input type="checkbox" id="select-all-files" title="Seleccionar todos"></th>
                <th>Nombre</th><th>Tamaño</th><th>Fecha</th><th>Acciones</th>
              </tr>
            </thead><tbody>` +
            data.files.map(file => `
              <tr class="file-row file-preview-row" data-path="${file.path}" data-type="${file.type}">
                <td><input type="checkbox" class="file-checkbox" data-path="${file.path}"></td>
                <td><span class="file-preview-link" data-path="${file.path}" data-type="${file.type}" style="color: inherit; text-decoration: none; cursor: pointer;">${file.filename}</span></td>
                <td>${(file.filesize / 1024).toFixed(2)} KB</td>
                <td>${file.uploaded_at}</td>
                <td class="action-buttons">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      Opciones
                    </button>
                    <ul class="dropdown-menu">
                      <li><a class="dropdown-item" href="download_file.php?path=${encodeURIComponent(file.path)}">⬇️ Descargar</a></li>
                      <li><a class="dropdown-item" href="#" onclick="renameFile('${file.path}', '${file.filename}')">✏️ Renombrar</a></li>
                      <li><a class="dropdown-item" href="#" onclick="deleteFile('${file.path}')">🗑️ Eliminar</a></li>
                      <li><a class="dropdown-item" href="#" onclick="showProperties('${file.path}')">ℹ️ Propiedades</a></li>
                    </ul>
                  </div>
                </td>
              </tr>
            `).join('') +
            `</tbody></table>`
        : '<p>No hay archivos.</p>';

      // Actualizar input hidden Dropzone
      const targetFolderInput = document.querySelector('#my-dropzone input[name="targetFolder"]');
      if (targetFolderInput) targetFolderInput.value = currentFolder;

      // Listener para clicks en breadcrumbs (ya codificado correctamente)
      breadcrumbContainer.querySelectorAll('a').forEach(el => {
        el.addEventListener('click', e => {
          e.preventDefault();
          const rawPath = decodeURIComponent(el.dataset.folder);
          loadFolder(rawPath);
        });
      });

    })
    .catch(() => toastr.error('Error al cargar contenido'));
}


function deleteFolder(folderId) {
  if (!confirm('¿Seguro que quieres eliminar esta carpeta y todo su contenido?')) return;

  fetch('delete_folder.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ folder_id: folderId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toastr.success('Carpeta eliminada');
      loadFolder(currentFolder); // refrescar vista
    } else {
      toastr.error(data.error || 'Error al eliminar carpeta');
    }
  })
  .catch(() => toastr.error('Error al eliminar carpeta'));
}


document.addEventListener('DOMContentLoaded', () => loadFolder(currentFolder));

document.getElementById('create-folder-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const name = this.folder_name.value.trim();
  if (!name) return toastr.warning('Nombre de carpeta requerido');
  fetch('create_folder.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ folder_name: name, current_folder: currentFolder })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toastr.success('Carpeta creada');
      this.folder_name.value = '';
      loadFolder(currentFolder);
    } else {
      toastr.error(data.error || 'Error al crear carpeta');
    }
  })
  .catch(() => toastr.error('Error al crear carpeta'));
});

// Configuración Dropzone
Dropzone.options.myDropzone = {
  paramName: "file",
  maxFilesize: 10,
  acceptedFiles: "image/*,application/pdf,text/plain,video/mp4,audio/mpeg",
  uploadMultiple: true,
  parallelUploads: 5,
  dictDefaultMessage: "Arrastra tus archivos aquí o haz clic para seleccionar",
  init: function () {
    this.on("sending", function (file, xhr, formData) {
      formData.append("targetFolder", currentFolder);
    });
    this.on("successmultiple", function (files, response) {
      if (response.success) {
        toastr.success('Archivos subidos');
        loadFolder(currentFolder);
      } else if (response.error) {
        toastr.error(response.error);
      }
    });
    this.on("error", (file, response) =>
      toastr.error(typeof response === 'string' ? response : response.error || 'Error desconocido')
    );
  }
};

// Acciones

function deleteFile(path) {
  // Ya no pide confirmación, elimina directamente
  fetch('delete_file.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ path })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toastr.success('Archivo eliminado');
      loadFolder(currentFolder); // recarga solo la lista sin recargar toda la página
    } else {
      toastr.error(data.error || 'No se pudo eliminar');
    }
  })
  .catch(() => toastr.error('Error al eliminar archivo'));
}

// Guardamos instancia del modal para abrir/cerrar
const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
const renameForm = document.getElementById('renameForm');
const newNameInput = document.getElementById('newNameInput');
const renamePathInput = document.getElementById('renamePath');

function renameFile(path, oldName) {
  renamePathInput.value = path;
  newNameInput.value = oldName;
  renameModal.show();
}

renameForm.addEventListener('submit', e => {
  e.preventDefault();
  const path = renamePathInput.value;
  const newName = newNameInput.value.trim();
  if (!newName) {
    toastr.warning('El nombre no puede estar vacío');
    return;
  }
  fetch('rename_file.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ oldPath: path, newNameInput: newName })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toastr.success('Archivo renombrado');
      renameModal.hide();
      loadFolder(currentFolder, true);
    } else {
      toastr.error(data.error || 'Error al renombrar');
    }
  })
  .catch(() => toastr.error('Error al renombrar archivo'));
});

function previewFile(path, type) {
  const win = window.open('', '_blank');
  if (!win) return toastr.warning('Desbloquea los pop-ups');
  let content = '';
  if (type.startsWith('image/')) {
    content = `<img src="${path}" style="max-width:100%">`;
    win.document.write(content);
  } else if (type === 'application/pdf') {
    content = `<embed src="${path}" type="application/pdf" width="100%" height="600px">`;
    win.document.write(content);
  } else if (type.startsWith('text/')) {
    fetch(path).then(r => r.text()).then(txt => {
      win.document.write(`<pre style="white-space:pre-wrap;">${txt}</pre>`);
    });
  } else {
    toastr.info('Vista previa no disponible para este tipo de archivo');
    win.close();
  }
}

function showProperties(path) {
  fetch('file_properties.php?path=' + encodeURIComponent(path))
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(`Nombre: ${data.name}\nTamaño: ${data.size} KB\nTipo: ${data.type}\nModificado: ${data.modified}`);
      } else {
        toastr.error('No se pudieron obtener propiedades');
      }
    });
}

function downloadMultipleFiles(paths) {
  if (!paths.length) return;
  
  paths.forEach(path => {
    const url = `download_file.php?path=${encodeURIComponent(path)}`;
    const a = document.createElement('a');
    a.href = url;
    a.download = ''; // Esto indica que es descarga
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  });
}


// Cuando se cambian los checkboxes, habilitar o no los botones
function updateButtonsState() {
  const checkedCount = document.querySelectorAll('.file-checkbox:checked').length;
  document.getElementById('delete-selected').disabled = checkedCount === 0;
  document.getElementById('download-selected').disabled = checkedCount === 0;
  document.getElementById('copy-selected').disabled = checkedCount === 0;
  document.getElementById('cut-selected').disabled = checkedCount === 0;
}

// Seleccionar todos
document.addEventListener('change', e => {
  if (e.target.id === 'select-all-files') {
    const checked = e.target.checked;
    document.querySelectorAll('.file-checkbox').forEach(chk => chk.checked = checked);
    updateButtonsState();
  } else if (e.target.classList.contains('file-checkbox')) {
    updateButtonsState();
  }
});

// Eliminar archivos seleccionados
document.getElementById('delete-selected').addEventListener('click', () => {
  const selectedPaths = Array.from(document.querySelectorAll('.file-checkbox:checked'))
    .map(chk => chk.dataset.path);

  if (selectedPaths.length === 0) return;

  // Opcional: Confirmar la eliminación
  if (!confirm(`¿Eliminar ${selectedPaths.length} archivo(s)? Esta acción no se puede deshacer.`)) return;

  fetch('delete_files.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ paths: selectedPaths })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toastr.success(`Se eliminaron ${selectedPaths.length} archivo(s)`);
      loadFolder(currentFolder);
    } else {
      toastr.error(data.error || 'Error al eliminar archivos');
    }
  })
  .catch(() => toastr.error('Error al eliminar archivos'));
});

// Descargar archivos seleccionados
document.getElementById('download-selected').addEventListener('click', () => {
  const selectedPaths = Array.from(document.querySelectorAll('.file-checkbox:checked'))
    .map(chk => chk.dataset.path);

  if (selectedPaths.length === 0) return;

  downloadMultipleFiles(selectedPaths);
});

let previewModal;

document.addEventListener('DOMContentLoaded', () => {
  // Ahora que el DOM está listo, inicializamos el modal bootstrap
  previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
});

document.addEventListener('dblclick', e => {
  if (e.target.classList.contains('file-preview-link')) {
    e.preventDefault();

    const path = e.target.dataset.path;
    const type = e.target.dataset.type;
    const previewContent = document.getElementById('previewContent');

    let contentHtml = '';

    if (type.startsWith('image/')) {
      contentHtml = `<img src="${path}" alt="Imagen" style="max-width: 100%;">`;
      previewContent.innerHTML = contentHtml;
      previewModal.show();
    } else if (type === 'application/pdf') {
      contentHtml = `<embed src="${path}" type="application/pdf" width="100%" height="600px">`;
      previewContent.innerHTML = contentHtml;
      previewModal.show();
    } else if (type.startsWith('audio/')) {
      contentHtml = `<audio controls style="width: 100%;">
                       <source src="${path}" type="${type}">
                       Tu navegador no soporta el elemento de audio.
                     </audio>`;
      previewContent.innerHTML = contentHtml;
      previewModal.show();
    } else if (type.startsWith('text/')) {
      previewContent.innerHTML = '<pre>Cargando texto...</pre>';
      previewModal.show();
      fetch(path).then(r => r.text()).then(txt => {
        previewContent.innerHTML = `<pre style="white-space: pre-wrap;">${txt}</pre>`;
      }).catch(() => {
        previewContent.innerHTML = '<p>Error cargando texto.</p>';
      });
    } else if (
      type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || // .docx
      type === 'application/msword' || // .doc
      type === 'application/vnd.ms-excel' || // .xls
      type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || // .xlsx
      type === 'application/vnd.ms-powerpoint' || // .ppt
      type === 'application/vnd.openxmlformats-officedocument.presentationml.presentation' // .pptx
    ) {
      // Usar Office Online Viewer (requiere URL pública)
      const encodedUrl = encodeURIComponent(window.location.origin + '/' + path);
      contentHtml = `
        <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodedUrl}" width="100%" height="600px" frameborder="0"></iframe>
      `;
      previewContent.innerHTML = contentHtml;
      previewModal.show();
    } else {
      contentHtml = `
        <p>Vista previa no disponible para este tipo de archivo.</p>
        <a href="${path}" download>Descargar archivo</a>
      `;
      previewContent.innerHTML = contentHtml;
      previewModal.show();
    }
  }
});

</script>

</body>
</html>
