const editarModal = document.getElementById('editarModal');

document.querySelectorAll('.btn-editar-usuario').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('edit_cedula').value = this.dataset.cedula;
        document.getElementById('edit_cedula_display').value = this.dataset.cedula;
        document.getElementById('edit_nombre').value = this.dataset.nombre;
        document.getElementById('edit_rol').value = this.dataset.rol;
        document.getElementById('edit_estado').value = this.dataset.estado;
        document.getElementById('edit_contrasena_nueva').value = '';
        editarModal.classList.add('show');
    });
});

document.getElementById('cerrarEditarModal').addEventListener('click', function () {
    editarModal.classList.remove('show');
});

editarModal.addEventListener('click', function (event) {
    if (event.target === editarModal) {
        editarModal.classList.remove('show');
    }
});
