document.addEventListener("DOMContentLoaded", function () {
    const userButton = document.getElementById("userButton");
    const dropdownMenu = document.getElementById("dropdownMenu");
    const mobileMenuButton = document.getElementById("mobileMenuButton");
    const sidebar = document.getElementById("sidebar");

    if (mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener("click", function (e) {
            e.stopPropagation();
            const isOpen = sidebar.classList.toggle("mobile-open");
            mobileMenuButton.setAttribute("aria-expanded", String(isOpen));
        });

        document.addEventListener("click", function (e) {
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && e.target !== mobileMenuButton) {
                sidebar.classList.remove("mobile-open");
                mobileMenuButton.setAttribute("aria-expanded", "false");
            }
        });
    }

    if (!userButton || !dropdownMenu) {
        return;
    }

    userButton.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle("show");
    });

    document.addEventListener("click", function () {
        dropdownMenu.classList.remove("show");
    });
});

const PAGINATION_PAGE_SIZE = 10;

function formatCurrency(value) {
    const amount = Number.parseFloat(value);
    const formatted = Number.isFinite(amount)
        ? amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '0.00';
    return `<span class="currency-symbol">$</span><span class="currency-value">${formatted}</span>`;
}

function refreshTablePagination(tableId) {
    const table = document.getElementById(tableId);
    if (!table || table.dataset.pagination !== 'true') return;

    const body = table.tBodies[0];
    const sedeActiva = table.dataset.sedeActiva || '';
    const rows = body ? Array.from(body.rows).filter(row => {
        if (row.classList.contains('pagination-empty') || row.classList.contains('sede-empty-state')) {
            return false;
        }

        return sedeActiva === '' || row.dataset.sede === sedeActiva;
    }) : [];
    const pagination = document.getElementById(`${tableId}Pagination`);
    if (!body || !pagination) return;

    const pageCount = Math.max(1, Math.ceil(rows.length / PAGINATION_PAGE_SIZE));
    let currentPage = Number.parseInt(table.dataset.page || '1', 10);
    currentPage = Math.min(Math.max(currentPage, 1), pageCount);
    table.dataset.page = String(currentPage);

    Array.from(body.rows).forEach(row => {
        if (row.dataset.sede && sedeActiva !== '' && row.dataset.sede !== sedeActiva) {
            row.style.display = 'none';
        }
    });

    rows.forEach((row, index) => {
        const firstRow = (currentPage - 1) * PAGINATION_PAGE_SIZE;
        row.style.display = index >= firstRow && index < firstRow + PAGINATION_PAGE_SIZE ? '' : 'none';
    });

    pagination.innerHTML = '';
    if (rows.length <= PAGINATION_PAGE_SIZE) {
        pagination.style.display = 'none';
        return;
    }

    pagination.style.display = 'flex';
    const previous = document.createElement('button');
    previous.type = 'button';
    previous.className = 'pagination-button';
    previous.textContent = 'Anterior';
    previous.disabled = currentPage === 1;
    previous.addEventListener('click', () => {
        table.dataset.page = String(currentPage - 1);
        refreshTablePagination(tableId);
    });

    const status = document.createElement('span');
    status.className = 'pagination-status';
    status.textContent = `Página ${currentPage} de ${pageCount}`;

    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'pagination-button';
    next.textContent = 'Siguiente';
    next.disabled = currentPage === pageCount;
    next.addEventListener('click', () => {
        table.dataset.page = String(currentPage + 1);
        refreshTablePagination(tableId);
    });

    pagination.append(previous, status, next);
}

function initTablePagination() {
    document.querySelectorAll('table[data-pagination="true"]').forEach(table => {
        refreshTablePagination(table.id);
    });
}

document.addEventListener('DOMContentLoaded', initTablePagination);

function initSyncOverlay() {
    const progressFill = document.getElementById('progressFill');
    const syncStatus = document.getElementById('syncStatus');
    const syncBlocker = document.getElementById('syncBlocker');
    const syncCard = document.getElementById('syncCard');
    const resultsCard = document.getElementById('resultsCard');
    const resultsSummary = document.getElementById('resultsSummary');
    const resultsBody = document.getElementById('resultsBody');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const fechaHoy = document.querySelector('meta[name="sync-date"]')?.content || '';

    if (!progressFill || !syncStatus || !syncBlocker || !syncCard || !resultsCard || !resultsSummary || !resultsBody || !noResultsMessage) {
        return;
    }

    syncBlocker.style.display = 'block';
    syncCard.style.display = 'flex';
    resultsCard.style.display = 'none';
    noResultsMessage.style.display = 'none';

    let fetchCompleted = false;
    let apiResult = null;
    let progress = 0;
    let lastTimestamp = null;
    const preventExit = (event) => {
        event.preventDefault();
        event.returnValue = '';
    };

    window.addEventListener('beforeunload', preventExit);

    function hideOverlay() {
        syncBlocker.style.display = 'none';
        syncCard.style.display = 'none';
        window.removeEventListener('beforeunload', preventExit);

        if (apiResult !== null) {
            resultsCard.style.display = 'block';

            if (apiResult.length > 0) {
                resultsSummary.textContent = `Facturas sincronizadas para la fecha de hoy (${fechaHoy}): ${apiResult.length}`;
                resultsBody.innerHTML = apiResult
                    .map((factura, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${factura.documento ?? '-'}</td>
                            <td>${factura.cliente?.nombre_comercial ?? '-'}</td>
                            <td class="currency-cell">${formatCurrency(factura.total)}</td>
                        </tr>`)
                    .join('');
                refreshTablePagination('resultsTable');
                noResultsMessage.style.display = 'none';
            } else {
                resultsSummary.textContent = `Facturas sincronizadas para la fecha de hoy (${fechaHoy}): 0`;
                resultsBody.innerHTML = '';
                refreshTablePagination('resultsTable');
                noResultsMessage.style.display = 'block';
            }
        }
    }

    function updateProgress(timestamp) {
        if (!lastTimestamp) {
            lastTimestamp = timestamp;
        }

        const delta = timestamp - lastTimestamp;
        lastTimestamp = timestamp;

        if (!fetchCompleted) {
            progress = Math.min(99, progress + delta * 0.04);
        } else {
            progress = Math.min(100, progress + delta * 0.5);
        }

        progressFill.style.width = `${progress}%`;

        if (progress < 100) {
            window.requestAnimationFrame(updateProgress);
        } else {
            syncStatus.textContent = 'Sincronización completada.';
            hideOverlay();
        }
    }

    fetch(`../public/index.php?controller=factura&action=sincronizar`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            apiResult = Array.isArray(data.facturas) ? data.facturas : [];
            if (data.inserted_count !== undefined) {
                syncStatus.textContent = `Sincronización completada. ${data.inserted_count} facturas guardadas.`;
            }
            fetchCompleted = true;
        })
        .catch(error => {
            syncStatus.textContent = 'Error al consultar facturas: ' + error.message;
            apiResult = [];
            fetchCompleted = true;
        });

    window.requestAnimationFrame(updateProgress);
}

document.addEventListener('DOMContentLoaded', initSyncOverlay);

function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('arrowIcon');
    const page = document.querySelector('.page');

    if (!sidebar) {
        return;
    }

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
        return;
    }

    sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed');

    // compute current sidebar width and apply to .page so content shifts smoothly
    // const sWidth = getComputedStyle(sidebar).width; // e.g. '200px' or '64px'
    // // Debugging line to check the computed width
    // if (page) {
    //     page.style.marginLeft = sWidth;
    //     // set width to viewport minus sidebar to avoid overflow
    //     page.style.width = `calc(100vw - ${sWidth})`;
    // }

    setTimeout(() => {

        const sWidth = getComputedStyle(sidebar).width;

        if (page && window.innerWidth > 768) {
            page.style.marginLeft = sWidth;
            page.style.width = `calc(100vw - ${sWidth})`;
        }

    }, 310);

    icon.className = sidebar.classList.contains('collapsed') ? 'fas fa-angle-right' : 'fas fa-angle-left';
}

function adjustPageLayout() {
    const sidebar = document.getElementById('sidebar');
    const page = document.querySelector('.page');
    if (!sidebar || !page) {
        return;
    }

    if (window.innerWidth <= 768) {
        page.style.marginLeft = '';
        page.style.width = '';
        return;
    }

    const sWidth = getComputedStyle(sidebar).width;
    page.style.marginLeft = sWidth;
    page.style.width = `calc(100vw - ${sWidth})`;
}

// Keep the content aligned when the device changes orientation or the window is resized.
document.addEventListener('DOMContentLoaded', adjustPageLayout);
window.addEventListener('resize', adjustPageLayout);


