// Cette zone recupere les elements principaux du DOM utilises dans toute l interface.
const addLineButton = document.getElementById('add-line');
const invoiceLines = document.getElementById('invoice-lines');
const themeToggle = document.querySelector('[data-theme-toggle]');
const aiFab = document.getElementById('ai-fab');
const aiSidepanel = document.getElementById('ai-sidepanel');
const aiSidepanelClose = document.getElementById('ai-sidepanel-close');
const aiSidepanelRun = document.getElementById('ai-sidepanel-run');
const aiSidepanelRunLocal = document.getElementById('ai-sidepanel-run-local');
const aiSidepanelSummary = document.getElementById('ai-sidepanel-summary');
const aiSidepanelAskOpen = document.getElementById('ai-sidepanel-ask-open');
const aiSidepanelReset = document.getElementById('ai-sidepanel-reset');
const aiSidepanelDownload = document.getElementById('ai-sidepanel-download');
const aiSidepanelContent = document.getElementById('ai-sidepanel-content');
const aiRunButton = document.getElementById('ai-run-analysis');
const aiRunLocalButton = document.getElementById('ai-run-local-analysis');
const aiRefreshButton = document.getElementById('ai-refresh-analysis');
const aiLoading = document.getElementById('ai-loading');
const aiLoadingTitle = document.getElementById('ai-loading-title');
const aiLoadingMessage = document.getElementById('ai-loading-message');
const aiOutput = document.getElementById('ai-output');
const aiOpenQuestionDialogButton = document.getElementById('ai-open-question-dialog');
const aiQuestionDialog = document.getElementById('ai-question-dialog');
const aiDialogClose = document.getElementById('ai-dialog-close');
const aiDialogForm = document.getElementById('ai-dialog-form');
const aiDialogQuestion = document.getElementById('ai-dialog-question');
const aiDialogBody = document.getElementById('ai-dialog-body');
const aiCharts = {};
let lastAnalysisPayload = null;
let isAiAnalyzing = false;
const dashboardAiSeed = window.dashboardAiSeed || null;

// Cette fonction applique le theme clair ou sombre sur toute l application.
const applyTheme = (theme) => {
    document.body.classList.toggle('theme-dark', theme === 'dark');

    if (themeToggle) {
        themeToggle.textContent = theme === 'dark' ? 'Mode clair' : 'Mode sombre';
    }
};

const savedTheme = window.localStorage.getItem('gestion-argent-theme') || 'light';
applyTheme(savedTheme);

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        // Ce clic inverse le theme et sauvegarde le choix dans le navigateur.
        const nextTheme = document.body.classList.contains('theme-dark') ? 'light' : 'dark';
        window.localStorage.setItem('gestion-argent-theme', nextTheme);
        applyTheme(nextTheme);
    });
}

// Cette fonction envoie une requete AJAX au backend Laravel en JSON.
const postJson = async (url, body = {}) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        let message = 'Erreur de communication avec le module IA.';
        try {
            const payload = await response.json();
            message = payload.message || message;
        } catch (error) {
            // Rien a faire ici.
        }
        throw new Error(message);
    }

    return response.json();
};

// Cette fonction affiche un resume court de l analyse dans le panneau lateral IA.
const renderSidepanelSummary = (payload) => {
    if (!aiSidepanelContent) return;

    const recommendations = Array.isArray(payload.recommendations) ? payload.recommendations.slice(0, 3) : [];
    const alerts = Array.isArray(payload.alerts) ? payload.alerts.slice(0, 3) : [];
    const originLabel = payload.analysis_origin_label ?? 'Source non connue';
    const originMessage = payload.analysis_origin === 'ia'
        ? 'Ce resultat provient de l assistant IA.'
        : 'Ce resultat provient du mode local de secours de l application.';
    const originBadgeClass = payload.analysis_origin === 'ia' ? 'status-success' : 'status-warning';

    if (payload.analysis_mode === 'local_graphs') {
        aiSidepanelContent.innerHTML = `
            <div class="note">
                <div class="note-head">
                    <strong>Source</strong>
                    <span class="status-badge ${originBadgeClass}">${originLabel}</span>
                </div>
                <div class="muted">${originMessage}</div>
            </div>
            <div class="note">
                <strong>Mode local</strong>
                <div class="muted">Cette analyse remplit uniquement les graphiques et les indicateurs visuels. Pour obtenir un resume ecrit, les alertes et les explications, lancez une analyse avec IA.</div>
            </div>
        `;
        return;
    }

    aiSidepanelContent.innerHTML = `
        <div class="note">
            <div class="note-head">
                <strong>Source</strong>
                <span class="status-badge ${originBadgeClass}">${originLabel}</span>
            </div>
            <div class="muted">${originMessage}</div>
        </div>
        <div class="note">
            <div class="note-head">
                <strong>Rapport rapide</strong>
                <span class="status-badge status-info">${payload.global_score?.score ?? 0}/100</span>
            </div>
            <div class="muted">${payload.executive_summary ?? ''}</div>
        </div>
        <div class="note">
            <strong>Ce qu il faut retenir</strong>
            <div class="muted">${payload.download_summary?.summary ?? ''}</div>
        </div>
        ${alerts.map((alert) => `
            <div class="note">
                <div class="note-head">
                    <strong>${alert.title ?? 'Alerte'}</strong>
                    <span class="status-badge ${alert.severity === 'eleve' ? 'status-danger' : (alert.severity === 'faible' ? 'status-success' : 'status-warning')}">${alert.severity ?? 'moyen'}</span>
                </div>
                <div class="muted">${alert.message ?? ''}</div>
            </div>
        `).join('')}
        ${recommendations.map((item) => `<div class="note"><div class="muted">${item}</div></div>`).join('')}
    `;
};

// Cette fonction ajoute un message simple en haut du panneau lateral IA.
const showSidepanelMessage = (message) => {
    if (!aiSidepanelContent) return;
    aiSidepanelContent.insertAdjacentHTML('afterbegin', `<div class="note"><div class="muted">${message}</div></div>`);
};

const openAiQuestionDialog = () => {
    if (!aiQuestionDialog) return;
    aiQuestionDialog.hidden = false;
    document.body.classList.add('dialog-open');
    window.setTimeout(() => aiDialogQuestion?.focus(), 30);
};

const closeAiQuestionDialog = () => {
    if (!aiQuestionDialog) return;
    aiQuestionDialog.hidden = true;
    document.body.classList.remove('dialog-open');
};

// Cette fonction active ou desactive le lien de telechargement du rapport IA.
const enableAiDownload = (enabled) => {
    if (!aiSidepanelDownload) return;
    aiSidepanelDownload.classList.toggle('is-disabled', !enabled);
    aiSidepanelDownload.setAttribute('aria-disabled', enabled ? 'false' : 'true');
};

// Cette fonction cree ou remplace un graphique Chart.js deja present sur la page.
const upsertChart = (id, config) => {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return;

    if (aiCharts[id]) aiCharts[id].destroy();
    aiCharts[id] = new Chart(canvas, config);
};

// Cette fonction choisit une couleur selon l etat renvoye par l analyse.
const colorByStatus = (status) => {
    if (status === 'critical' || status === 'suspect') return '#d14343';
    if (status === 'dormant') return '#d48b1f';
    return '#0f9d76';
};

// Ces fonctions utilitaires simplifient les mises a jour de texte dans le DOM.
const setText = (id, value) => {
    const element = document.getElementById(id);
    if (!element) return;
    element.textContent = value;
};

const safeSetText = (element, value) => {
    if (!element) return;
    element.textContent = value;
};

const renderAiNotifications = (alerts = []) => {
    const countElement = document.getElementById('ai-bell-count');
    const listElement = document.getElementById('ai-bell-list');
    const safeAlerts = Array.isArray(alerts) ? alerts : [];

    safeSetText(countElement, `${safeAlerts.length}`);

    if (!listElement) return;

    if (!safeAlerts.length) {
        listElement.innerHTML = '<p class="muted">Aucune notification IA pour le moment.</p>';
        return;
    }

    listElement.innerHTML = safeAlerts.map((alert) => `
        <div class="note">
            <div class="note-head">
                <strong>${alert.title ?? 'Alerte IA'}</strong>
                <span class="status-badge ${alert.severity === 'eleve' ? 'status-danger' : (alert.severity === 'faible' ? 'status-success' : 'status-warning')}">${alert.severity ?? 'moyen'}</span>
            </div>
            <div class="muted">${alert.message ?? ''}</div>
        </div>
    `).join('');
};

// Cette zone contient les couleurs et options reutilisees par tous les graphiques.
const chartTextColor = () => '#334155';
const chartGridColor = () => 'rgba(148, 163, 184, 0.16)';
const chartTrackColor = () => '#e8eef5';
const chartPalette = ['#2563eb', '#0f9d76', '#f59e0b', '#ef4444', '#14b8a6', '#6366f1', '#f97316', '#8b5cf6'];
const chartBorderColor = () => '#ffffff';
const chartTickFont = () => ({ size: 12, weight: '800' });
const chartLegendFont = () => ({ size: 13, weight: '800' });
const stockStatusPalette = {
    critical: '#d14343',
    watch: '#f59e0b',
    healthy: '#0f9d76',
    dormant: '#64748b',
    high: '#1d4ed8',
    normal: '#0f9d76',
};

const basicChartOptions = (extra = {}) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 12,
            displayColors: true,
            boxPadding: 4,
            titleFont: { size: 13, weight: '800' },
            bodyFont: { size: 12, weight: '700' },
        },
    },
    scales: {
        x: {
            ticks: { color: chartTextColor(), font: chartTickFont() },
            grid: { color: chartGridColor(), drawBorder: false },
            border: { display: false },
        },
        y: {
            ticks: { color: chartTextColor(), font: chartTickFont() },
            grid: { color: chartGridColor(), drawBorder: false },
            border: { display: false },
        },
    },
    ...extra,
});

// Ces fonctions normalisent les donnees avant de les injecter dans les graphiques.
const normalizeRows = (rows, fallback = []) => {
    if (Array.isArray(rows) && rows.length) {
        return rows;
    }

    return Array.isArray(fallback) ? fallback : [];
};

const makeSafeSeries = (rows, emptyLabel = 'Aucune donnee') => {
    if (rows.length) {
        return rows;
    }

    return [{ label: emptyLabel, value: 0, status: 'neutral' }];
};

const getScoreTone = (value) => {
    if (value < 50) return '#d14343';
    if (value < 70) return '#d48b1f';
    return '#1d4ed8';
};

const calculateGrowthRate = (rows) => {
    if (!Array.isArray(rows) || rows.length < 2) {
        return dashboardAiSeed?.monthlyGrowthRate ?? 0;
    }

    const current = Number(rows[rows.length - 1]?.value ?? 0);
    const previous = Number(rows[rows.length - 2]?.value ?? 0);

    if (previous > 0) {
        return ((current - previous) / previous) * 100;
    }

    return current > 0 ? 100 : 0;
};

const formatPercent = (value) => `${Number(value || 0).toFixed(1)}%`;

const colorByStockStatus = (status) => stockStatusPalette[status] || '#94a3b8';

const buildStockOverviewFromRows = (rows = []) => {
    const safeRows = Array.isArray(rows) ? rows : [];
    const overview = {
        total_products: safeRows.length,
        critical_products: 0,
        watch_products: 0,
        healthy_products: 0,
        dormant_products: 0,
        high_stock_products: 0,
        total_units_in_stock: 0,
        health_percentage: 0,
    };

    safeRows.forEach((row) => {
        const value = Number(row?.value ?? 0);
        const status = row?.status ?? 'healthy';
        overview.total_units_in_stock += value;

        if (status === 'critical') overview.critical_products += 1;
        else if (status === 'watch') overview.watch_products += 1;
        else if (status === 'dormant') overview.dormant_products += 1;
        else if (status === 'high') overview.high_stock_products += 1;
        else overview.healthy_products += 1;
    });

    if (overview.total_products > 0) {
        overview.health_percentage = (((overview.healthy_products + overview.watch_products + overview.high_stock_products) / overview.total_products) * 100);
    }

    return overview;
};

const buildStockRingDataset = (overview = {}) => {
    const segments = [
        { label: 'Critiques', value: Number(overview.critical_products || 0), color: colorByStockStatus('critical') },
        { label: 'A surveiller', value: Number(overview.watch_products || 0), color: colorByStockStatus('watch') },
        { label: 'Stables', value: Number(overview.healthy_products || 0), color: colorByStockStatus('healthy') },
        { label: 'Dormants', value: Number(overview.dormant_products || 0), color: colorByStockStatus('dormant') },
        { label: 'Stock eleve', value: Number(overview.high_stock_products || 0), color: colorByStockStatus('high') },
    ].filter((segment) => segment.value > 0);

    if (!segments.length) {
        return {
            labels: ['Aucun produit'],
            values: [1],
            colors: [chartTrackColor()],
        };
    }

    return {
        labels: segments.map((segment) => segment.label),
        values: segments.map((segment) => segment.value),
        colors: segments.map((segment) => segment.color),
    };
};


// Cette fonction transforme le taux de croissance en note simple sur 100 pour le radar.
const normalizeGrowthScore = (value) => {
    const safeValue = Number(value || 0);

    if (safeValue <= -100) return 0;
    if (safeValue >= 100) return 100;

    return Math.max(0, Math.min(100, 50 + (safeValue / 2)));
};

const renderPriorityGauge = (value, label, caption) => {
    const safeValue = Math.min(100, Math.max(0, Number(value || 0)));
    const rotation = -90 + (safeValue * 1.8);
    safeSetText(document.getElementById('dashboard-ai-priority-score'), `${Math.round(safeValue)}%`);
    safeSetText(document.getElementById('dashboard-ai-priority-label'), label);
    safeSetText(document.getElementById('dashboard-ai-priority-caption'), caption);

    const needle = document.getElementById('dashboard-ai-speedometer-needle');
    if (needle) {
        needle.style.transform = `rotate(${rotation}deg)`;
    }
};

// Cette fonction change le texte et la classe d un badge visuel.
const updateBadge = (id, label, tone = 'neutral') => {
    const element = document.getElementById(id);
    if (!element) return;

    element.textContent = label;
    element.classList.remove('success', 'warning', 'danger', 'info', 'neutral');
    element.classList.add(tone);
};

// Cette fonction transforme les alertes et anomalies en planning simplifie de type Gantt.
const buildGanttDataset = (payload) => {
    const stockRows = (payload.charts?.stock_rotation ?? []).slice(0, 4);
    const anomalies = (payload.anomalies ?? []).slice(0, 2);
    const rows = [];

    stockRows.forEach((row, index) => {
        rows.push({
            label: `Stock ${row.label}`,
            value: Math.max(20, Math.min(100, Number(row.value || 0) * 10)),
            color: colorByStatus(row.status),
        });
    });

    anomalies.forEach((row, index) => {
        rows.push({
            label: row.title ?? `Action ${index + 1}`,
            value: index === 0 ? 90 : 65,
            color: row.severity === 'eleve' ? '#d14343' : '#d48b1f',
        });
    });

    if (!rows.length) {
        return [{
            label: 'Aucune action urgente',
            value: 0,
            color: '#94a3b8',
        }];
    }

    return rows.slice(0, 6);
};

// Cette fonction construit un faux resultat vide pour remettre le dashboard IA a zero.
const buildZeroAnalysisPayload = () => {
    const revenueLabels = (dashboardAiSeed?.monthlyRevenue ?? []).map((row) => row.label);
    const categoryLabels = (dashboardAiSeed?.categoryPerformance ?? []).map((row) => row.label);
    const stockLabels = (dashboardAiSeed?.categoryStock ?? []).map((row) => row.label);

    return {
        executive_summary: 'Analyse reinitialisee. Les indicateurs sont revenus a zero.',
        analysis_origin: 'local',
        analysis_origin_label: 'Aucune analyse active',
        detailed_analysis: {
            finance: 'Aucune analyse en cours.',
            stock: 'Aucune analyse en cours.',
            behavior: 'Aucune analyse en cours.',
        },
        download_summary: {
            title: 'Analyse reinitialisee',
            summary: 'Les valeurs ont ete remises a zero. Relancez une analyse pour obtenir un nouveau resultat.',
            conclusion: 'Le dashboard attend une nouvelle analyse.',
        },
        recommendations: [],
        anomalies: [],
        alerts: [],
        global_score: {
            score: 0,
        },
        charts: {
            revenue_trend: revenueLabels.map((label) => ({ label, value: 0 })),
            revenue_by_product: (categoryLabels.length ? categoryLabels : ['Aucune categorie']).map((label) => ({ label, value: 0 })),
            stock_rotation: (stockLabels.length ? stockLabels : ['Aucun stock']).map((label) => ({ label, value: 0, status: 'neutral' })),
            score_gauge: { value: 0 },
        },
    };
};

// Cette fonction injecte toute la reponse IA dans le dashboard et reconstruit les graphiques.
const renderAiDashboard = (payload) => {
    // Ici se fait le remplissage complet du dashboard IA apres reception d une analyse.
    const isVerifiedAiResponse = payload.analysis_origin === 'ia' && payload.analysis_mode !== 'local_graphs';
    if (aiOutput) {
        aiOutput.hidden = !isVerifiedAiResponse;
    }
    const scoreValue = payload.global_score?.score ?? 0;
    const originLabel = payload.analysis_origin_label ?? 'Source non connue';
    const originMessage = payload.analysis_origin === 'ia'
        ? 'Le resultat affiche provient bien de l assistant IA et a ete structure pour le dashboard.'
        : 'Le resultat affiche provient du moteur local de secours de l application.';
    const chartScoreValue = payload.charts?.score_gauge?.value ?? scoreValue;
    const alertCount = payload.alerts?.length ?? 0;
    const cancelRate = dashboardAiSeed?.cancelRate ?? 0;
    const activeUserRate = dashboardAiSeed?.activeUserRate ?? 0;
    const grossMargin = dashboardAiSeed?.grossMargin ?? 0;
    const averageTicket = dashboardAiSeed?.averageTicket ?? 0;
    const currency = dashboardAiSeed?.currency ?? '';
    const revenueTrend = makeSafeSeries(normalizeRows(payload.charts?.revenue_trend, dashboardAiSeed?.monthlyRevenue?.map((row) => ({
        label: row.label,
        value: row.amount,
    })) ?? []));
    const revenueByProduct = makeSafeSeries(normalizeRows(payload.charts?.revenue_by_product, dashboardAiSeed?.revenueByProduct ?? []));
    const stockDistribution = makeSafeSeries(normalizeRows(payload.charts?.stock_distribution, dashboardAiSeed?.stockDistribution ?? []), 'Aucun produit');
    const stockRotation = makeSafeSeries(normalizeRows(payload.charts?.stock_rotation, stockDistribution));
    const stockOverview = payload.stock_overview ?? dashboardAiSeed?.stockOverview ?? buildStockOverviewFromRows(stockDistribution);
    const stockHealth = Number(stockOverview.health_percentage ?? dashboardAiSeed?.stockHealth ?? 0);
    const growthRate = calculateGrowthRate(revenueTrend);
    const topCategory = revenueByProduct[0] ?? { label: dashboardAiSeed?.topCategoryLabel ?? 'Aucune categorie', value: dashboardAiSeed?.topCategoryValue ?? 0 };
    const riskyStockCount = Number(stockOverview.critical_products || 0) + Number(stockOverview.watch_products || 0);
    const stockAlertRate = Number(stockOverview.total_products || 0) > 0
        ? (riskyStockCount / Number(stockOverview.total_products || 0)) * 100
        : (dashboardAiSeed?.stockAlertRate ?? 0);
    const radarMetrics = [
        Math.min(100, Math.max(0, Number(scoreValue || 0))),
        Math.min(100, Math.max(0, Number(stockHealth || 0))),
        Math.min(100, Math.max(0, Number(activeUserRate || 0))),
        Math.min(100, Math.max(0, 100 - Number(cancelRate || 0))),
        Math.min(100, Math.max(0, 100 - Number(stockAlertRate || 0))),
        normalizeGrowthScore(growthRate),
    ];
    const mainPriority = payload.anomalies?.[0]?.title
        ?? payload.alerts?.[0]?.title
        ?? payload.recommendations?.[0]
        ?? 'Continuer le suivi des ventes et du stock';

    // Ici on met a jour les valeurs textuelles visibles dans les cartes et les resumes.
    safeSetText(document.getElementById('dashboard-ai-score-text'), `${scoreValue}/100`);
    safeSetText(document.getElementById('dashboard-ai-cancel-text'), `${Math.round(cancelRate)}%`);
    safeSetText(document.getElementById('dashboard-ai-stock-text'), `${Math.round(stockHealth)}%`);
    safeSetText(document.getElementById('dashboard-ai-user-text'), `${Math.round(activeUserRate)}%`);
    safeSetText(document.getElementById('dashboard-ai-gross-margin'), `${Number(grossMargin).toFixed(2)} ${currency}`);
    safeSetText(document.getElementById('dashboard-ai-average-ticket'), `${Number(averageTicket).toFixed(2)} ${currency}`);
    safeSetText(document.getElementById('dashboard-ai-stock-badge'), `${Number(stockOverview.total_products || 0)}`);
    safeSetText(document.getElementById('dashboard-ai-total-products'), `${Number(stockOverview.total_products || 0)}`);
    safeSetText(document.getElementById('dashboard-ai-critical-products'), `${Number(stockOverview.critical_products || 0)}`);
    safeSetText(document.getElementById('dashboard-ai-watch-products'), `${Number(stockOverview.watch_products || 0)}`);
    safeSetText(document.getElementById('dashboard-ai-healthy-products'), `${Number(stockOverview.healthy_products || 0)}`);
    safeSetText(document.getElementById('ai-alert-count-inline'), `${alertCount}`);
    renderAiNotifications(payload.alerts ?? []);
    safeSetText(document.getElementById('dashboard-ai-meter-score-text'), `${scoreValue}/100`);
    safeSetText(document.getElementById('dashboard-ai-meter-stock-text'), `${Math.round(stockHealth)}%`);
    safeSetText(document.getElementById('dashboard-ai-meter-user-text'), `${Math.round(activeUserRate)}%`);
    safeSetText(document.getElementById('dashboard-ai-meter-cancel-text'), `${Math.round(cancelRate)}%`);
    safeSetText(document.getElementById('dashboard-ai-growth-rate'), formatPercent(growthRate));
    safeSetText(document.getElementById('dashboard-ai-growth-caption'), growthRate >= 0 ? 'La dynamique du chiffre d affaires reste orientee a la hausse.' : 'Le chiffre d affaires baisse par rapport au mois precedent.');
    safeSetText(document.getElementById('dashboard-ai-top-category'), topCategory.label ?? 'Aucune categorie');
    safeSetText(document.getElementById('dashboard-ai-top-category-value'), `${Number(topCategory.value ?? 0).toFixed(2)} ${currency}`);
    safeSetText(document.getElementById('dashboard-ai-stock-alert-rate'), formatPercent(stockAlertRate));
    safeSetText(document.getElementById('dashboard-ai-stock-alert-caption'), stockAlertRate > 40 ? 'Le stock demande une attention rapide pour eviter les ruptures.' : 'Le stock reste globalement sous controle.');
    safeSetText(document.getElementById('dashboard-ai-priority-text'), mainPriority);
    const priorityScore = Math.min(100, Math.max(0, (alertCount * 18) + stockAlertRate + Math.max(0, cancelRate - 5)));
    const priorityLabel = priorityScore < 35 ? 'Niveau faible' : (priorityScore < 70 ? 'Niveau moyen' : 'Niveau eleve');
    renderPriorityGauge(priorityScore, priorityLabel, 'Action proposee automatiquement apres lecture des donnees.');
    updateBadge('dashboard-ai-growth-badge', growthRate >= 0 ? 'Hausse' : 'Baisse', growthRate >= 0 ? 'success' : 'danger');
    safeSetText(document.getElementById('dashboard-ai-meter-score-fill'), '');
    safeSetText(document.getElementById('dashboard-ai-meter-stock-fill'), '');
    safeSetText(document.getElementById('dashboard-ai-meter-user-fill'), '');
    safeSetText(document.getElementById('dashboard-ai-meter-cancel-fill'), '');
    const scoreFill = document.getElementById('dashboard-ai-meter-score-fill');
    const stockFill = document.getElementById('dashboard-ai-meter-stock-fill');
    const userFill = document.getElementById('dashboard-ai-meter-user-fill');
    const cancelFill = document.getElementById('dashboard-ai-meter-cancel-fill');
    if (scoreFill) scoreFill.style.width = `${Math.min(100, Math.max(0, scoreValue))}%`;
    if (stockFill) stockFill.style.width = `${Math.min(100, Math.max(0, stockHealth))}%`;
    if (userFill) userFill.style.width = `${Math.min(100, Math.max(0, activeUserRate))}%`;
    if (cancelFill) cancelFill.style.width = `${Math.min(100, Math.max(0, cancelRate))}%`;

    setText('ai-executive-summary', payload.executive_summary ?? '');
    setText('ai-origin-badge', originLabel);
    setText('ai-origin-text', originMessage);
    setText('ai-analysis-finance', payload.detailed_analysis?.finance ?? '');
    setText('ai-analysis-stock', payload.detailed_analysis?.stock ?? '');
    setText('ai-analysis-behavior', payload.detailed_analysis?.behavior ?? '');
    const reportTitle = document.getElementById('ai-report-title');
    const reportSummary = document.getElementById('ai-report-summary');
    const reportConclusion = document.getElementById('ai-report-conclusion');

    safeSetText(reportTitle, payload.download_summary?.title ?? 'Rapport IA');
    safeSetText(reportSummary, payload.download_summary?.summary ?? payload.executive_summary ?? '');
    safeSetText(reportConclusion, payload.download_summary?.conclusion ?? '');

    const alertsList = document.getElementById('ai-alerts-list');
    const recommendations = document.getElementById('ai-recommendations');
    const anomalies = document.getElementById('ai-anomalies');

    if (alertsList) {
        alertsList.innerHTML = (payload.alerts ?? []).map((alert) => `
            <div class="note">
                <div class="note-head">
                    <strong>${alert.title ?? 'Alerte'}</strong>
                    <span class="status-badge ${alert.severity === 'eleve' ? 'status-danger' : (alert.severity === 'faible' ? 'status-success' : 'status-warning')}">${alert.severity ?? 'moyen'}</span>
                </div>
                <div class="muted">${alert.message ?? ''}</div>
            </div>
        `).join('') || '<p class="muted">Aucune alerte IA.</p>';
    }

    if (recommendations) {
        recommendations.innerHTML = (payload.recommendations ?? []).map((item) => `<div class="note"><div class="muted">${item}</div></div>`).join('') || '<p class="muted">Aucune recommandation.</p>';
    }

    if (anomalies) {
        anomalies.innerHTML = (payload.anomalies ?? []).map((item) => `
            <div class="note">
                <div class="note-head">
                    <strong>${item.title ?? 'Anomalie'}</strong>
                    <span class="status-badge ${item.severity === 'eleve' ? 'status-danger' : (item.severity === 'faible' ? 'status-success' : 'status-warning')}">${item.severity ?? 'moyen'}</span>
                </div>
                <div class="muted">${item.message ?? ''}</div>
            </div>
        `).join('') || '<p class="muted">Aucune anomalie detectee.</p>';
    }

    // Ici on construit le graphique d evolution du chiffre d affaires.
    upsertChart('dashboard-ai-revenue-chart', {
        type: 'line',
        data: {
            labels: revenueTrend.map((row) => row.label),
            datasets: [{
                label: 'CA',
                data: revenueTrend.map((row) => row.value),
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(29,78,216,.14)',
                pointBackgroundColor: '#1d4ed8',
                pointRadius: 4,
                tension: 0.35,
                fill: true,
            }],
        },
        options: basicChartOptions(),
    });

    // Ici on construit le graphique des categories ou produits qui rapportent le plus.
    upsertChart('dashboard-ai-category-chart', {
        type: 'bar',
        data: {
            labels: revenueByProduct.map((row) => row.label),
            datasets: [{
                label: 'Produits qui rapportent',
                data: revenueByProduct.map((row) => row.value),
                backgroundColor: revenueByProduct.map((_, index) => chartPalette[index % chartPalette.length]),
                borderColor: chartBorderColor(),
                borderWidth: 2,
                borderRadius: 12,
                maxBarThickness: 20,
            }],
        },
        options: basicChartOptions({
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => `${context.label}: ${Number(context.raw || 0).toFixed(2)} ${currency}`,
                    },
                },
            },
        }),
    });

    // Ici on construit le graphique de l etat du stock.
    upsertChart('dashboard-ai-stock-health-chart', {
        type: 'bar',
        data: {
            labels: stockRotation.map((row) => row.label),
            datasets: [{
                label: 'Rotation et risque stock',
                data: stockRotation.map((row) => row.value),
                backgroundColor: stockRotation.map((row) => colorByStatus(row.status)),
                borderColor: chartBorderColor(),
                borderWidth: 2,
                borderRadius: 12,
                maxBarThickness: 20,
            }],
        },
        options: basicChartOptions({ indexAxis: 'y' }),
    });

    // Ici on construit le grand graphique circulaire de repartition des ventes.
    upsertChart('dashboard-ai-product-share-chart', {
        type: 'doughnut',
        data: {
            labels: stockDistribution.map((row) => row.label),
            datasets: [{
                data: stockDistribution.map((row) => row.value),
                backgroundColor: stockDistribution.map((row, index) => colorByStockStatus(row.status) || chartPalette[index % chartPalette.length]),
                borderColor: chartBorderColor(),
                borderWidth: 3,
                hoverOffset: 10,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '64%',
            plugins: {
                legend: { position: 'bottom', labels: { color: chartTextColor(), boxWidth: 14, boxHeight: 14, padding: 16, usePointStyle: true, pointStyle: 'circle', font: chartLegendFont() } },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)} unite(s)`,
                    },
                },
            },
        },
    });

    // Ces cadrans resumment les indicateurs principaux du dashboard IA.
    upsertChart('dashboard-ai-user-ring', {
        type: 'doughnut',
        data: {
            labels: ['Actifs', 'Reste'],
            datasets: [{
                data: [Math.min(100, Math.max(0, activeUserRate)), 100 - Math.min(100, Math.max(0, activeUserRate))],
                backgroundColor: ['#eb9a04', chartTrackColor()],
                borderWidth: 0,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });

    upsertChart('dashboard-ai-stock-ring', {
        type: 'doughnut',
        data: {
            labels: buildStockRingDataset(stockOverview).labels,
            datasets: [{
                data: buildStockRingDataset(stockOverview).values,
                backgroundColor: buildStockRingDataset(stockOverview).colors,
                borderColor: chartBorderColor(),
                borderWidth: 3,
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '78%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)} produit(s)`,
                    },
                },
            },
        },
    });

    upsertChart('dashboard-ai-cancel-ring', {
        type: 'doughnut',
        data: {
            labels: ['Annulations', 'Reste'],
            datasets: [{
                data: [Math.min(100, Math.max(0, cancelRate)), 100 - Math.min(100, Math.max(0, cancelRate))],
                backgroundColor: ['#f59e0b', chartTrackColor()],
                borderWidth: 0,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });

    upsertChart('dashboard-ai-score-ring', {
        type: 'doughnut',
        data: {
            labels: ['Score', 'Reste'],
            datasets: [{
                data: [chartScoreValue, 100 - chartScoreValue],
                backgroundColor: [getScoreTone(chartScoreValue), chartTrackColor()],
                borderWidth: 0,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });

    // Ici on ajoute un radar pour lire l equilibre global du business sous un autre angle.
    upsertChart('dashboard-business-radar-chart', {
        type: 'radar',
        data: {
            labels: ['Score', 'Stock', 'Equipe', 'Factures', 'Alertes', 'Croissance'],
            datasets: [{
                label: 'Equilibre global',
                data: radarMetrics,
                fill: true,
                backgroundColor: 'rgba(29, 78, 216, 0.14)',
                borderColor: '#1d4ed8',
                pointBackgroundColor: '#eb7d34',
                pointBorderColor: '#ffffff',
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: '#1d4ed8',
                pointRadius: 4,
                pointHoverRadius: 5,
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}/100`,
                    },
                },
            },
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        color: chartTextColor(),
                        backdropColor: 'transparent',
                        showLabelBackdrop: false,
                        font: { size: 11, weight: '700' },
                    },
                    pointLabels: {
                        color: chartTextColor(),
                        font: { size: 12, weight: '800' },
                    },
                    grid: { color: chartGridColor() },
                    angleLines: { color: chartGridColor() },
                },
            },
        },
    });

};

const clearAiCharts = (chartIds) => {
    // Cette fonction detruit proprement les graphiques selectionnes.
    chartIds.forEach((id) => {
        if (aiCharts[id]) {
            aiCharts[id].destroy();
            delete aiCharts[id];
        }

        const canvas = document.getElementById(id);
        const context = canvas?.getContext?.('2d');
        if (context && canvas) {
            context.clearRect(0, 0, canvas.width, canvas.height);
        }
    });
};

const resetAiDashboard = () => {
    // Cette fonction remet le dashboard IA a zero tout en gardant les graphiques visibles.
    if (aiOutput) aiOutput.hidden = false;

    const zeroPayload = buildZeroAnalysisPayload();
    renderAiDashboard(zeroPayload);
    renderAiNotifications([]);
};

const resetAiPanel = () => {
    // Cette fonction reinitialise a la fois le panneau lateral et le dashboard IA.
    lastAnalysisPayload = null;
    enableAiDownload(false);
    resetAiDashboard();

    if (aiLoading) aiLoading.hidden = true;
    if (aiSidepanelContent) {
        aiSidepanelContent.innerHTML = '<p class="muted">Analyse reinitialisee. Lancez une analyse locale ou une analyse avec IA.</p>';
    }
    if (aiChatHistory) aiChatHistory.innerHTML = '';
};

const setAiStatus = (message) => {
    // Cette fonction affiche un message d etat pendant les traitements IA.
    if (aiLoading) {
        aiLoading.hidden = false;
        if (aiLoadingTitle) {
            safeSetText(aiLoadingTitle, 'Analyse en cours...');
        }
        if (aiLoadingMessage) {
            safeSetText(aiLoadingMessage, message);
        }
        return;
    }

    if (aiSidepanelContent) {
        aiSidepanelContent.insertAdjacentHTML('afterbegin', `<div class="note"><div class="muted">${message}</div></div>`);
    }
};

const setAiBusy = (busy, message = '') => {
    isAiAnalyzing = busy;

    [
        aiRunButton,
        aiRunLocalButton,
        aiRefreshButton,
        aiSidepanelRun,
        aiSidepanelRunLocal,
        aiSidepanelReset,
    ].forEach((button) => {
        if (!button) return;
        button.disabled = busy;
        button.classList.toggle('is-disabled', busy);
        button.setAttribute('aria-disabled', busy ? 'true' : 'false');
    });

    if (aiLoading) {
        if (busy) {
            aiLoading.classList.remove('is-success', 'is-error');
            aiLoading.hidden = false;
        }
    }

    if (busy) {
        if (aiLoadingTitle) {
            safeSetText(aiLoadingTitle, 'Analyse en cours...');
        }
        if (aiLoadingMessage) {
            safeSetText(aiLoadingMessage, message || 'Veuillez patienter pendant que le module IA traite les donnees.');
        }
    }
};

const setAiFinished = (title, message, state = 'success') => {
    if (!aiLoading) return;

    aiLoading.hidden = false;
    aiLoading.classList.remove('is-success', 'is-error');
    aiLoading.classList.add(state === 'error' ? 'is-error' : 'is-success');

    if (aiLoadingTitle) {
        safeSetText(aiLoadingTitle, title);
    }

    if (aiLoadingMessage) {
        safeSetText(aiLoadingMessage, message);
    }
};

const runAiAnalysis = async (mode = 'ia', force = false) => {
    // Cette fonction lance soit une analyse locale pour les graphiques, soit une analyse complete avec IA.
    if (!window.aiRoutes?.analyze || isAiAnalyzing) return;

    const loadingMessage = mode === 'local'
        ? 'Analyse locale en cours. Les graphiques se mettent a jour, merci de patienter.'
        : 'Analyse IA en cours.';

    setAiBusy(true, loadingMessage);
    setAiStatus(loadingMessage);

    try {
        // Ici on appelle Laravel pour recuperer soit les graphiques locaux, soit le rapport IA complet.
        const payload = await postJson(window.aiRoutes.analyze, { force, mode });
        lastAnalysisPayload = payload;
        renderAiDashboard(payload);
        renderSidepanelSummary(payload);
        enableAiDownload(payload.analysis_origin === 'ia' && payload.analysis_mode !== 'local_graphs');
        setAiFinished(
            'Analyse terminee',
            mode === 'local'
                ? 'Analyse locale terminee. Les graphiques ont ete mis a jour.'
                : 'Analyse IA terminee. Vous pouvez maintenant consulter le resultat.'
        );
    } catch (error) {
        setAiFinished('Analyse interrompue', error.message, 'error');
    } finally {
        setAiBusy(false);
    }
};

// Cette zone relie les boutons du panneau IA a leurs actions.
if (aiFab && aiSidepanel) {
    aiFab.addEventListener('click', () => aiSidepanel.classList.add('open'));
}

if (aiSidepanelClose && aiSidepanel) {
    aiSidepanelClose.addEventListener('click', () => aiSidepanel.classList.remove('open'));
}

if (aiSidepanelRun) {
    aiSidepanelRun.addEventListener('click', () => runAiAnalysis('ia', false));
}

if (aiSidepanelRunLocal) {
    aiSidepanelRunLocal.addEventListener('click', () => runAiAnalysis('local', true));
}

if (aiSidepanelSummary) {
    aiSidepanelSummary.addEventListener('click', () => {
        if (!lastAnalysisPayload) {
            showSidepanelMessage('Lancez d abord une analyse. Ensuite je pourrai faire un resume clair.');
            return;
        }

        if (lastAnalysisPayload.analysis_mode === 'local_graphs') {
            showSidepanelMessage('Le resume ecrit est disponible seulement apres une analyse avec IA.');
            return;
        }

        renderSidepanelSummary(lastAnalysisPayload);
    });
}

if (aiSidepanelReset) {
    aiSidepanelReset.addEventListener('click', resetAiPanel);
}

if (aiSidepanelDownload) {
    aiSidepanelDownload.addEventListener('click', (event) => {
        if (!lastAnalysisPayload || lastAnalysisPayload.analysis_mode === 'local_graphs' || lastAnalysisPayload.analysis_origin !== 'ia') {
            event.preventDefault();
            showSidepanelMessage('Telechargement disponible seulement apres une vraie analyse IA.');
        }
    });
}

if (aiRunButton) {
    aiRunButton.addEventListener('click', () => runAiAnalysis('ia', false));
}

if (aiRunLocalButton) {
    aiRunLocalButton.addEventListener('click', () => runAiAnalysis('local', true));
}

if (aiRefreshButton) {
    aiRefreshButton.addEventListener('click', resetAiPanel);
}

if (aiSidepanelAskOpen) {
    aiSidepanelAskOpen.addEventListener('click', openAiQuestionDialog);
}

if (aiOpenQuestionDialogButton) {
    aiOpenQuestionDialogButton.addEventListener('click', openAiQuestionDialog);
}

if (aiDialogClose) {
    aiDialogClose.addEventListener('click', closeAiQuestionDialog);
}

document.querySelectorAll('[data-ai-dialog-close]').forEach((element) => {
    element.addEventListener('click', closeAiQuestionDialog);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAiQuestionDialog();
    }
});

const appendAiDialogExchange = (question, answer, summary = '') => {
    if (!aiDialogBody) return;

    const emptyState = document.getElementById('ai-chat-empty');
    if (emptyState) {
        emptyState.remove();
    }

    aiDialogBody.insertAdjacentHTML('afterbegin', `
        <div class="ai-chat-entry">
            <div class="ai-chat-bubble ai-chat-bubble-answer">
                <strong>Reponse IA</strong>
                <div class="muted">${answer}</div>
                ${summary.trim() !== '' ? `<div class="muted">${summary}</div>` : ''}
            </div>
            <div class="ai-chat-bubble ai-chat-bubble-question">
                <strong>Question</strong>
                <div class="muted">${question}</div>
            </div>
        </div>
    `);
};

// Cette zone gere la boite de dialogue de question a l IA.
if (aiDialogForm && window.aiRoutes?.ask) {
    aiDialogForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!aiDialogQuestion || !aiDialogQuestion.value.trim()) return;

        const question = aiDialogQuestion.value.trim();
        aiDialogQuestion.value = '';

        if (lastAnalysisPayload?.analysis_mode === 'local_graphs') {
            appendAiDialogExchange(question, 'Le chat detaille fonctionne mieux apres une analyse avec IA.', '');
        }

        try {
            const payload = await postJson(window.aiRoutes.ask, { question });
            appendAiDialogExchange(question, payload.answer ?? '', payload.short_summary ?? '');
        } catch (error) {
            if (aiDialogBody) {
                aiDialogBody.insertAdjacentHTML('afterbegin', `<div class="alert alert-danger">${error.message}</div>`);
            }
        }
    });
}

const renderDashboardSeedCharts = () => {
    // Cette fonction affiche les graphiques de base avant qu une analyse IA soit lancee.
    if (!dashboardAiSeed || typeof Chart === 'undefined') return;

    upsertChart('dashboard-ai-score-ring', {
        type: 'doughnut',
        data: { labels: ['Score', 'Reste'], datasets: [{ data: [dashboardAiSeed.score, 100 - dashboardAiSeed.score], backgroundColor: [dashboardAiSeed.score < 50 ? '#d14343' : (dashboardAiSeed.score < 70 ? '#d48b1f' : '#1d4ed8'), chartTrackColor()], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });
    upsertChart('dashboard-ai-cancel-ring', {
        type: 'doughnut',
        data: { labels: ['Annulation', 'Reste'], datasets: [{ data: [dashboardAiSeed.cancelRate, 100 - dashboardAiSeed.cancelRate], backgroundColor: ['#f59e0b', chartTrackColor()], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });
    upsertChart('dashboard-ai-stock-ring', {
        type: 'doughnut',
        data: { labels: ['Sain', 'Reste'], datasets: [{ data: [dashboardAiSeed.stockHealth, 100 - dashboardAiSeed.stockHealth], backgroundColor: ['#0f9d76', chartTrackColor()], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });
    upsertChart('dashboard-ai-user-ring', {
        type: 'doughnut',
        data: { labels: ['Actifs', 'Reste'], datasets: [{ data: [dashboardAiSeed.activeUserRate, 100 - dashboardAiSeed.activeUserRate], backgroundColor: ['#1d4ed8', chartTrackColor()], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false } } },
    });
    upsertChart('dashboard-ai-category-chart', {
        type: 'bar',
        data: {
            labels: (dashboardAiSeed.categoryPerformance ?? []).map((row) => row.label),
            datasets: [{ data: (dashboardAiSeed.categoryPerformance ?? []).map((row) => row.value), backgroundColor: ['#2563eb', '#0f9d76', '#f59e0b', '#ef4444', '#14b8a6', '#8b5cf6'], borderColor: chartBorderColor(), borderWidth: 2, borderRadius: 10, maxBarThickness: 18 }],
        },
        options: basicChartOptions({ indexAxis: 'y' }),
    });
    upsertChart('dashboard-ai-stock-health-chart', {
        type: 'bar',
        data: {
            labels: (dashboardAiSeed.categoryStock ?? []).map((row) => row.label),
            datasets: [{ data: (dashboardAiSeed.categoryStock ?? []).map((row) => row.value), backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#0f9d76', '#34d399', '#93c5fd'], borderColor: chartBorderColor(), borderWidth: 2, borderRadius: 10, maxBarThickness: 18 }],
        },
        options: basicChartOptions({ indexAxis: 'y' }),
    });
    upsertChart('dashboard-ai-revenue-chart', {
        type: 'line',
        data: {
            labels: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.label),
            datasets: [{ data: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.amount), borderColor: '#1d4ed8', backgroundColor: 'rgba(29,78,216,.12)', fill: true, tension: 0.35 }],
        },
        options: basicChartOptions(),
    });
    upsertChart('dashboard-ai-product-share-chart', {
        type: 'doughnut',
        data: {
            labels: (dashboardAiSeed.categoryPerformance ?? []).map((row) => row.label),
            datasets: [{
                data: (dashboardAiSeed.categoryPerformance ?? []).map((row) => row.value),
                backgroundColor: chartPalette,
                borderColor: chartBorderColor(),
                borderWidth: 3,
                hoverOffset: 10,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '64%',
            plugins: {
                legend: { position: 'bottom', labels: { color: chartTextColor(), boxWidth: 14, boxHeight: 14, padding: 16, usePointStyle: true, pointStyle: 'circle', font: chartLegendFont() } },
            },
        },
    });

    upsertChart('dashboard-business-revenue-chart', {
        type: 'line',
        data: {
            labels: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.label),
            datasets: [{
                label: 'Chiffre d affaires',
                data: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.amount),
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(56,189,248,.10)',
                pointBackgroundColor: '#1d4ed8',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 4,
                tension: 0.35,
                fill: true,
            }],
        },
        options: basicChartOptions({
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => `${Number(context.parsed.y || 0).toFixed(2)} ${dashboardAiSeed.currency ?? ''}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: chartTextColor(), maxRotation: 0, autoSkip: false, font: { size: 10 } },
                    grid: { color: 'rgba(148, 163, 184, 0.08)', drawBorder: false },
                    border: { display: false },
                },
                y: {
                    ticks: {
                        color: chartTextColor(),
                        font: { size: 10 },
                        callback: (value) => Number(value).toLocaleString(),
                    },
                    grid: { color: chartGridColor(), drawBorder: false },
                    border: { display: false },
                },
            },
        }),
    });

    upsertChart('dashboard-business-radar-chart', {
        type: 'radar',
        data: {
            labels: ['Score', 'Stock', 'Equipe', 'Factures', 'Alertes', 'Croissance'],
            datasets: [{
                label: 'Equilibre global',
                data: [
                    Math.min(100, Math.max(0, Number(dashboardAiSeed.score ?? 0))),
                    Math.min(100, Math.max(0, Number(dashboardAiSeed.stockHealth ?? 0))),
                    Math.min(100, Math.max(0, Number(dashboardAiSeed.activeUserRate ?? 0))),
                    Math.min(100, Math.max(0, 100 - Number(dashboardAiSeed.cancelRate ?? 0))),
                    Math.min(100, Math.max(0, 100 - Number(dashboardAiSeed.stockAlertRate ?? 0))),
                    normalizeGrowthScore(dashboardAiSeed.monthlyGrowthRate ?? 0),
                ],
                fill: true,
                backgroundColor: 'rgba(29, 78, 216, 0.14)',
                borderColor: '#1d4ed8',
                pointBackgroundColor: '#eb7d34',
                pointBorderColor: '#ffffff',
                pointRadius: 4,
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}/100`,
                    },
                },
            },
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        color: chartTextColor(),
                        backdropColor: 'transparent',
                        showLabelBackdrop: false,
                        font: { size: 10 },
                    },
                    pointLabels: {
                        color: chartTextColor(),
                        font: { size: 11, weight: '700' },
                    },
                    grid: { color: chartGridColor() },
                    angleLines: { color: chartGridColor() },
                },
            },
        },
    });

    const seedPriorityScore = Math.min(100, Math.max(0, (dashboardAiSeed.stockAlertRate ?? 0) + Math.max(0, (dashboardAiSeed.cancelRate ?? 0) - 5)));
    const seedPriorityLabel = seedPriorityScore < 35 ? 'Niveau faible' : (seedPriorityScore < 70 ? 'Niveau moyen' : 'Niveau eleve');
    renderPriorityGauge(seedPriorityScore, seedPriorityLabel, 'L indicateur montre l urgence generale a traiter.');
};

renderDashboardSeedCharts();

// Cette zone initialise quelques textes du dashboard a partir des donnees deja disponibles au chargement.
if (dashboardAiSeed) {
    safeSetText(document.getElementById('dashboard-ai-growth-rate'), formatPercent(dashboardAiSeed.monthlyGrowthRate ?? 0));
    safeSetText(document.getElementById('dashboard-ai-top-category'), dashboardAiSeed.topCategoryLabel ?? 'Aucune categorie');
    safeSetText(document.getElementById('dashboard-ai-top-category-value'), `${Number(dashboardAiSeed.topCategoryValue ?? 0).toFixed(2)} ${dashboardAiSeed.currency ?? ''}`);
    safeSetText(document.getElementById('dashboard-ai-stock-alert-rate'), formatPercent(dashboardAiSeed.stockAlertRate ?? 0));
    updateBadge('dashboard-ai-growth-badge', (dashboardAiSeed.monthlyGrowthRate ?? 0) >= 0 ? 'Hausse' : 'Baisse', (dashboardAiSeed.monthlyGrowthRate ?? 0) >= 0 ? 'success' : 'danger');
}

renderAiNotifications(dashboardAiSeed?.notifications ?? []);

// Cette derniere zone gere l ajout et la suppression dynamique des lignes de facture.
if (addLineButton && invoiceLines && Array.isArray(window.invoiceProducts)) {
    const renderOptions = () => window.invoiceProducts.map(product =>
        `<option value="${product.id}">${product.name} (${product.stock} en stock)</option>`
    ).join('');

    const attachRemoveListeners = () => {
        document.querySelectorAll('.remove-line').forEach(button => {
            button.onclick = () => {
                if (invoiceLines.children.length > 1) {
                    button.parentElement.remove();
                    renameLines();
                }
            };
        });
    };

    const renameLines = () => {
        [...invoiceLines.children].forEach((line, index) => {
            const select = line.querySelector('select');
            const quantity = line.querySelector('input[type="number"]');
            select.name = `items[${index}][produit_id]`;
            quantity.name = `items[${index}][quantity]`;
        });
    };

    // Ici on ajoute une nouvelle ligne de produit dans le formulaire de facture.
    addLineButton.onclick = () => {
        const wrapper = document.createElement('div');
        wrapper.className = 'invoice-line';
        wrapper.innerHTML = `
            <select required>${renderOptions()}</select>
            <input type="number" min="1" value="1" required>
            <button type="button" class="btn btn-danger remove-line">Retirer</button>
        `;
        invoiceLines.appendChild(wrapper);
        renameLines();
        attachRemoveListeners();
    };

    attachRemoveListeners();
    renameLines();
}
