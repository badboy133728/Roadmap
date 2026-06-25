import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('quizWizard', (config) => ({
    staticQuestions: config.staticQuestions || [],
    aiQuestionsUrl: config.aiQuestionsUrl,
    csrfToken: config.csrfToken,
    authProfile: config.authProfile || null,
    phase: 'profile',
    profile: { name: '', about: '', status: 'exploring' },
    priorities: [],
    staticStep: 0,
    staticAnswers: {},
    allAiQuestions: [],
    currentBatch: [],
    aiAnswers: {},
    aiStep: 0,
    aiRound: 0,
    aiComplete: false,
    aiClarity: null,
    aiBatchMessage: null,
    aiLoadingMessage: 'Подбираем персональные вопросы под твои ответы',
    aiError: null,
    aiSource: null,
    encouragement: '',

    init() {
        if (this.authProfile?.name && !this.profile.name) {
            this.profile.name = this.authProfile.name;
        }
    },

    statusOptions: [
        { value: 'school_9', label: 'Учусь в 9 классе', emoji: '🎒' },
        { value: 'school_11', label: 'Учусь в 10–11 классе', emoji: '📖' },
        { value: 'student', label: 'Студент / колледж', emoji: '🎓' },
        { value: 'working', label: 'Уже работаю', emoji: '💼' },
        { value: 'exploring', label: 'Просто ищу себя', emoji: '🔍' },
    ],

    priorityOptions: [
        { value: 'money', label: 'Высокий доход', emoji: '💰', desc: 'Хочу зарабатывать хорошо' },
        { value: 'creativity', label: 'Творчество', emoji: '🎨', desc: 'Важно создавать и выражать себя' },
        { value: 'people', label: 'Помогать людям', emoji: '💚', desc: 'Хочу быть полезным' },
        { value: 'stability', label: 'Стабильность', emoji: '🏠', desc: 'Надёжная работа и график' },
        { value: 'growth', label: 'Развитие', emoji: '🚀', desc: 'Учиться и расти в карьере' },
    ],

    encouragements: [
        'Круто! Уже видно твой стиль 🙌',
        'Отличный выбор — идём дальше!',
        'Ты на полпути — так держать!',
        'Ещё чуть-чуть — результат уже близко ✨',
        'Последние вопросы — ты справляешься!',
    ],

    get currentStaticQuestion() {
        return this.staticQuestions[this.staticStep] ?? null;
    },

    get currentAiQuestion() {
        return this.currentBatch[this.aiStep] ?? null;
    },

    get staticProgress() {
        if (!this.staticQuestions.length) return 0;
        return Math.round(((this.staticStep + 1) / this.staticQuestions.length) * 100);
    },

    get aiProgress() {
        if (!this.currentBatch.length) return 0;
        return Math.round(((this.aiStep + 1) / this.currentBatch.length) * 100);
    },

    get profileComplete() {
        return !!this.profile.status;
    },

    get prioritiesComplete() {
        return this.priorities.length >= 1;
    },

    get overallProgress() {
        if (this.phase === 'profile') return 5;
        if (this.phase === 'priorities') return 12;
        if (this.phase === 'static') return Math.round(12 + (this.staticProgress * 0.28));
        if (this.phase === 'ai_loading') return 88;
        if (this.phase === 'ai_questions') {
            const base = 40 + Math.min(this.allAiQuestions.length * 4, 40);
            return Math.round(base + (this.aiProgress * 0.2));
        }
        return 95;
    },

    get currentStaticAnswered() {
        const q = this.currentStaticQuestion;
        return q ? this.staticAnswers[q.id] !== undefined && this.staticAnswers[q.id] !== null : false;
    },

    get allStaticAnswered() {
        return this.staticQuestions.every((q) => this.staticAnswers[q.id] !== undefined && this.staticAnswers[q.id] !== null);
    },

    get currentAiAnswered() {
        const q = this.currentAiQuestion;
        return q ? this.aiAnswers[q.id] !== undefined && this.aiAnswers[q.id] !== null : false;
    },

    get currentBatchAnswered() {
        return this.currentBatch.every((q) => this.aiAnswers[q.id] !== undefined && this.aiAnswers[q.id] !== null);
    },

    get canSubmit() {
        return this.allStaticAnswered && (this.aiComplete || this.allAiQuestions.length === 0);
    },

    get phaseLabel() {
        if (this.phase === 'profile') return 'Шаг 1 — о тебе';
        if (this.phase === 'priorities') return 'Шаг 2 — что важно';
        if (this.phase === 'static') return `Базовый вопрос ${this.staticStep + 1} из ${this.staticQuestions.length}`;
        if (this.phase === 'ai_loading') return 'ИИ анализирует ответы';
        if (this.phase === 'ai_questions') {
            const total = this.allAiQuestions.length;
            const current = this.allAiQuestions.findIndex((q) => q.id === this.currentAiQuestion?.id) + 1;
            return `Уточнение ${current || 1} из ${total || '?'}`;
        }
        return 'Тест';
    },

    startPriorities() {
        if (!this.profileComplete) return;
        this.phase = 'priorities';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    startStatic() {
        if (!this.prioritiesComplete) return;
        this.phase = 'static';
        this.staticStep = 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    selectStatus(value) {
        this.profile = { ...this.profile, status: value };
    },

    togglePriority(value) {
        if (this.priorities.includes(value)) {
            this.priorities = this.priorities.filter((p) => p !== value);
            return;
        }
        if (this.priorities.length >= 2) {
            this.priorities = [this.priorities[1], value];
            return;
        }
        this.priorities = [...this.priorities, value];
    },

    isPrioritySelected(value) {
        return this.priorities.includes(value);
    },

    selectStaticAnswer(optionId) {
        const qid = this.currentStaticQuestion.id;
        this.staticAnswers = { ...this.staticAnswers, [qid]: optionId };
    },

    isStaticSelected(optionId) {
        const q = this.currentStaticQuestion;
        if (!q) return false;
        return this.staticAnswers[q.id] === optionId;
    },

    selectAiAnswer(optionId) {
        const qid = this.currentAiQuestion.id;
        this.aiAnswers = { ...this.aiAnswers, [qid]: optionId };
    },

    isAiSelected(optionId) {
        const q = this.currentAiQuestion;
        if (!q) return false;
        return this.aiAnswers[q.id] === optionId;
    },

    buildAiRequestBody() {
        const body = new FormData();
        body.append('_token', this.csrfToken);
        body.append('name', this.profile.name || '');
        body.append('about', this.profile.about || '');
        body.append('status', this.profile.status);
        body.append('round', String(this.aiRound));
        body.append('ai_questions', JSON.stringify(this.allAiQuestions));
        this.priorities.forEach((p, i) => body.append(`priorities[${i}]`, p));
        Object.entries(this.staticAnswers).forEach(([qid, oid]) => body.append(`static_answers[${qid}]`, oid));
        Object.entries(this.aiAnswers).forEach(([qid, oid]) => body.append(`ai_answers[${qid}]`, oid));
        return body;
    },

    async loadNextAiBatch() {
        this.phase = 'ai_loading';
        this.aiError = null;
        this.aiLoadingMessage = this.aiRound === 0
            ? 'Смотрим, нужны ли уточняющие вопросы'
            : 'ИИ проверяет, достаточно ли уже данных';
        window.scrollTo({ top: 0, behavior: 'smooth' });

        try {
            const response = await fetch(this.aiQuestionsUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: this.buildAiRequestBody(),
            });

            if (!response.ok) {
                throw new Error('fetch failed');
            }

            const data = await response.json();
            this.aiSource = data.source || 'local';
            this.aiClarity = data.clarity ?? null;
            this.aiBatchMessage = data.message || null;

            if (data.complete) {
                this.aiComplete = true;
                this.submitQuiz();
                return;
            }

            const batch = data.questions || [];
            if (!batch.length) {
                this.aiComplete = true;
                this.submitQuiz();
                return;
            }

            this.allAiQuestions = [...this.allAiQuestions, ...batch];
            this.currentBatch = batch;
            this.aiStep = 0;
            this.aiRound++;
            this.phase = 'ai_questions';
        } catch {
            this.aiError = 'Не удалось загрузить вопросы. Попробуй ещё раз.';
            this.phase = this.allAiQuestions.length ? 'ai_questions' : 'static';
        }
    },

    finishStatic() {
        if (!this.allStaticAnswered) return;
        this.aiRound = 0;
        this.allAiQuestions = [];
        this.currentBatch = [];
        this.aiAnswers = {};
        this.aiComplete = false;
        this.loadNextAiBatch();
    },

    finishAiBatch() {
        if (!this.currentBatchAnswered) return;
        this.loadNextAiBatch();
    },

    submitQuiz() {
        this.phase = 'ai_loading';
        this.aiLoadingMessage = 'Считаем твой результат...';
        this.$nextTick(() => this.$refs.quizForm.submit());
    },

    nextStatic() {
        if (!this.currentStaticAnswered) return;
        this.showEncouragement();
        if (this.staticStep < this.staticQuestions.length - 1) {
            this.staticStep++;
        }
    },

    nextAi() {
        if (!this.currentAiAnswered) return;
        if (this.aiStep < this.currentBatch.length - 1) {
            this.aiStep++;
        }
    },

    prevAi() {
        if (this.aiStep > 0) {
            this.aiStep--;
            return;
        }

        if (this.allAiQuestions.length > this.currentBatch.length) {
            const prevBatchSize = this.allAiQuestions.length - this.currentBatch.length;
            this.allAiQuestions = this.allAiQuestions.slice(0, prevBatchSize);
            this.aiRound = Math.max(0, this.aiRound - 1);
            this.phase = 'static';
            this.staticStep = this.staticQuestions.length - 1;
            return;
        }

        this.allAiQuestions = [];
        this.currentBatch = [];
        this.aiAnswers = {};
        this.aiRound = 0;
        this.phase = 'static';
        this.staticStep = this.staticQuestions.length - 1;
    },

    showEncouragement() {
        const idx = Math.min(this.staticStep, this.encouragements.length - 1);
        this.encouragement = this.encouragements[idx] ?? '';
        setTimeout(() => { this.encouragement = ''; }, 2200);
    },

    prev() {
        if (this.phase === 'ai_questions') {
            this.prevAi();
            return;
        }
        if (this.staticStep > 0) {
            this.staticStep--;
        } else if (this.phase === 'static') {
            this.phase = 'priorities';
        } else if (this.phase === 'priorities') {
            this.phase = 'profile';
        }
    },
}));

Alpine.data('quizInsights', (options) => ({
    sessionId: options.sessionId,
    insightsUrl: options.insightsUrl,
    insights: options.initial ?? null,
    loading: !options.initial || !options.initial?.personality_traits,
    error: null,

    init() {
        if (!this.insights || !this.insights.personality_traits) {
            this.fetchInsights();
        }
    },

    async fetchInsights() {
        this.loading = true;
        this.error = null;

        const url = this.insightsUrl || `/test/result/${this.sessionId}/insights`;

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Не удалось загрузить разбор');
            }

            this.insights = await response.json();
        } catch {
            this.error = 'Персональный разбор временно недоступен — ниже всё равно есть твои результаты.';
        } finally {
            this.loading = false;
        }
    },

    noteFor(professionName) {
        return this.professionDetail(professionName)?.note || null;
    },

    professionDetail(professionName) {
        if (!this.insights?.profession_notes) {
            return null;
        }

        return this.insights.profession_notes.find(
            (note) => note.profession_name === professionName,
        ) || null;
    },

    get isAi() {
        return this.insights?.source === 'ai';
    },
}));

Alpine.start();
