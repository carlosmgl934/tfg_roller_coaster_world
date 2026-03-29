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
        document.querySelectorAll('.dash-btn-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.dash-btn-toggle').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.currentPeriod = btn.dataset.period;
                this.loadGrowthCharts();
            });
        });
    },

    async loadAll() {
        await Promise.all([
            this.loadSummary(),
            this.loadGrowthCharts(),
            this.loadDistCharts()
        ]);
    },

    async loadSummary() {
        try {
            const res  = await fetch(`${window.DASHBOARD_API}?action=getSummary`);
            const data = await res.json();
            if (data.success && data.data) {
                const s = data.data;
                document.getElementById('kpi-users').innerText    = s.total_users    ?? '--';
                document.getElementById('kpi-coasters').innerText = s.total_coasters ?? '--';
                document.getElementById('kpi-parks').innerText    = s.total_parks    ?? '--';
                document.getElementById('kpi-reviews').innerText  = s.total_reviews  ?? '--';
                document.getElementById('kpi-photos').innerText   = s.total_photos   ?? '--';
            }
        } catch (e) { console.error('Error KPI:', e); }
    },

    async loadGrowthCharts() {
        const usersData   = await this.fetchGrowthData('users',   this.currentPeriod);
        this.renderLineChart('chart-growth-users', usersData, 'Nuevos Usuarios', '#28a745');

        const reviewsData = await this.fetchGrowthData('reviews', this.currentPeriod);
        this.renderBarChart('chart-growth-reviews', reviewsData, 'Reseñas', '#17a2b8');
    },

    async loadDistCharts() {
        const statusData  = await this.fetchDistData('status');
        this.renderPieChart('chart-dist-status', statusData);

        const countryData = await this.fetchDistData('country');
        this.renderHorizontalBarChart('chart-dist-country', countryData);
    },

    async fetchGrowthData(type, period) {
        try {
            const res  = await fetch(`${window.DASHBOARD_API}?action=getGrowth&type=${type}&period=${period}`);
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
