/**
 * dashboard.js — Admin Panel de Control statistics and chart logic.
 * Requires: Chart.js (loaded via CDN in dashboard.php)
 * Config:   window.DASHBOARD_API must be set before this script loads.
 */

const StatManager = {
    currentPeriod: 'month',
    charts: {},

    init() {
        this.bindEvents();
        this.loadAll();
    },

    bindEvents() {
        const btns = document.querySelectorAll('.dash-btn-toggle');

        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Resetear todos a outline
                btns.forEach(b => {
                    b.classList.remove('btn-success');
                    b.classList.add('btn-outline-success');
                });
                // Marcar el activo como filled
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success');

                const period = btn.dataset.period;
                const rangeRow = document.getElementById('custom-range-row');

                if (period === 'custom') {
                    rangeRow.classList.remove('d-none');
                    // No cargamos hasta que pulse Aplicar
                } else {
                    rangeRow.classList.add('d-none');
                    this.customFrom = null;
                    this.customTo   = null;
                    this.currentPeriod = period;
                    this.loadGrowthCharts();
                }
            });
        });

        // Botón Aplicar rango
        document.getElementById('btn-apply-range').addEventListener('click', () => {
            const from = document.getElementById('range-from').value;
            const to   = document.getElementById('range-to').value;
            if (!from || !to) { alert('Selecciona ambas fechas'); return; }
            if (from > to)    { alert('La fecha inicial no puede ser posterior a la final'); return; }
            this.currentPeriod = 'custom';
            this.customFrom    = from;
            this.customTo      = to;
            this.loadGrowthCharts();
        });
    },

    async loadAll() {
        await Promise.all([
            this.loadSummary(),
            this.loadGrowthCharts(),
            this.loadDistCharts(),
            this.loadRecentTrips()
        ]);
    },

    async loadSummary() {
        try {
            const res  = await fetch(`${window.DASHBOARD_API}?action=getSummary`);
            const data = await res.json();
            if (data.success && data.data) {
                const s = data.data;
                document.getElementById('kpi-users').innerText    = s.total_users       ?? '--';
                document.getElementById('kpi-coasters').innerText = s.total_coasters    ?? '--';
                document.getElementById('kpi-parks').innerText    = s.total_parks       ?? '--';
                document.getElementById('kpi-reviews').innerText  = s.total_reviews     ?? '--';
                document.getElementById('kpi-photos').innerText   = s.total_photos      ?? '--';
                const kpiForum = document.getElementById('kpi-forum-posts');
                if (kpiForum) kpiForum.innerText = s.total_forum_posts ?? '--';
                const kpiTrips = document.getElementById('kpi-trips');
                if (kpiTrips) kpiTrips.innerText = s.total_trips ?? '--';
            }
        } catch (e) { console.error('Error KPI:', e); }
    },

    async loadGrowthCharts() {
        const usersData   = await this.fetchGrowthData('users',       this.currentPeriod);
        this.renderLineChart('chart-growth-users', usersData, 'Nuevos Usuarios', '#28a745');

        const reviewsData = await this.fetchGrowthData('reviews',     this.currentPeriod);
        this.renderBarChart('chart-growth-reviews', reviewsData, 'Reseñas', '#17a2b8');

        const forumData   = await this.fetchGrowthData('forum_posts', this.currentPeriod);
        if (document.getElementById('chart-growth-forum')) {
            this.renderBarChart('chart-growth-forum', forumData, 'Mensajes en Foros', '#ffc107');
        }

        const tripsData = await this.fetchGrowthData('trips', this.currentPeriod);
        if (document.getElementById('chart-growth-trips')) {
            this.renderLineChart('chart-growth-trips', tripsData, 'Viajes Reservados', '#20c997');
        }
    },

    async loadDistCharts() {
        const statusData  = await this.fetchDistData('status');
        this.renderPieChart('chart-dist-status', statusData);

        const countryData = await this.fetchDistData('country');
        this.renderHorizontalBarChart('chart-dist-country', countryData);
    },

    async loadRecentTrips() {
        const tbody = document.getElementById('table-recent-trips');
        if (!tbody) return;

        try {
            const res = await fetch(`${window.DASHBOARD_API}?action=getRecentTrips`);
            const data = await res.json();
            
            if (data.success && data.data && data.data.length > 0) {
                tbody.innerHTML = data.data.map(trip => {
                    const start = new Date(trip.start_date).toLocaleDateString('es-ES');
                    const end = new Date(trip.end_date).toLocaleDateString('es-ES');
                    const created = new Date(trip.created_at).toLocaleDateString('es-ES');
                    return `
                        <tr style="border-color:rgba(255,255,255,0.05);">
                            <td class="py-3 px-4 text-white align-middle">#${trip.id}</td>
                            <td class="py-3 px-4 text-white align-middle fw-semibold">${trip.title}</td>
                            <td class="py-3 px-4 text-white align-middle"><i class="fa-solid fa-user text-muted me-2"></i>${trip.username || 'Desconocido'}</td>
                            <td class="py-3 px-4 text-white align-middle"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">${trip.parks_visited || '--'}</span></td>
                            <td class="py-3 px-4 text-white align-middle">${start} &rarr; ${end}</td>
                            <td class="py-3 px-4 text-muted align-middle">${created}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No hay viajes reservados recientemente.</td></tr>`;
            }
        } catch (e) {
            console.error('Error cargando últimos viajes:', e);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error al cargar datos.</td></tr>`;
        }
    },

    async fetchGrowthData(type, period) {
        try {
            let url = `${window.DASHBOARD_API}?action=getGrowth&type=${type}&period=${period}`;
            if (period === 'custom' && this.customFrom && this.customTo) {
                url += `&from=${this.customFrom}&to=${this.customTo}`;
            }
            const res  = await fetch(url);
            const data = await res.json();
            return data.success ? data.data : [];
        } catch (e) { return []; }
    },

    async fetchDistData(type) {
        try {
            const res  = await fetch(`${window.DASHBOARD_API}?action=getDistribution&type=${type}`);
            const data = await res.json();
            return data.success ? data.data : [];
        } catch (e) { return []; }
    },

    renderLineChart(id, data, label, color) {
        if (this.charts[id]) this.charts[id].destroy();
        const ctx = document.getElementById(id).getContext('2d');
        this.charts[id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels:   data.map(i => i.label),
                datasets: [{
                    label,
                    data:            data.map(i => i.count),
                    borderColor:     color,
                    backgroundColor: color + '22',
                    fill:    true,
                    tension: 0.4
                }]
            },
            options: this.getChartOptions()
        });
    },

    renderBarChart(id, data, label, color) {
        if (this.charts[id]) this.charts[id].destroy();
        this.charts[id] = new Chart(document.getElementById(id), {
            type: 'bar',
            data: {
                labels:   data.map(i => i.label),
                datasets: [{ label, data: data.map(i => i.count), backgroundColor: color }]
            },
            options: this.getChartOptions()
        });
    },

    renderPieChart(id, data) {
        if (this.charts[id]) this.charts[id].destroy();
        this.charts[id] = new Chart(document.getElementById(id), {
            type: 'doughnut',
            data: {
                labels:   data.map(i => i.label),
                datasets: [{
                    data:            data.map(i => i.count),
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#6f42c1', '#fd7e14']
                }]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: '#fff' } } }
            }
        });
    },

    renderHorizontalBarChart(id, data) {
        if (this.charts[id]) this.charts[id].destroy();
        this.charts[id] = new Chart(document.getElementById(id), {
            type: 'bar',
            data: {
                labels:   data.map(i => i.label),
                datasets: [{ label: 'Usuarios', data: data.map(i => i.count), backgroundColor: '#007bff' }]
            },
            options: { indexAxis: 'y', ...this.getChartOptions() }
        });
    },

    getChartOptions() {
        return {
            responsive:          true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,0.5)' }, grid: { display: false } },
                y: { ticks: { color: 'rgba(255,255,255,0.5)' }, grid: { color: 'rgba(255,255,255,0.1)' } }
            }
        };
    }
};

document.addEventListener('DOMContentLoaded', () => StatManager.init());
