const editarModal = document.getElementById('editarModal');

function abrirEditarModal(btn) {
    document.getElementById('edit_cedula').value = btn.dataset.cedula;
    document.getElementById('edit_cedula_display').value = btn.dataset.cedula;
    document.getElementById('edit_nombre').value = btn.dataset.nombre;
    document.getElementById('edit_rol').value = btn.dataset.rol;
    document.getElementById('edit_estado').value = btn.dataset.estado;
    document.getElementById('edit_contrasena_nueva').value = '';
    editarModal.classList.add('show');
}

document.addEventListener('click', function (event) {
    const editarBtn = event.target.closest('.btn-editar-usuario');
    if (editarBtn) {
        abrirEditarModal(editarBtn);
        return;
    }

    const enlace = event.target.closest('#usuarios-listado .usuarios-role-tabs a');
    if (enlace) {
        event.preventDefault();
        cargarListadoUsuarios(enlace.href);
    }
});

document.addEventListener('submit', function (event) {
    const formulario = event.target.closest('#usuarios-listado .usuarios-table-pagination');
    if (!formulario) {
        return;
    }

    event.preventDefault();
    const datos = new FormData(formulario);
    if (event.submitter && event.submitter.name) {
        datos.set(event.submitter.name, event.submitter.value);
    }
    const url = new URL(formulario.action || window.location.href, window.location.href);
    url.search = new URLSearchParams(datos).toString();
    cargarListadoUsuarios(url.href);
});

async function cargarListadoUsuarios(url, actualizarHistorial = true) {
    const listadoActual = document.getElementById('usuarios-listado');
    if (!listadoActual) {
        return;
    }

    listadoActual.setAttribute('aria-busy', 'true');
    listadoActual.classList.add('is-loading');

    try {
        const respuesta = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!respuesta.ok) {
            throw new Error('No se pudo cargar la lista de usuarios.');
        }

        const html = await respuesta.text();
        const documento = new DOMParser().parseFromString(html, 'text/html');
        const nuevoListado = documento.getElementById('usuarios-listado');
        if (!nuevoListado) {
            throw new Error('La respuesta no contiene la lista de usuarios.');
        }

        listadoActual.replaceWith(nuevoListado);
        if (actualizarHistorial) {
            window.history.pushState({}, '', url);
        }
    } catch (error) {
        window.location.href = url;
    }
}

window.addEventListener('popstate', function () {
    cargarListadoUsuarios(window.location.href, false);
});

document.getElementById('cerrarEditarModal').addEventListener('click', function () {
    editarModal.classList.remove('show');
});

editarModal.addEventListener('click', function (event) {
    if (event.target === editarModal) {
        editarModal.classList.remove('show');
    }
});
