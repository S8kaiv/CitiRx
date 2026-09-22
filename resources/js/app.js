import Alpine from "alpinejs";

window.Alpine = Alpine;

/**
 * Animated readiness percentage ring.
 *
 * Shared by:
 * - Student dashboard
 * - Readiness report
 */
Alpine.data("readinessRing", (target, circumference) => {
    const numericTarget = Number(target);
    const numericCircumference = Number(circumference);

    const safeTarget = Number.isFinite(numericTarget)
        ? Math.max(0, Math.min(100, numericTarget))
        : 0;

    const safeCircumference = Number.isFinite(numericCircumference)
        ? numericCircumference
        : 0;

    return {
        target: safeTarget,
        circumference: safeCircumference,
        displayScore: safeTarget.toFixed(1),
        animationFrame: null,

        setValue(value) {
            const numericValue = Number(value);

            const safeValue = Number.isFinite(numericValue)
                ? Math.max(0, Math.min(100, numericValue))
                : 0;

            this.displayScore = safeValue.toFixed(1);

            const offset =
                this.circumference - (safeValue / 100) * this.circumference;

            this.$refs.ring?.setAttribute("stroke-dashoffset", offset);
        },

        init() {
            this.$nextTick(() => {
                const reducedMotion = window.matchMedia(
                    "(prefers-reduced-motion: reduce)",
                ).matches;

                /*
                 * Do not run an animation for:
                 * - reduced-motion users
                 * - a zero readiness score
                 */
                if (reducedMotion || this.target <= 0) {
                    this.setValue(this.target);

                    return;
                }

                this.setValue(0);

                const startedAt = performance.now();
                const duration = 1200;

                const animate = (now) => {
                    const progress = Math.min((now - startedAt) / duration, 1);

                    const eased = 1 - Math.pow(1 - progress, 3);

                    this.setValue(eased * this.target);

                    if (progress < 1) {
                        this.animationFrame = requestAnimationFrame(animate);
                    } else {
                        this.setValue(this.target);
                        this.animationFrame = null;
                    }
                };

                this.animationFrame = requestAnimationFrame(animate);
            });
        },

        destroy() {
            if (this.animationFrame !== null) {
                cancelAnimationFrame(this.animationFrame);
            }
        },
    };
});

/**
 * Interactive Sample Quiz for the Auth/Login Page with Question Navigation
 */
Alpine.data("sampleQuiz", (questions = []) => ({
    questions: questions,
    current: {},
    picked: null,
    history: [],

    nextQuestion() {
        if (!this.questions || this.questions.length === 0) return;

        // Push current question state to history before generating a new one
        if (this.current && Object.keys(this.current).length > 0) {
            this.history.push({
                question: this.current,
                picked: this.picked
            });
        }

        this.picked = null;

        let nextIdx;
        do {
            nextIdx = Math.floor(Math.random() * this.questions.length);
        } while (
            this.questions.length > 1 &&
            this.questions[nextIdx] === this.current
        );

        this.current = this.questions[nextIdx];
    },

    prevQuestion() {
        if (this.history.length === 0) return;

        // Restore last question and previous answer selection
        const previousState = this.history.pop();
        this.current = previousState.question;
        this.picked = previousState.picked;
    },

    init() {
        this.nextQuestion();
    },
}));

window.addEventListener("pageshow", (event) => {
    if (event.persisted && document.body.dataset.authenticatedPage === "true") {
        window.location.reload();
    }
});

Alpine.start();