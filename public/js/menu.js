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

function initSyncOverlay() {
    const progressFill = document.getElementById('progressFill');
    const syncStatus = document.getElementById('syncStatus');
    const syncBlocker = document.getElementById('syncBlocker');
    const resultsCard = document.getElementById('resultsCard');
    const resultsSummary = document.getElementById('resultsSummary');
    const resultsBody = document.getElementById('resultsBody');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const fechaHoy = document.querySelector('meta[name="sync-date"]')?.content || '';

    if (!progressFill || !syncStatus || !syncBlocker || !resultsCard || !resultsSummary || !resultsBody || !noResultsMessage) {
        return;
    }

    syncBlocker.style.display = 'block';
    resultsCard.style.display = 'none';
    noResultsMessage.style.display = 'none';

    let fetchCompleted = false;
    let apiResult = null;
    let progress = 0;
    let lastTimestamp = null;

    function hideOverlay() {
        syncBlocker.style.display = 'none';
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.display = 'none';
        }

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
                            <td>${factura.total ?? '-'}</td>
                        </tr>`)
                    .join('');
                noResultsMessage.style.display = 'none';
            } else {
                resultsSummary.textContent = `Facturas sincronizadas para la fecha de hoy (${fechaHoy}): 0`;
                resultsBody.innerHTML = '';
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
        .then(response => response.json())
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


