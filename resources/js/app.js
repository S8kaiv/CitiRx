import Alpine from "alpinejs";

window.Alpine = Alpine;

const CITI_RX_THEMES = [
    {
        id: "theme-standard",
        name: "Standard",
        swatch: "#6D4AFF",
        border: "#4A2FC4",
        requiredLevel: 1,
    },
    {
        id: "theme-forest",
        name: "Forest Grove",
        swatch: "#10B981",
        border: "#047857",
        requiredLevel: 2,
    },
    {
        id: "theme-sakura",
        name: "Sakura Romance",
        swatch: "#F43F5E",
        border: "#BE123C",
        requiredLevel: 4,
    },
    {
        id: "theme-midnight",
        name: "Midnight Slate",
        swatch: "#1E293B",
        border: "#020617",
        requiredLevel: 6,
    },
    {
        id: "theme-ocean",
        name: "Ocean Abyss",
        swatch: "#0EA5E9",
        border: "#0369A1",
        requiredLevel: 8,
    },
    {
        id: "theme-solar",
        name: "Solar Gold",
        swatch: "#F59E0B",
        border: "#B45309",
        requiredLevel: 10,
    },
];

Alpine.data("themeManager", (level = 1) => {
    const parsedLevel = Number.parseInt(level, 10);

    const currentLevel = Number.isFinite(parsedLevel)
        ? Math.max(1, parsedLevel)
        : 1;

    return {
        themes: CITI_RX_THEMES.map((theme) => ({
            ...theme,
        })),

        currentLevel,
        globalTheme: "theme-standard",
        animationsPaused: document.hidden,
        visibilityHandler: null,

        isUnlocked(themeOrId) {
            const theme =
                typeof themeOrId === "string"
                    ? this.themes.find(
                          (candidate) =>
                              candidate.id === themeOrId,
                      )
                    : themeOrId;

            return (
                Boolean(theme) &&
                this.currentLevel >= theme.requiredLevel
            );
        },

        readStoredTheme() {
            try {
                return window.localStorage.getItem(
                    "citiRxTheme",
                );
            } catch {
                return null;
            }
        },

        storeTheme(themeId) {
            try {
                window.localStorage.setItem(
                    "citiRxTheme",
                    themeId,
                );
            } catch {
                /*
                 * Storage can be blocked by browser privacy
                 * settings. The selected theme still works for
                 * the current page.
                 */
            }
        },

        applyTheme(themeId) {
            if (!this.isUnlocked(themeId)) {
                return false;
            }

            this.globalTheme = themeId;
            this.storeTheme(themeId);

            return true;
        },

        init() {
            const storedTheme = this.readStoredTheme();

            if (!this.applyTheme(storedTheme)) {
                this.applyTheme("theme-standard");
            }

            this.visibilityHandler = () => {
                this.animationsPaused = document.hidden;
            };

            document.addEventListener(
                "visibilitychange",
                this.visibilityHandler,
            );
        },

        destroy() {
            if (this.visibilityHandler !== null) {
                document.removeEventListener(
                    "visibilitychange",
                    this.visibilityHandler,
                );
            }
        },
    };
});

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

    const safeCircumference =
        Number.isFinite(numericCircumference)
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
                this.circumference -
                (safeValue / 100) * this.circumference;

            this.$refs.ring?.setAttribute(
                "stroke-dashoffset",
                offset,
            );
        },

        init() {
            this.$nextTick(() => {
                const reducedMotion = window.matchMedia(
                    "(prefers-reduced-motion: reduce)",
                ).matches;

                if (reducedMotion || this.target <= 0) {
                    this.setValue(this.target);

                    return;
                }

                this.setValue(0);

                const startedAt = performance.now();
                const duration = 1200;

                const animate = (now) => {
                    const progress = Math.min(
                        (now - startedAt) / duration,
                        1,
                    );

                    const eased =
                        1 - Math.pow(1 - progress, 3);

                    this.setValue(eased * this.target);

                    if (progress < 1) {
                        this.animationFrame =
                            requestAnimationFrame(
                                animate,
                            );
                    } else {
                        this.setValue(this.target);
                        this.animationFrame = null;
                    }
                };

                this.animationFrame =
                    requestAnimationFrame(animate);
            });
        },

        destroy() {
            if (this.animationFrame !== null) {
                cancelAnimationFrame(
                    this.animationFrame,
                );
            }
        },
    };
});

window.addEventListener("pageshow", (event) => {
    if (
        event.persisted &&
        document.body.dataset.authenticatedPage ===
            "true"
    ) {
        window.location.reload();
    }
});

Alpine.start();
