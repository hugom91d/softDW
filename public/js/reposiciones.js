document.addEventListener('DOMContentLoaded', function() {
    const modalOverlay = document.getElementById('modalOverlay');
    const detalleBody = document.getElementById('detalleBody');
    const closeDetail = document.getElementById('closeDetail');
    const modalFacturaId = document.getElementById('modalFacturaId');
    const systemNotification = document.getElementById('systemNotification');
    const systemNotificationMessage = document.getElementById('systemNotificationMessage');
    const systemNotificationAccept = document.getElementById('systemNotificationAccept');
    const syncNewInvoicesButton = document.getElementById('syncNewInvoicesButton');
    let currentNoRepuestoId = null;
    let currentNoRepuestoButton = null;

    function filtrarPorSede(sede) {
        document.querySelectorAll('#reposicionesActualesTable, #reposicionesAnterioresTable').forEach(table => {
            table.dataset.sedeActiva = sede;
            table.dataset.page = '1';
            const rows = Array.from(table.querySelectorAll('tbody tr[data-sede]'));
            rows.forEach(row => {
                row.classList.toggle('sede-hidden', row.dataset.sede !== sede);
            });

            const hasVisibleRows = rows.some(row => row.dataset.sede === sede);
            let emptyRow = table.querySelector('.sede-empty-state');
            if (!hasVisibleRows && rows.length > 0) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'sede-empty-state';
                    emptyRow.innerHTML = '<td colspan="5" class="empty-state">No hay facturas abiertas para esta sede.</td>';
                    table.querySelector('tbody').appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }

            refreshTablePagination(table.id);
        });

        document.querySelectorAll('.sede-tab').forEach(tab => {
            const activa = tab.dataset.sede === sede;
            tab.classList.toggle('active', activa);
            tab.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
    }

    document.querySelectorAll('.sede-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            filtrarPorSede(this.dataset.sede);
        });
    });

    filtrarPorSede('Pto. Ayora');

    if (syncNewInvoicesButton) {
        syncNewInvoicesButton.addEventListener('click', async function() {
            const button = this;
            const originalIcon = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const response = await fetch('../public/index.php?controller=factura&action=sincronizar');
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                const insertedCount = Number(data.inserted_count ?? 0);

                if (insertedCount > 0) {
                    showSystemNotification(`Sincronización completada. ${insertedCount} facturas guardadas.`);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                    return;
                }

                showSystemNotification('Sincronización completada. No hay facturas nuevas por guardar.');
            } catch (error) {
                showSystemNotification('Error al sincronizar facturas: ' + error.message);
            } finally {
                button.disabled = false;
                button.innerHTML = originalIcon;
            }
        });
    }

    function showSystemNotification(message) {
        systemNotificationMessage.textContent = message;
        systemNotification.classList.add('show');
        systemNotificationAccept.focus();
    }

    function closeSystemNotification() {
        systemNotification.classList.remove('show');
    }

    function removeExistingNoRepuestoForm() {
        const existing = detalleBody.querySelector('.no-repuesto-row');
        if (existing) existing.remove();
        currentNoRepuestoId = null;
        currentNoRepuestoButton = null;
    }

    function openModal(id) {
        removeExistingNoRepuestoForm();
        modalOverlay.style.display = 'flex';
        modalFacturaId.textContent = `Factura ID: ${id}`;
    }

    function openNoRepuestoForm(idDetalle, buttonElement) {
        removeExistingNoRepuestoForm();
        currentNoRepuestoId = idDetalle;
        currentNoRepuestoButton = buttonElement;

        const row = buttonElement.closest('tr');
        if (!row) return;

        const formRow = document.createElement('tr');
        formRow.className = 'no-repuesto-row';
        const td = document.createElement('td');
        td.colSpan = 6;
        td.innerHTML = `
            <div class="no-repuesto-form">
                <label>Observación</label>
                <select id="noRepuestoInput" required>
                    <option value="">Selecciona una opción</option>
                    <option value="No hay stock en GPS">No hay stock en GPS</option>
                    <option value="Solicitar stock a Quito">Solicitar stock a Quito</option>
                </select>
                <button type="button" class="btn-ok" id="saveNoRepuestoButton">OK</button>
            </div>
        `;

        formRow.appendChild(td);
        row.parentNode.insertBefore(formRow, row.nextSibling);

        const input = document.getElementById('noRepuestoInput');
        input.focus();

        document.getElementById('saveNoRepuestoButton').addEventListener('click', function() {
            const observacion = input.value.trim();
            if (observacion.length === 0) {
                showSystemNotification('Escribe una observación antes de continuar.');
                input.focus();
                return;
            }

            const idDetalle = currentNoRepuestoId;
            const buttonEl = currentNoRepuestoButton;
            if (!idDetalle || !buttonEl) {
                removeExistingNoRepuestoForm();
                return;
            }

            fetch('reposiciones.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=no_repuesto&id_detalle=${encodeURIComponent(idDetalle)}&observacion=${encodeURIComponent(observacion)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const rowEl = buttonEl.closest('tr');
                        if (rowEl) {
                            rowEl.querySelector('td:nth-child(5)').textContent = 'No repuesto';
                            const markButton = rowEl.querySelector('.btn-mark-detail');
                            if (markButton) {
                                markButton.disabled = true;
                                markButton.classList.add('disabled');
                            }
                            buttonEl.disabled = true;
                            buttonEl.classList.add('disabled');
                        }
                        removeExistingNoRepuestoForm();
                    } else {
                        showSystemNotification('No se pudo guardar la observación.');
                    }
                })
                .catch(() => {
                    showSystemNotification('Error al guardar la observación.');
                });
        });
    }

    function closeModal() {
        removeExistingNoRepuestoForm();
        modalOverlay.style.display = 'none';
        detalleBody.innerHTML = '';
    }

    function bindCloseFacturaButtons() {
        document.querySelectorAll('.btn-cerrar').forEach(button => {
            button.addEventListener('click', function() {
                const idFactura = this.dataset.id;
                if (!idFactura || this.disabled) {
                    return;
                }

                const buttonElement = this;
                fetch('reposiciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=cerrar&id_factura=${encodeURIComponent(idFactura)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const row = buttonElement.closest('tr');
                            if (row) {
                                const estadoCell = row.querySelector('td:nth-child(4)');
                                if (estadoCell) {
                                    estadoCell.innerHTML = '<span class="status-closed">Cerrada</span>';
                                }
                                const viewButton = row.querySelector('.btn-ver');
                                [buttonElement, viewButton].forEach(el => {
                                    if (el) {
                                        el.disabled = true;
                                        el.classList.add('disabled');
                                    }
                                });
                            }
                        } else {
                            showSystemNotification(data.message || 'No se puede cerrar la factura porque hay detalles abiertos.');
                        }
                    })
                    .catch(() => {
                        showSystemNotification('Error al cerrar la factura.');
                    });
            });
        });
    }

    function bindMarkDetailButtons() {
        document.querySelectorAll('.btn-mark-detail').forEach(button => {
            button.addEventListener('click', function() {
                const idDetalle = this.dataset.detalleId;
                if (!idDetalle || this.disabled) {
                    return;
                }

                const buttonElement = this;
                fetch('reposiciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=repuesto&id_detalle=${encodeURIComponent(idDetalle)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            buttonElement.disabled = true;
                            buttonElement.classList.add('disabled');
                            const row = buttonElement.closest('tr');
                            if (row) {
                                row.querySelector('td:nth-child(5)').textContent = 'Repuesto';
                                const removeButton = row.querySelector('.btn-remove-detail');
                                if (removeButton) {
                                    removeButton.disabled = true;
                                    removeButton.classList.add('disabled');
                                }
                            }
                        } else {
                            showSystemNotification('No se pudo marcar el detalle como repuesto.');
                        }
                    })
                    .catch(() => {
                        showSystemNotification('Error al marcar el detalle como repuesto.');
                    });
            });
        });

        document.querySelectorAll('.btn-remove-detail').forEach(button => {
            button.addEventListener('click', function() {
                const idDetalle = this.dataset.detalleId;
                if (!idDetalle || this.disabled) {
                    return;
                }
                openNoRepuestoForm(idDetalle, this);
            });
        });
    }

    document.querySelectorAll('.btn-ver').forEach(button => {
        button.addEventListener('click', function() {
            const idFactura = this.dataset.id;
            if (!idFactura || this.disabled) {
                return;
            }

            fetch(`reposiciones.php?action=detalle&id_factura=${encodeURIComponent(idFactura)}`)
                .then(response => response.json())
                .then(data => {
                    const detalles = Array.isArray(data.detalles) ? data.detalles : [];
                    detalleBody.innerHTML = detalles.length > 0 ? detalles.map((detalle, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td data-label="Prenda">
                                <span class="detail-code">${detalle.codigo_interno ?? '-'}</span>
                                <span class="detail-quantity-mobile">Cantidad: ${detalle.cantidad ?? '-'}</span>
                            </td>
                            <td data-label="Cantidad">${detalle.cantidad ?? '-'}</td>
                            <td data-label="Descripción">${detalle.descripcion ?? '-'}</td>
                            <td>${detalle.estado == 0 ? 'Abierto' : (detalle.estado == 1 ? 'Repuesto' : 'No repuesto')}</td>
                            <td data-label="Acciones">
                                <div class="detalle-actions">
                                    <button type="button" class="btn-mark-detail${detalle.estado != 0 ? ' disabled' : ''}" data-detalle-id="${detalle.id_detalle}" ${detalle.estado != 0 ? 'disabled="disabled"' : ''} title="Marcar como repuesto">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button type="button" class="btn-remove-detail${detalle.estado != 0 ? ' disabled' : ''}" data-detalle-id="${detalle.id_detalle}" ${detalle.estado != 0 ? 'disabled="disabled"' : ''} title="Marcar como no repuesto">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`).join('') : '<tr><td colspan="6">No hay detalles para esta factura.</td></tr>';

                    bindMarkDetailButtons();
                    openModal(idFactura);
                })
                .catch(() => {
                    detalleBody.innerHTML = '<tr><td colspan="6">Error al cargar el detalle.</td></tr>';
                    openModal(idFactura);
                });
        });
    });

    bindCloseFacturaButtons();

    closeDetail.addEventListener('click', closeModal);
    systemNotificationAccept.addEventListener('click', closeSystemNotification);
    systemNotification.addEventListener('click', function(event) {
        if (event.target === systemNotification) {
            closeSystemNotification();
        }
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSystemNotification();
        }
    });
    modalOverlay.addEventListener('click', function(event) {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });
});
