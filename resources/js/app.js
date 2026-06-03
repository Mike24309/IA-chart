// Cette zone recupere les elements principaux du DOM utilises dans toute l interface.
const addLineButton = document.getElementById('add-line');
const invoiceLines = document.getElementById('invoice-lines');
const themeToggle = document.querySelector('[data-theme-toggle]');
const aiFab = document.getElementById('ai-fab');
const aiSidepanel = document.getElementById('ai-sidepanel');
const aiSidepanelClose = document.getElementById('ai-sidepanel-close');
const aiSidepanelRun = document.getElementById('ai-sidepanel-run');
const aiSidepanelSummary = document.getElementById('ai-sidepanel-summary');
const aiSidepanelAskOpen = document.getElementById('ai-sidepanel-ask-open');
const aiSidepanelReset = document.getElementById('ai-sidepanel-reset');
const aiSidepanelDownload = document.getElementById('ai-sidepanel-download');
const aiSidepanelContent = document.getElementById('ai-sidepanel-content');
const aiRunButton = document.getElementById('ai-run-analysis');
const aiRunSummaryButton = document.getElementById('ai-run-summary');
const aiRefreshButton = document.getElementById('ai-refresh-analysis');
const aiAnalysisPeriodSelect = document.getElementById('dashboard-analysis-period');
const aiLoading = document.getElementById('ai-loading');
const aiLoadingTitle = document.getElementById('ai-loading-title');
const aiLoadingMessage = document.getElementById('ai-loading-message');
const aiOutput = document.getElementById('ai-output');
const aiOpenQuestionDialogButton = document.getElementById('ai-open-question-dialog');
const aiQuestionDialog = document.getElementById('ai-question-dialog');
const aiDialogClose = document.getElementById('ai-dialog-close');
const aiDialogClearHistory = document.getElementById('ai-dialog-clear-history');
const aiDialogForm = document.getElementById('ai-dialog-form');
const aiDialogQuestion = document.getElementById('ai-dialog-question');
const aiDialogBody = document.getElementById('ai-dialog-body');
const aiDialogConversationList = document.getElementById('ai-dialog-conversation-list');
const aiDialogHistoryCount = document.getElementById('ai-dialog-history-count');
const aiDialogSubmitButton = document.getElementById('ai-dialog-submit');
const aiDialogNewConversation = document.getElementById('ai-dialog-new-conversation');
const aiCharts = {};
let lastAnalysisPayload = null;
let isAiAnalyzing = false;
const defaultSummaryButtonLabel = aiSidepanelSummary?.textContent?.trim() || 'Generer le resume IA';
const dashboardAiSeed = window.dashboardAiSeed || null;
let autoFilterTimer = null;
const aiDialogConversationsStorageKey = 'ia-chart-ai-dialog-conversations';
const aiDialogActiveConversationStorageKey = 'ia-chart-ai-dialog-active-conversation';
const aiDialogLegacyHistoryStorageKey = 'ia-chart-ai-dialog-history';
let aiDialogConversations = [];
let aiDialogActiveConversationId = null;

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
        let message = 'Erreur de communication avec le module d interpretation.';
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
    const periodLabel = payload.company?.analysis_period_label ?? dashboardAiSeed?.analysisPeriodLabel ?? 'Periode courante';
    const isLocalAnalysis = payload.analysis_origin === 'local';
    const executiveSummary = pickDisplayText(
        payload.executive_summary,
        payload.download_summary?.summary,
        isLocalAnalysis
            ? 'Les donnees calculees sont disponibles. Ouvrez la sidebar IA puis lancez le resume IA pour obtenir l interpretation.'
        : 'Le tableau de bord a ete mis a jour a partir du resume IA des donnees calculees.'
    );
    const keySummary = pickDisplayText(
        payload.download_summary?.summary,
        payload.executive_summary,
        isLocalAnalysis
            ? 'Les indicateurs sont prets. Le bouton de resume dans la sidebar IA permet maintenant de produire le resume IA.'
            : 'Les indicateurs sont prets pour la lecture et le commentaire.'
    );
    const originMessage = isLocalAnalysis
        ? 'Ce resultat provient des donnees calculees localement et ne depend pas encore du resume IA.'
        : 'Ce resultat provient du resume IA et repose sur les calculs du dashboard.';
    const originBadgeClass = isLocalAnalysis ? 'status-info' : 'status-success';
    const summaryTitle = payload.download_summary?.title || 'Resume de la periode';
    const finance = pickDisplayText(payload.detailed_analysis?.finance, 'Lecture finance disponible dans le rapport.');
    const stock = pickDisplayText(payload.detailed_analysis?.stock, 'Lecture stock disponible dans le rapport.');
    const behavior = pickDisplayText(payload.detailed_analysis?.behavior, 'Lecture utilisateurs disponible dans le rapport.');

    aiSidepanelContent.innerHTML = `
        <div class="note">
            <div class="note-head">
                <strong>Source</strong>
                <span class="status-badge ${originBadgeClass}">${escapeHtml(originLabel)}</span>
            </div>
            <div class="muted">${escapeHtml(originMessage)}</div>
        </div>
        <div class="note">
            <div class="note-head">
                <strong>Rapport rapide</strong>
                <span class="status-badge status-info">${escapeHtml(periodLabel)}</span>
            </div>
            <div class="muted">${escapeHtml(summaryTitle)}</div>
            <div class="summary-score"><strong>${payload.global_score?.score ?? 0}/100</strong></div>
        </div>
        <div class="note">
            <strong>Lecture de la periode</strong>
            <div class="muted">${escapeHtml(executiveSummary)}</div>
        </div>
        <div class="note">
            <strong>Finance</strong>
            <div class="muted">${escapeHtml(finance)}</div>
        </div>
        <div class="note">
            <strong>Stock</strong>
            <div class="muted">${escapeHtml(stock)}</div>
        </div>
        <div class="note">
            <strong>Utilisateurs</strong>
            <div class="muted">${escapeHtml(behavior)}</div>
        </div>
        ${alerts.map((alert) => `
            <div class="note">
                <div class="note-head">
                    <strong>${escapeHtml(alert.title ?? 'Alerte')}</strong>
                    <span class="status-badge ${alert.severity === 'eleve' ? 'status-danger' : (alert.severity === 'faible' ? 'status-success' : 'status-warning')}">${escapeHtml(alert.severity ?? 'moyen')}</span>
                </div>
                <div class="muted">${escapeHtml(alert.message ?? '')}</div>
            </div>
        `).join('')}
        <div class="note">
            <strong>Recommandations</strong>
            <ul class="ai-summary-list">
                ${recommendations.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}
            </ul>
        </div>
    `;
};

// Cette fonction ajoute un message simple en haut du panneau lateral IA.
const showSidepanelMessage = (message) => {
    if (!aiSidepanelContent) return;
    aiSidepanelContent.insertAdjacentHTML('afterbegin', `<div class="note"><div class="muted">${message}</div></div>`);
};

const showSidepanelLoading = (title, message) => {
    if (!aiSidepanelContent) return;
    aiSidepanelContent.innerHTML = `
        <div class="note">
            <div class="note-head">
                <strong>${title}</strong>
                <span class="status-badge status-warning">En cours</span>
            </div>
            <div class="muted">${message}</div>
        </div>
    `;
};

const setSummaryButtonLoading = (loading) => {
    if (!aiSidepanelSummary) return;

    aiSidepanelSummary.disabled = loading;
    aiSidepanelSummary.classList.toggle('is-loading', loading);
    aiSidepanelSummary.textContent = loading ? 'Resume IA en cours...' : defaultSummaryButtonLabel;
};

const openAiQuestionDialog = () => {
    if (!aiQuestionDialog) return;
    ensureAiDialogConversationState();
    aiQuestionDialog.hidden = false;
    document.body.classList.add('dialog-open');
    renderAiDialogHistory();
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

const getSelectedAiAnalysisPeriod = () => {
    if (aiAnalysisPeriodSelect?.value) return aiAnalysisPeriodSelect.value;
    return dashboardAiSeed?.analysisPeriod ?? 'annual';
};

const buildAiReportUrl = () => {
    if (!window.aiRoutes?.report) return '#';

    const url = new URL(window.aiRoutes.report, window.location.origin);
    url.searchParams.set('analysis_period', getSelectedAiAnalysisPeriod());

    return `${url.pathname}${url.search}`;
};

const syncAiReportLink = () => {
    if (!aiSidepanelDownload) return;
    aiSidepanelDownload.setAttribute('href', buildAiReportUrl());
};

const reloadDashboardForSelectedPeriod = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('analysis_period', getSelectedAiAnalysisPeriod());
    window.location.href = url.toString();
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

const pickDisplayText = (...values) => {
    for (const value of values) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value.trim();
        }

        if (typeof value === 'number' && Number.isFinite(value)) {
            return String(value);
        }
    }

    return '';
};

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
}[character] ?? character));

const formatAiDialogTimestamp = (value) => {
    if (!value) return '';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const normalizeAiDialogEntry = (entry) => {
    if (!entry || typeof entry !== 'object') {
        return null;
    }

    return {
        id: typeof entry.id === 'string' && entry.id ? entry.id : `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        question: typeof entry.question === 'string' ? entry.question : '',
        answer: typeof entry.answer === 'string' ? entry.answer : '',
        summary: typeof entry.summary === 'string' ? entry.summary : '',
        pending: Boolean(entry.pending),
        error: typeof entry.error === 'string' ? entry.error : '',
        createdAt: typeof entry.createdAt === 'string' ? entry.createdAt : new Date().toISOString(),
    };
};

const normalizeAiDialogConversation = (conversation, index = 0) => {
    if (!conversation || typeof conversation !== 'object') {
        return null;
    }

    const entries = Array.isArray(conversation.entries)
        ? conversation.entries.map(normalizeAiDialogEntry).filter(Boolean)
        : [];
    const createdAt = typeof conversation.createdAt === 'string' ? conversation.createdAt : new Date().toISOString();
    const updatedAt = typeof conversation.updatedAt === 'string'
        ? conversation.updatedAt
        : (entries[entries.length - 1]?.createdAt || createdAt);
    const firstQuestion = entries.find((entry) => entry.question)?.question ?? '';
    const fallbackTitle = firstQuestion.trim() ? firstQuestion.trim() : `Conversation ${index + 1}`;

    return {
        id: typeof conversation.id === 'string' && conversation.id ? conversation.id : `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        title: typeof conversation.title === 'string' && conversation.title.trim() ? conversation.title.trim() : fallbackTitle,
        createdAt,
        updatedAt,
        entries,
    };
};

const createAiDialogConversation = (title = 'Nouvelle conversation') => ({
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
    title,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    entries: [],
});

const getAiDialogConversationTitle = (conversation, index = 0) => {
    const title = typeof conversation?.title === 'string' ? conversation.title.trim() : '';
    if (title) return title;

    const firstQuestion = conversation?.entries?.find((entry) => entry?.question)?.question?.trim() ?? '';
    if (firstQuestion) return firstQuestion.slice(0, 64);

    return `Conversation ${index + 1}`;
};

const getAiDialogConversationPreview = (conversation) => {
    const entries = Array.isArray(conversation?.entries) ? conversation.entries : [];
    const lastEntry = entries[entries.length - 1];
    const lastText = lastEntry?.answer || lastEntry?.question || '';
    const preview = lastText.trim().replace(/\s+/g, ' ');

    return preview.slice(0, 96) || 'Nouvelle conversation';
};

const refreshAiDialogConversationTitle = (conversation, index = 0) => {
    const firstQuestion = conversation?.entries?.find((entry) => entry?.question)?.question?.trim() ?? '';
    const currentTitle = typeof conversation?.title === 'string' ? conversation.title.trim() : '';
    const isGenericTitle = !currentTitle
        || currentTitle === 'Nouvelle conversation'
        || /^Conversation \d+$/i.test(currentTitle);

    if (isGenericTitle && firstQuestion) {
        return firstQuestion.slice(0, 64);
    }

    if (currentTitle) {
        return currentTitle;
    }

    if (firstQuestion) {
        return firstQuestion.slice(0, 64);
    }

    return `Conversation ${index + 1}`;
};

const readAiDialogConversations = () => {
    try {
        const raw = window.localStorage.getItem(aiDialogConversationsStorageKey);
        if (raw) {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed.map(normalizeAiDialogConversation).filter(Boolean) : [];
        }

        const legacyRaw = window.localStorage.getItem(aiDialogLegacyHistoryStorageKey);
        if (legacyRaw) {
            const parsed = JSON.parse(legacyRaw);
            const legacyEntries = Array.isArray(parsed)
                ? parsed.map(normalizeAiDialogEntry).filter(Boolean).reverse()
                : [];

            if (legacyEntries.length > 0) {
                return [normalizeAiDialogConversation({
                    id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
                    title: legacyEntries.find((entry) => entry.question)?.question?.trim() || 'Conversation 1',
                    createdAt: legacyEntries[0]?.createdAt || new Date().toISOString(),
                    updatedAt: legacyEntries[legacyEntries.length - 1]?.createdAt || new Date().toISOString(),
                    entries: legacyEntries,
                })].filter(Boolean);
            }
        }
    } catch (error) {
        return [];
    }

    return [];
};

const persistAiDialogConversations = () => {
    try {
        window.localStorage.setItem(aiDialogConversationsStorageKey, JSON.stringify(aiDialogConversations));
        if (aiDialogActiveConversationId) {
            window.localStorage.setItem(aiDialogActiveConversationStorageKey, aiDialogActiveConversationId);
        }
    } catch (error) {
        // Rien a faire si le stockage local est bloque.
    }
};

const getActiveAiDialogConversation = () => {
    if (aiDialogConversations.length === 0) return null;
    return aiDialogConversations.find((conversation) => conversation.id === aiDialogActiveConversationId) || aiDialogConversations[0];
};

const ensureAiDialogConversationState = () => {
    if (aiDialogConversations.length === 0) {
        const starterConversation = createAiDialogConversation();
        aiDialogConversations = [starterConversation];
        aiDialogActiveConversationId = starterConversation.id;
        persistAiDialogConversations();
        return;
    }

    if (!getActiveAiDialogConversation()) {
        aiDialogActiveConversationId = aiDialogConversations[0].id;
    }

    persistAiDialogConversations();
};

const updateAiDialogHistoryCount = () => {
    if (!aiDialogHistoryCount) return;

    const count = aiDialogConversations.length;
    aiDialogHistoryCount.textContent = count === 1 ? '1 conversation' : `${count} conversations`;
};

const renderAiDialogConversationList = () => {
    if (!aiDialogConversationList) return;

    if (aiDialogConversations.length === 0) {
        aiDialogConversationList.innerHTML = `
            <div class="ai-dialog-conversation-empty">
                <strong>Aucune conversation</strong>
                <div class="muted">Cliquez sur le bouton + pour commencer une nouvelle discussion.</div>
            </div>
        `;
        updateAiDialogHistoryCount();
        return;
    }

    aiDialogConversationList.innerHTML = aiDialogConversations.map((conversation, index) => {
        const active = conversation.id === aiDialogActiveConversationId;
        const title = escapeHtml(getAiDialogConversationTitle(conversation, index));
        const preview = escapeHtml(getAiDialogConversationPreview(conversation));
        const meta = `${conversation.entries.length} message${conversation.entries.length > 1 ? 's' : ''}`;
        const updatedAt = formatAiDialogTimestamp(conversation.updatedAt);

        return `
            <button
                type="button"
                class="ai-dialog-conversation-item ${active ? 'is-active' : ''}"
                data-conversation-id="${escapeHtml(conversation.id)}"
            >
                <strong>${title}</strong>
                <div class="ai-dialog-conversation-preview">${preview}</div>
                <div class="ai-dialog-conversation-meta">
                    <span>${meta}</span>
                    <span>${escapeHtml(updatedAt || 'A l instant')}</span>
                </div>
            </button>
        `;
    }).join('');

    updateAiDialogHistoryCount();
};

const renderAiDialogHistory = () => {
    if (!aiDialogBody) return;

    const activeConversation = getActiveAiDialogConversation();

    if (!activeConversation || activeConversation.entries.length === 0) {
        aiDialogBody.innerHTML = `
            <div class="ai-chat-empty" id="ai-chat-empty">
                <strong>Assistant IA</strong>
                <div class="muted">Vos conversations apparaissent ici. Ouvrez une nouvelle discussion ou reprenez un ancien fil depuis la colonne de gauche.</div>
            </div>
        `;
        renderAiDialogConversationList();
        return;
    }

    aiDialogBody.innerHTML = activeConversation.entries.map((entry) => {
        const question = escapeHtml(entry.question ?? '');
        const answer = escapeHtml(entry.answer ?? '');
        const error = escapeHtml(entry.error ?? '');
        const timestamp = formatAiDialogTimestamp(entry.createdAt);
        const pending = Boolean(entry.pending);
        const statusClass = entry.error ? 'status-danger' : (pending ? 'status-warning' : 'status-success');
        const statusLabel = entry.error ? 'Erreur' : (pending ? 'En attente' : 'Repondu');

        return `
            <article class="ai-chat-entry ${pending ? 'is-pending' : ''}">
                <div class="ai-chat-bubble ai-chat-bubble-question">
                    <strong>Question</strong>
                    <div class="muted">${question}</div>
                    ${timestamp ? `<div class="muted">${timestamp}</div>` : ''}
                </div>
                <div class="ai-chat-bubble ai-chat-bubble-answer ${pending ? 'is-pending' : ''}">
                    <div class="note-head">
                        <strong>Reponse IA</strong>
                        <span class="status-badge ${statusClass}">${statusLabel}</span>
                    </div>
                    ${
                        pending
                            ? `<div class="ai-chat-status"><span class="ai-chat-spinner" aria-hidden="true"></span><span>En attente de la reponse...</span></div>`
                            : (
                                error
                                    ? `<div class="muted">${error}</div>`
                                    : `<div class="muted">${answer}</div>`
                            )
                    }
                </div>
            </article>
        `;
    }).join('');

    if (aiDialogBody.scrollTo) {
        aiDialogBody.scrollTo({ top: aiDialogBody.scrollHeight, behavior: 'smooth' });
    } else {
        aiDialogBody.scrollTop = aiDialogBody.scrollHeight;
    }

    renderAiDialogConversationList();
};

const addAiDialogEntry = (entry) => {
    ensureAiDialogConversationState();

    const conversation = getActiveAiDialogConversation();
    if (!conversation) return entry.id;

    conversation.entries = [...conversation.entries, entry];
    conversation.title = refreshAiDialogConversationTitle(conversation, aiDialogConversations.findIndex((item) => item.id === conversation.id));
    conversation.updatedAt = entry.createdAt || new Date().toISOString();
    aiDialogConversations = aiDialogConversations.map((item) => (
        item.id === conversation.id ? { ...conversation } : item
    ));
    persistAiDialogConversations();
    renderAiDialogHistory();
    return entry.id;
};

const updateAiDialogEntry = (id, patch) => {
    aiDialogConversations = aiDialogConversations.map((conversation) => {
        const entryIndex = conversation.entries.findIndex((entry) => entry.id === id);
        if (entryIndex === -1) return conversation;

        const nextEntries = conversation.entries.map((entry) => (
            entry.id === id ? { ...entry, ...patch } : entry
        ));

        const nextConversation = {
            ...conversation,
            entries: nextEntries,
            updatedAt: new Date().toISOString(),
        };

        nextConversation.title = refreshAiDialogConversationTitle(
            nextConversation,
            aiDialogConversations.findIndex((item) => item.id === conversation.id)
        );

        return nextConversation;
    });
    persistAiDialogConversations();
    renderAiDialogHistory();
};

const clearAiDialogHistory = () => {
    aiDialogConversations = [];
    aiDialogActiveConversationId = null;
    persistAiDialogConversations();
    renderAiDialogHistory();
};

const startNewAiConversation = () => {
    const newConversation = createAiDialogConversation();
    aiDialogConversations = [...aiDialogConversations, newConversation];
    aiDialogActiveConversationId = newConversation.id;
    persistAiDialogConversations();
    renderAiDialogHistory();

    if (aiDialogQuestion) {
        aiDialogQuestion.value = '';
        aiDialogQuestion.focus();
    }
};

const submitAutoFilterForm = (form) => {
    if (!form) return;
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
    }

    form.submit();
};

const compactSeries = (rows = [], limit = 5, otherLabel = 'Autres') => {
    const safeRows = Array.isArray(rows) ? rows.filter((row) => Number(row?.value ?? 0) > 0) : [];

    if (safeRows.length <= limit) {
        return safeRows;
    }

    const primaryRows = safeRows.slice(0, limit);
    const otherRows = safeRows.slice(limit);
    const otherValue = otherRows.reduce((sum, row) => sum + Number(row?.value ?? 0), 0);

    if (otherValue <= 0) {
        return primaryRows;
    }

    return [
        ...primaryRows,
        {
            label: otherLabel,
            value: otherValue,
            status: 'grouped',
        },
    ];
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
const chartGridColor = () => 'rgba(148, 163, 184, 0.14)';
const chartTrackColor = () => '#e8eef5';
const chartPalette = ['#1d4ed8', '#0f766e', '#f97316', '#7c3aed', '#0891b2', '#dc2626', '#65a30d', '#c026d3', '#0f9d76', '#be123c'];
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

const formatCompactNumber = (value, digits = 0) => Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
});

const getCanvasContext = (canvasId) => document.getElementById(canvasId)?.getContext('2d') ?? null;

const createVerticalGradient = (canvasId, from, to) => {
    const ctx = getCanvasContext(canvasId);
    if (!ctx) return to;

    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, from);
    gradient.addColorStop(1, to);
    return gradient;
};

const createHorizontalGradient = (canvasId, from, to) => {
    const ctx = getCanvasContext(canvasId);
    if (!ctx) return to;

    const gradient = ctx.createLinearGradient(0, 0, 360, 0);
    gradient.addColorStop(0, from);
    gradient.addColorStop(1, to);
    return gradient;
};

const buildRingOptions = (labelFormatter) => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '80%',
    animation: {
        animateRotate: true,
        animateScale: true,
        duration: 950,
        easing: 'easeOutCubic',
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            displayColors: false,
            padding: 12,
            cornerRadius: 12,
            titleFont: { size: 13, weight: '800' },
            bodyFont: { size: 12, weight: '700' },
            callbacks: {
                label: labelFormatter,
            },
        },
    },
});

const basicChartOptions = (extra = {}) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 950,
        easing: 'easeOutCubic',
    },
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 12,
            displayColors: false,
            boxPadding: 4,
            titleFont: { size: 13, weight: '800' },
            bodyFont: { size: 12, weight: '700' },
        },
    },
    scales: {
        x: {
            ticks: { color: chartTextColor(), font: chartTickFont(), padding: 8 },
            grid: { color: chartGridColor(), drawBorder: false },
            border: { display: false },
        },
        y: {
            ticks: { color: chartTextColor(), font: chartTickFont(), padding: 8 },
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
const createSeriesPalette = (rows = []) => rows.map((row, index) => {
    if (row?.status === 'grouped') {
        return '#94a3b8';
    }

    return chartPalette[index % chartPalette.length];
});
const shortenLabel = (label, maxLength = 26) => {
    const safeLabel = String(label ?? '').trim();

    if (safeLabel.length <= maxLength) {
        return safeLabel;
    }

    return `${safeLabel.slice(0, maxLength - 1).trim()}…`;
};
const renderStockDistributionLegend = (rows = [], colors = []) => {
    const legend = document.getElementById('dashboard-ai-stock-legend');
    if (!legend) return;

    if (!Array.isArray(rows) || !rows.length) {
        legend.innerHTML = '<div class="muted">Aucune donnee de stock a afficher.</div>';
        return;
    }

    legend.innerHTML = rows.map((row, index) => `
        <div class="dashboard-stock-legend-item">
            <span class="dashboard-stock-legend-swatch" style="background:${colors[index] ?? '#94a3b8'}"></span>
            <span class="dashboard-stock-legend-label">${row.label ?? ''}</span>
            <span class="dashboard-stock-legend-value">${formatCompactNumber(row.value ?? 0)}</span>
        </div>
    `).join('');
};
const renderRevenueLegend = (rows = [], currency = '') => {
    const legend = document.getElementById('dashboard-ai-revenue-legend');
    if (!legend) return;

    const meaningfulRows = Array.isArray(rows) ? rows.filter((row) => Number(row?.value ?? 0) > 0) : [];

    if (!meaningfulRows.length) {
        legend.innerHTML = '';
        return;
    }

    legend.innerHTML = meaningfulRows.map((row, index) => `
        <div class="dashboard-revenue-legend-item">
            <span class="dashboard-revenue-legend-rank">${index + 1}</span>
            <span class="dashboard-revenue-legend-label">${row.label ?? ''}</span>
            <span class="dashboard-revenue-legend-value">${formatCompactNumber(row.value ?? 0, 2)} ${currency}</span>
        </div>
    `).join('');
};
const toggleChartEmptyState = (canvasId, emptyId, hasData) => {
    const canvas = document.getElementById(canvasId);
    const emptyState = document.getElementById(emptyId);

    if (canvas) {
        canvas.style.display = hasData ? '' : 'none';
    }

    if (emptyState) {
        emptyState.hidden = hasData;
    }
};

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
        overview.health_percentage = ((overview.healthy_products / overview.total_products) * 100);
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
            summary: 'Les valeurs ont ete remises a zero. Relancez l analyse locale pour obtenir un nouveau resultat.',
            conclusion: 'Le dashboard attend une nouvelle analyse locale.',
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
    const isVerifiedAiResponse = payload.analysis_origin === 'ia';
    if (aiOutput) {
        aiOutput.hidden = false;
    }
    const scoreValue = payload.global_score?.score ?? 0;
    const originLabel = payload.analysis_origin_label ?? 'Source non connue';
    const executiveSummary = pickDisplayText(
        payload.executive_summary,
        payload.download_summary?.summary,
        'Le tableau de bord a ete actualise a partir des donnees calculees localement.'
    );
    const financeSummary = pickDisplayText(
        payload.detailed_analysis?.finance,
        executiveSummary,
        'Les resultats financiers visibles sur le dashboard servent de base au commentaire.'
    );
    const stockSummary = pickDisplayText(
        payload.detailed_analysis?.stock,
        'Le stock est interprete a partir des mouvements, du disponible et des alertes visibles.'
    );
    const behaviorSummary = pickDisplayText(
        payload.detailed_analysis?.behavior,
        'L activite utilisateur est lue a partir des operations enregistrees dans l application.'
    );
    const reportTitleText = pickDisplayText(
        payload.download_summary?.title,
        'Rapport de performance'
    );
    const reportSummaryText = pickDisplayText(
        payload.download_summary?.summary,
        executiveSummary,
        'Le resume du dashboard sera affiche ici apres analyse.'
    );
    const reportConclusionText = pickDisplayText(
        payload.download_summary?.conclusion,
        'Consultez les indicateurs du dashboard pour presenter la situation generale.'
    );
    const originMessage = 'Le resultat affiche provient des donnees calculees localement, puis de leur resume par l IA.';
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
    const revenueByProduct = compactSeries(
        makeSafeSeries(normalizeRows(payload.charts?.revenue_by_product, dashboardAiSeed?.revenueByProduct ?? [])),
        5,
        'Autres produits'
    );
    const stockDistribution = compactSeries(
        makeSafeSeries(normalizeRows(payload.charts?.stock_distribution, dashboardAiSeed?.stockDistribution ?? []), 'Aucun produit'),
        5,
        'Autres produits'
    );
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

    setText('ai-executive-summary', executiveSummary);
    setText('ai-origin-badge', originLabel);
    setText('ai-origin-text', originMessage);
    setText('ai-analysis-finance', financeSummary);
    setText('ai-analysis-stock', stockSummary);
    setText('ai-analysis-behavior', behaviorSummary);
    const reportTitle = document.getElementById('ai-report-title');
    const reportSummary = document.getElementById('ai-report-summary');
    const reportConclusion = document.getElementById('ai-report-conclusion');

    safeSetText(reportTitle, reportTitleText);
    safeSetText(reportSummary, reportSummaryText);
    safeSetText(reportConclusion, reportConclusionText);

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
        `).join('') || '<p class="muted">Aucune alerte retournee.</p>';
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
                borderColor: createHorizontalGradient('dashboard-ai-revenue-chart', '#1d4ed8', '#0f9d76'),
                backgroundColor: createVerticalGradient('dashboard-ai-revenue-chart', 'rgba(29,78,216,.24)', 'rgba(15,118,110,.02)'),
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#1d4ed8',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBorderWidth: 3,
                tension: 0.42,
                fill: true,
            }],
        },
        options: basicChartOptions({
            scales: {
                x: {
                    ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10 },
                    grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                    border: { display: false },
                },
                y: {
                    ticks: {
                        color: chartTextColor(),
                        font: chartTickFont(),
                        padding: 10,
                        callback: (value) => formatCompactNumber(value),
                    },
                    grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                    border: { display: false },
                },
            },
        }),
    });

    // Ici on construit le graphique des categories ou produits qui rapportent le plus.
    const revenueByProductColors = createSeriesPalette(revenueByProduct);
    const hasRevenueByProduct = revenueByProduct.some((row) => Number(row?.value ?? 0) > 0);
    toggleChartEmptyState('dashboard-ai-category-chart', 'dashboard-ai-category-empty', hasRevenueByProduct);
    renderRevenueLegend(hasRevenueByProduct ? revenueByProduct : [], currency);
    if (hasRevenueByProduct) {
        upsertChart('dashboard-ai-category-chart', {
            type: 'bar',
            data: {
                labels: revenueByProduct.map((row) => shortenLabel(row.label, 24)),
                datasets: [{
                    label: 'Produits qui rapportent',
                    data: revenueByProduct.map((row) => row.value),
                    backgroundColor: revenueByProductColors,
                    borderColor: chartBorderColor(),
                    borderWidth: 2,
                    borderRadius: 14,
                    maxBarThickness: 18,
                    barPercentage: 0.68,
                    categoryPercentage: 0.72,
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
                            title: (items) => revenueByProduct[items[0]?.dataIndex ?? 0]?.label ?? '',
                            label: (context) => `${Number(context.raw || 0).toFixed(2)} ${currency}`,
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10, callback: (value) => formatCompactNumber(value) },
                        grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                        border: { display: false },
                    },
                    y: {
                        ticks: { color: chartTextColor(), font: { size: 11, weight: '800' }, padding: 10 },
                        grid: { display: false, drawBorder: false },
                        border: { display: false },
                    },
                },
            }),
        });
    } else if (aiCharts['dashboard-ai-category-chart']) {
        aiCharts['dashboard-ai-category-chart'].destroy();
        delete aiCharts['dashboard-ai-category-chart'];
    }

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
                borderRadius: 14,
                maxBarThickness: 18,
                barPercentage: 0.68,
                categoryPercentage: 0.72,
            }],
        },
        options: basicChartOptions({
            indexAxis: 'y',
            scales: {
                x: {
                    ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10, callback: (value) => formatCompactNumber(value) },
                    grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                    border: { display: false },
                },
                y: {
                    ticks: { color: chartTextColor(), font: { size: 11, weight: '800' }, padding: 10 },
                    grid: { display: false, drawBorder: false },
                    border: { display: false },
                },
            },
        }),
    });

    // Ici on construit le grand graphique circulaire de repartition des ventes.
    const stockDistributionColors = createSeriesPalette(stockDistribution);
    upsertChart('dashboard-ai-product-share-chart', {
        type: 'doughnut',
        data: {
            labels: stockDistribution.map((row) => shortenLabel(row.label, 24)),
            datasets: [{
                data: stockDistribution.map((row) => row.value),
                backgroundColor: stockDistributionColors,
                borderColor: chartBorderColor(),
                borderWidth: 3,
                hoverOffset: 12,
                spacing: 3,
                borderRadius: 6,
            }],
        },
        options: buildRingOptions((context) => `${Number(context.raw || 0).toFixed(0)} unite(s)`),
    });
    renderStockDistributionLegend(stockDistribution, stockDistributionColors);

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
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}%`),
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
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)} produit(s)`),
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
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}%`),
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
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}/100`),
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
                backgroundColor: 'rgba(29, 78, 216, 0.12)',
                borderColor: '#1d4ed8',
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#ffffff',
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: '#1d4ed8',
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 3,
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
        aiSidepanelContent.innerHTML = '<p class="muted">Calcul reinitialise. Lancez un nouveau calcul local pour obtenir un nouveau resume IA.</p>';
    }
    if (aiChatHistory) aiChatHistory.innerHTML = '';
};

const setAiStatus = (message) => {
    // Cette fonction affiche un message d etat pendant les traitements IA.
    if (aiLoading) {
        aiLoading.hidden = false;
        if (aiLoadingTitle) {
            safeSetText(aiLoadingTitle, 'Calcul en cours...');
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
        aiRunSummaryButton,
        aiRefreshButton,
        aiSidepanelRun,
        aiSidepanelSummary,
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
            safeSetText(aiLoadingTitle, 'Calcul en cours...');
        }
        if (aiLoadingMessage) {
            safeSetText(aiLoadingMessage, message || 'Veuillez patienter pendant que le dashboard calcule les donnees puis que l IA prepare le resume.');
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
    // Cette fonction lance uniquement l analyse interne du dashboard.
    if (!window.aiRoutes?.analyze || isAiAnalyzing) return;

    const loadingMessage = 'Calcul local du dashboard en cours.';

    setAiBusy(true, loadingMessage);
    setAiStatus(loadingMessage);

    try {
        // Ici on appelle Laravel pour recuperer l analyse interne du dashboard.
        const localPayload = await postJson(window.aiRoutes.analyze, { force, mode, analysis_period: getSelectedAiAnalysisPeriod() });
        lastAnalysisPayload = localPayload;
        renderAiDashboard(localPayload);
        renderSidepanelSummary(localPayload);
        enableAiDownload(false);
        setAiFinished(
            'Calcul termine',
            'Le calcul du dashboard est termine. Vous pouvez maintenant declencher le resume IA.'
        );
    } catch (error) {
        setAiFinished('Calcul interrompu', error.message, 'error');
    } finally {
        setAiBusy(false);
    }
};

const runAiSummary = async (mode = 'ia', force = false) => {
    // Cette fonction demande seulement a l IA de resumer et d interpreter les donnees deja calculees.
    if (!window.aiRoutes?.interpret || isAiAnalyzing) return;

    if (aiSidepanel) {
        aiSidepanel.classList.add('open');
    }

    const loadingMessage = 'L IA prepare le resume et l interpretation.';

    setAiBusy(true, loadingMessage);
    setSummaryButtonLoading(true);
    setAiStatus(loadingMessage);
    showSidepanelLoading('Resume IA en cours', 'L IA est en train de lire les donnees calculees et de preparer le resume.');

    try {
        const interpretedPayload = await postJson(window.aiRoutes.interpret, { force, mode, analysis_period: getSelectedAiAnalysisPeriod() });
        lastAnalysisPayload = interpretedPayload;
        renderAiDashboard(interpretedPayload);
        renderSidepanelSummary(interpretedPayload);
        enableAiDownload(interpretedPayload.analysis_origin === 'ia');
        setAiFinished(
            'Resume IA pret',
            'L IA a termine le resume et l interpretation des donnees calculees.'
        );
        if (aiSidepanel) {
            aiSidepanel.classList.add('open');
        }
    } catch (error) {
        showSidepanelMessage(error.message);
        setAiFinished('Resume IA interrompu', error.message, 'error');
    } finally {
        setSummaryButtonLoading(false);
        setAiBusy(false);
    }
};

window.triggerAiAnalysis = () => runAiAnalysis('ia', false);
window.triggerAiSummary = () => runAiSummary('ia', false);

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

// Le bouton de resume IA est pilote directement depuis le layout pour eviter les doubles declenchements.

if (aiSidepanelReset) {
    aiSidepanelReset.addEventListener('click', resetAiPanel);
}

if (aiSidepanelDownload) {
    aiSidepanelDownload.addEventListener('click', (event) => {
        if (!lastAnalysisPayload || lastAnalysisPayload.analysis_origin !== 'ia') {
            event.preventDefault();
            showSidepanelMessage('Telechargement disponible seulement apres un resume IA complet.');
        }
    });

    syncAiReportLink();
}

if (aiRunButton) {
    aiRunButton.addEventListener('click', () => runAiAnalysis('ia', false));
}

if (aiRunSummaryButton) {
    aiRunSummaryButton.addEventListener('click', () => runAiSummary('ia', false));
}

if (aiRefreshButton) {
    aiRefreshButton.addEventListener('click', reloadDashboardForSelectedPeriod);
}

if (window.initialAiSummaryPayload && typeof window.initialAiSummaryPayload === 'object' && window.initialAiSummaryPayload.analysis_origin === 'ia') {
    lastAnalysisPayload = window.initialAiSummaryPayload;
    renderAiDashboard(window.initialAiSummaryPayload);
    renderSidepanelSummary(window.initialAiSummaryPayload);
    enableAiDownload(true);
}

window.addEventListener('ai-gemini-summary-ready', (event) => {
    const payload = event.detail;
    if (!payload || typeof payload !== 'object') return;

    lastAnalysisPayload = payload;
    renderAiDashboard(payload);
    renderSidepanelSummary(payload);
    enableAiDownload(payload.analysis_origin === 'ia');
    setAiFinished(
        'Resume IA pret',
        'L IA a termine le resume et l interpretation des donnees calculees.'
    );
});

if (aiAnalysisPeriodSelect) {
    aiAnalysisPeriodSelect.addEventListener('change', reloadDashboardForSelectedPeriod);
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
    const entry = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        question,
        answer,
        summary,
        pending: false,
        createdAt: new Date().toISOString(),
    };

    addAiDialogEntry(entry);
    return entry.id;
};

// Cette zone gere la boite de dialogue de question a l IA.
if (aiDialogForm && window.aiRoutes?.ask) {
    aiDialogForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!aiDialogQuestion || !aiDialogQuestion.value.trim()) return;

        const question = aiDialogQuestion.value.trim();
        aiDialogQuestion.value = '';
        const pendingId = addAiDialogEntry({
            id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
            question,
            answer: '',
            summary: '',
            pending: true,
            createdAt: new Date().toISOString(),
        });

        if (aiDialogSubmitButton) {
            aiDialogSubmitButton.disabled = true;
            aiDialogSubmitButton.textContent = 'En attente...';
        }

        try {
            const payload = await postJson(window.aiRoutes.ask, { question });
            updateAiDialogEntry(pendingId, {
                answer: payload.answer ?? '',
                summary: payload.short_summary ?? '',
                pending: false,
                error: '',
            });
        } catch (error) {
            updateAiDialogEntry(pendingId, {
                answer: '',
                summary: '',
                pending: false,
                error: error.message,
            });
        } finally {
            if (aiDialogSubmitButton) {
                aiDialogSubmitButton.disabled = false;
                aiDialogSubmitButton.textContent = 'Envoyer la question';
            }
        }
    });
}

if (aiDialogQuestion) {
    aiDialogQuestion.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            aiDialogForm?.requestSubmit();
        }
    });
}

if (aiDialogNewConversation) {
    aiDialogNewConversation.addEventListener('click', () => {
        startNewAiConversation();
    });
}

if (aiDialogConversationList) {
    aiDialogConversationList.addEventListener('click', (event) => {
        const item = event.target.closest('[data-conversation-id]');
        if (!item) return;

        const conversationId = item.getAttribute('data-conversation-id');
        if (!conversationId || conversationId === aiDialogActiveConversationId) return;

        aiDialogActiveConversationId = conversationId;
        persistAiDialogConversations();
        renderAiDialogHistory();
        aiDialogQuestion?.focus();
    });
}

aiDialogConversations = readAiDialogConversations();
aiDialogActiveConversationId = window.localStorage.getItem(aiDialogActiveConversationStorageKey) || aiDialogConversations[0]?.id || null;
ensureAiDialogConversationState();
renderAiDialogHistory();

const renderDashboardSeedCharts = () => {
    // Cette fonction affiche les graphiques de base avant qu une analyse IA soit lancee.
    if (!dashboardAiSeed || typeof Chart === 'undefined') return;
    const seedStockDistribution = compactSeries(
        makeSafeSeries(normalizeRows(dashboardAiSeed.stockDistribution ?? []), 'Aucun produit'),
        5,
        'Autres produits'
    );
    const seedStockOverview = dashboardAiSeed.stockOverview ?? buildStockOverviewFromRows(seedStockDistribution);
    const seedStockRing = buildStockRingDataset(seedStockOverview);
    const seedRevenueByProduct = compactSeries(
        makeSafeSeries(normalizeRows(dashboardAiSeed.revenueByProduct ?? dashboardAiSeed.categoryPerformance ?? [])),
        5,
        'Autres produits'
    );

    upsertChart('dashboard-ai-score-ring', {
        type: 'doughnut',
        data: { labels: ['Score', 'Reste'], datasets: [{ data: [dashboardAiSeed.score, 100 - dashboardAiSeed.score], backgroundColor: [dashboardAiSeed.score < 50 ? '#d14343' : (dashboardAiSeed.score < 70 ? '#d48b1f' : '#1d4ed8'), chartTrackColor()], borderWidth: 0 }] },
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}/100`),
    });
    upsertChart('dashboard-ai-cancel-ring', {
        type: 'doughnut',
        data: { labels: ['Annulation', 'Reste'], datasets: [{ data: [dashboardAiSeed.cancelRate, 100 - dashboardAiSeed.cancelRate], backgroundColor: ['#f59e0b', chartTrackColor()], borderWidth: 0 }] },
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}%`),
    });
    upsertChart('dashboard-ai-stock-ring', {
        type: 'doughnut',
        data: { labels: seedStockRing.labels, datasets: [{ data: seedStockRing.values, backgroundColor: seedStockRing.colors, borderColor: chartBorderColor(), borderWidth: 3, hoverOffset: 8 }] },
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)} produit(s)`),
    });
    upsertChart('dashboard-ai-user-ring', {
        type: 'doughnut',
        data: { labels: ['Actifs', 'Reste'], datasets: [{ data: [dashboardAiSeed.activeUserRate, 100 - dashboardAiSeed.activeUserRate], backgroundColor: ['#1d4ed8', chartTrackColor()], borderWidth: 0 }] },
        options: buildRingOptions((context) => `${context.label}: ${Number(context.raw || 0).toFixed(0)}%`),
    });
    const seedRevenueColors = createSeriesPalette(seedRevenueByProduct);
    const hasSeedRevenue = seedRevenueByProduct.some((row) => Number(row?.value ?? 0) > 0);
    toggleChartEmptyState('dashboard-ai-category-chart', 'dashboard-ai-category-empty', hasSeedRevenue);
    renderRevenueLegend(hasSeedRevenue ? seedRevenueByProduct : [], dashboardAiSeed.currency ?? '');
    if (hasSeedRevenue) {
        upsertChart('dashboard-ai-category-chart', {
            type: 'bar',
            data: {
                labels: seedRevenueByProduct.map((row) => shortenLabel(row.label, 24)),
                datasets: [{
                    data: seedRevenueByProduct.map((row) => row.value),
                    backgroundColor: seedRevenueColors,
                    borderColor: chartBorderColor(),
                    borderWidth: 2,
                    borderRadius: 14,
                    maxBarThickness: 18,
                    barPercentage: 0.68,
                    categoryPercentage: 0.72,
                }],
            },
            options: basicChartOptions({
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10, callback: (value) => formatCompactNumber(value) },
                        grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                        border: { display: false },
                    },
                    y: {
                        ticks: { color: chartTextColor(), font: { size: 11, weight: '800' }, padding: 10 },
                        grid: { display: false, drawBorder: false },
                        border: { display: false },
                    },
                },
            }),
        });
    } else if (aiCharts['dashboard-ai-category-chart']) {
        aiCharts['dashboard-ai-category-chart'].destroy();
        delete aiCharts['dashboard-ai-category-chart'];
    }
    upsertChart('dashboard-ai-stock-health-chart', {
        type: 'bar',
        data: {
            labels: seedStockDistribution.map((row) => row.label),
            datasets: [{
                data: seedStockDistribution.map((row) => row.value),
                backgroundColor: seedStockDistribution.map((row, index) => colorByStockStatus(row.status) || chartPalette[index % chartPalette.length]),
                borderColor: chartBorderColor(),
                borderWidth: 2,
                borderRadius: 14,
                maxBarThickness: 18,
                barPercentage: 0.68,
                categoryPercentage: 0.72,
            }],
        },
        options: basicChartOptions({
            indexAxis: 'y',
            scales: {
                x: {
                    ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10, callback: (value) => formatCompactNumber(value) },
                    grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                    border: { display: false },
                },
                y: {
                    ticks: { color: chartTextColor(), font: { size: 11, weight: '800' }, padding: 10 },
                    grid: { display: false, drawBorder: false },
                    border: { display: false },
                },
            },
        }),
    });
    upsertChart('dashboard-ai-revenue-chart', {
        type: 'line',
        data: {
            labels: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.label),
            datasets: [{
                data: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.amount),
                borderColor: createHorizontalGradient('dashboard-ai-revenue-chart', '#1d4ed8', '#0f9d76'),
                backgroundColor: createVerticalGradient('dashboard-ai-revenue-chart', 'rgba(29,78,216,.24)', 'rgba(15,118,110,.02)'),
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#1d4ed8',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBorderWidth: 3,
                fill: true,
                tension: 0.42,
            }],
        },
        options: basicChartOptions({
            scales: {
                x: {
                    ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10 },
                    grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                    border: { display: false },
                },
                y: {
                    ticks: { color: chartTextColor(), font: chartTickFont(), padding: 10, callback: (value) => formatCompactNumber(value) },
                    grid: { color: chartGridColor(), drawBorder: false, drawTicks: false },
                    border: { display: false },
                },
            },
        }),
    });
    const seedStockColors = createSeriesPalette(seedStockDistribution);
    upsertChart('dashboard-ai-product-share-chart', {
        type: 'doughnut',
        data: {
            labels: seedStockDistribution.map((row) => shortenLabel(row.label, 24)),
            datasets: [{
                data: seedStockDistribution.map((row) => row.value),
                backgroundColor: seedStockColors,
                borderColor: chartBorderColor(),
                borderWidth: 3,
                hoverOffset: 12,
                spacing: 3,
                borderRadius: 6,
            }],
        },
        options: buildRingOptions((context) => `${Number(context.raw || 0).toFixed(0)} unite(s)`),
    });
    renderStockDistributionLegend(seedStockDistribution, seedStockColors);

    upsertChart('dashboard-business-revenue-chart', {
        type: 'line',
        data: {
            labels: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.label),
            datasets: [{
                label: 'Chiffre d affaires',
                data: (dashboardAiSeed.monthlyRevenue ?? []).map((row) => row.amount),
                borderColor: createHorizontalGradient('dashboard-business-revenue-chart', '#1d4ed8', '#0f9d76'),
                backgroundColor: createVerticalGradient('dashboard-business-revenue-chart', 'rgba(56,189,248,.18)', 'rgba(15,118,110,.03)'),
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.42,
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
    safeSetText(document.getElementById('dashboard-ai-stock-badge'), `${Number(dashboardAiSeed.stockOverview?.total_products ?? 0)}`);
    safeSetText(document.getElementById('dashboard-ai-total-products'), `${Number(dashboardAiSeed.stockOverview?.total_products ?? 0)}`);
    safeSetText(document.getElementById('dashboard-ai-critical-products'), `${Number(dashboardAiSeed.stockOverview?.critical_products ?? 0)}`);
    safeSetText(document.getElementById('dashboard-ai-watch-products'), `${Number(dashboardAiSeed.stockOverview?.watch_products ?? 0)}`);
    safeSetText(document.getElementById('dashboard-ai-healthy-products'), `${Number(dashboardAiSeed.stockOverview?.healthy_products ?? 0)}`);
    updateBadge('dashboard-ai-growth-badge', (dashboardAiSeed.monthlyGrowthRate ?? 0) >= 0 ? 'Hausse' : 'Baisse', (dashboardAiSeed.monthlyGrowthRate ?? 0) >= 0 ? 'success' : 'danger');
}

renderAiNotifications(dashboardAiSeed?.notifications ?? []);

document.querySelectorAll('[data-auto-filter-form]').forEach((form) => {
    const textInputs = form.querySelectorAll('[data-auto-filter-input]');
    const instantInputs = form.querySelectorAll('select, input[type="date"]');

    textInputs.forEach((input) => {
        input.addEventListener('input', () => {
            window.clearTimeout(autoFilterTimer);
            autoFilterTimer = window.setTimeout(() => submitAutoFilterForm(form), 350);
        });
    });

    instantInputs.forEach((input) => {
        input.addEventListener('change', () => submitAutoFilterForm(form));
    });
});
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.querySelector('input[type="file"]') && form.enctype !== 'multipart/form-data') {
        form.enctype = 'multipart/form-data';
    }
});
