import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('quizWizard', (questions) => ({
    questions,
    phase: 'profile',
    profile: { name: '', status: 'exploring' },
    priorities: [],
    currentStep: 0,
    answers: {},
    encouragement: '',

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

    get visibleQuestions() {
        return this.questions.filter((q) => {
            if (!q.target_statuses || !q.target_statuses.length) {
                return true;
            }
            return q.target_statuses.includes(this.profile.status);
        });
    },

    get currentQuestion() {
        return this.visibleQuestions[this.currentStep] ?? null;
    },

    get profileComplete() {
        return !!this.profile.status;
    },

    get prioritiesComplete() {
        return this.priorities.length >= 1;
    },

    get questionProgress() {
        if (!this.visibleQuestions.length) return 0;
        return Math.round(((this.currentStep + 1) / this.visibleQuestions.length) * 100);
    },

    get overallProgress() {
        if (this.phase === 'profile') return 5;
        if (this.phase === 'priorities') return 15;
        return Math.round(15 + (this.questionProgress * 0.85));
    },

    get currentAnswered() {
        const q = this.currentQuestion;
        return q ? this.answers[q.id] !== undefined && this.answers[q.id] !== null : false;
    },

    get allAnswered() {
        return this.visibleQuestions.every((q) => this.answers[q.id] !== undefined && this.answers[q.id] !== null);
    },

    get phaseLabel() {
        if (this.phase === 'profile') return 'Шаг 1 — о тебе';
        if (this.phase === 'priorities') return 'Шаг 2 — что важно';
        return `Вопрос ${this.currentStep + 1} из ${this.visibleQuestions.length}`;
    },

    startPriorities() {
        if (!this.profileComplete) return;
        this.phase = 'priorities';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    startQuestions() {
        if (!this.prioritiesComplete) return;
        this.phase = 'questions';
        this.currentStep = 0;
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

    selectAnswer(optionId) {
        const qid = this.currentQuestion.id;
        this.answers = { ...this.answers, [qid]: optionId };
    },

    isSelected(optionId) {
        const q = this.currentQuestion;
        if (!q) return false;
        return Number(this.answers[q.id]) === Number(optionId);
    },

    showEncouragement() {
        const idx = Math.min(this.currentStep, this.encouragements.length - 1);
        this.encouragement = this.encouragements[idx] ?? '';
        setTimeout(() => { this.encouragement = ''; }, 2200);
    },

    next() {
        if (!this.currentAnswered) return;
        this.showEncouragement();
        if (this.currentStep < this.visibleQuestions.length - 1) {
            this.currentStep++;
        }
    },

    prev() {
        if (this.currentStep > 0) {
            this.currentStep--;
        } else if (this.phase === 'questions') {
            this.phase = 'priorities';
        } else if (this.phase === 'priorities') {
            this.phase = 'profile';
        }
    },
}));

Alpine.start();
