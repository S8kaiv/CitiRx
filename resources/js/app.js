import Alpine from "alpinejs";
import { initializeAssessmentGuard } from './assessment-guard';

window.Alpine = Alpine;

const COMFORT_STORAGE_KEY = "citiRxComfort";
const THEME_STORAGE_KEY = "citiRxTheme";

const validComfortModes = [
    "light",
    "dark",
    "auto",
];

const readStoredValue = (key) => {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
};

const storeValue = (key, value) => {
    try {
        window.localStorage.setItem(key, value);
    } catch {
        // Storage may be unavailable in private mode.
    }
};

const normaliseComfortMode = (value) =>
    validComfortModes.includes(value)
        ? value
        : "auto";

const resolveComfortMode = (mode) => {
    if (mode === "dark") {
        return "dark";
    }

    if (mode === "light") {
        return "light";
    }

    return window.matchMedia(
        "(prefers-color-scheme: dark)",
    ).matches
        ? "dark"
        : "light";
};

const brandFavicon = document.querySelector('[data-brand-icon-root]');

const updateBrandIcons = (themeId, comfort) => {
    if (!brandFavicon) return;

    const theme = themeId.slice('theme-'.length);
    const version = new URL(brandFavicon.href).search;
    const iconUrl = `${brandFavicon.dataset.brandIconRoot}/${theme}-${comfort}.png${version}`;
    brandFavicon.href = iconUrl;

    for (const logo of document.querySelectorAll('[data-brand-logo]')) {
        logo.src = iconUrl;
    }
};

const applyDocumentComfort = (mode, themeId = 'theme-standard') => {
    const resolved = resolveComfortMode(mode);

    document.documentElement.dataset.comfort =
        resolved;

    const meta = document.querySelector(
        'meta[name="theme-color"]',
    );

    meta?.setAttribute(
        "content",
        resolved === "dark"
            ? "#0F172A"
            : "#F8FAFC",
    );

    updateBrandIcons(themeId, resolved);

    return resolved;
};

const initialComfortMode = normaliseComfortMode(
    readStoredValue(COMFORT_STORAGE_KEY),
);

applyDocumentComfort(initialComfortMode);

Alpine.data(
    "themeManager",
    (
        level = 1,
        surface,
        themes,
        requestedPreview,
    ) => {
        const parsedLevel = Number.parseInt(
            level,
            10,
        );

        const currentLevel = Number.isFinite(
            parsedLevel,
        )
            ? Math.max(1, parsedLevel)
            : 1;

        return {
            themes,
            surface,
            currentLevel,
            globalTheme: "theme-standard",
            equippedTheme: "theme-standard",
            previewThemeId: null,
            previewBackdrop: null,

            get previewThemeName() {
                return this.themes.find((theme) => theme.id === this.previewThemeId)?.name ?? "";
            },

            comfortMode: initialComfortMode,
            resolvedComfort:
                applyDocumentComfort(
                    initialComfortMode,
                ),

            animationsPaused: document.hidden,
            visibilityHandler: null,
            comfortQuery: null,
            comfortHandler: null,

            isUnlocked(themeOrId) {
                const theme =
                    typeof themeOrId === "string"
                        ? this.themes.find(
                              (candidate) =>
                                  candidate.id ===
                                  themeOrId,
                          )
                        : themeOrId;

                if (!theme) {
                    return false;
                }

                if (this.surface !== "student") {
                    return (
                        theme.id ===
                        "theme-standard"
                    );
                }

                return (
                    this.currentLevel >=
                    theme.requiredLevel
                );
            },

            applyTheme(themeId) {
                if (!this.isUnlocked(themeId)) {
                    return false;
                }

                this.equippedTheme = themeId;
                this.globalTheme = themeId;
                updateBrandIcons(themeId, this.resolvedComfort);

                if (this.surface === "student") {
                    storeValue(THEME_STORAGE_KEY, themeId);
                }

                return true;
            },

            showPreviewPalette(themeId, animate) {
                this.previewBackdrop?.remove();
                this.previewBackdrop = null;

                const environment = this.$refs.themeEnvironment;
                if (
                    animate
                    && environment?.animate
                    && themeId !== this.globalTheme
                    && window.matchMedia("(prefers-reduced-motion: no-preference)").matches
                ) {
                    const backdrop = document.createElement("div");
                    backdrop.className = "theme-preview-backdrop";
                    backdrop.style.background = window.getComputedStyle(environment).background;
                    environment.append(backdrop);
                    this.previewBackdrop = backdrop;
                    const easing = window.getComputedStyle(environment).getPropertyValue("--ease-out").trim() || "linear";
                    const fade = backdrop.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 200, easing });
                    fade.onfinish = () => {
                        backdrop.remove();
                        if (this.previewBackdrop === backdrop) this.previewBackdrop = null;
                    };
                }

                this.globalTheme = themeId;
                updateBrandIcons(themeId, this.resolvedComfort);
            },

            finishThemePreview(equip, animate = false) {
                if (!this.previewThemeId) return;

                if (equip) {
                    this.applyTheme(this.previewThemeId);
                } else {
                    this.showPreviewPalette(this.equippedTheme, animate);
                }

                this.previewThemeId = null;
                const url = new URL(window.location.href);
                url.searchParams.delete("preview-theme");
                if (url.hash === "#theme-preview-motion") url.hash = "";
                window.history.replaceState(window.history.state, "", url);
                this.$nextTick(() => this.$refs.themeJourney?.focus());
            },

            setComfort(mode) {
                this.comfortMode =
                    normaliseComfortMode(mode);

                storeValue(
                    COMFORT_STORAGE_KEY,
                    this.comfortMode,
                );

                this.resolvedComfort =
                    applyDocumentComfort(
                        this.comfortMode,
                        this.globalTheme,
                    );
            },

            init() {
                const storedTheme = readStoredValue(
                    THEME_STORAGE_KEY,
                );

                if (this.isUnlocked(storedTheme)) {
                    this.equippedTheme = storedTheme;
                    this.globalTheme = storedTheme;
                }

                updateBrandIcons(this.globalTheme, this.resolvedComfort);

                if (this.surface === "student" && typeof requestedPreview === "string" && this.isUnlocked(requestedPreview)) {
                    this.previewThemeId = requestedPreview;
                    const animate = window.location.hash === "#theme-preview-motion";
                    this.$nextTick(() => this.showPreviewPalette(requestedPreview, animate));
                }

                this.visibilityHandler = () => {
                    this.animationsPaused =
                        document.hidden;
                };

                document.addEventListener(
                    "visibilitychange",
                    this.visibilityHandler,
                );

                this.comfortQuery =
                    window.matchMedia(
                        "(prefers-color-scheme: dark)",
                    );

                this.comfortHandler = () => {
                    if (
                        this.comfortMode === "auto"
                    ) {
                        this.resolvedComfort =
                            applyDocumentComfort(
                                "auto",
                                this.globalTheme,
                            );
                    }
                };

                this.comfortQuery.addEventListener(
                    "change",
                    this.comfortHandler,
                );
            },

            destroy() {
                if (this.visibilityHandler) {
                    document.removeEventListener(
                        "visibilitychange",
                        this.visibilityHandler,
                    );
                }

                if (
                    this.comfortQuery
                    && this.comfortHandler
                ) {
                    this.comfortQuery
                        .removeEventListener(
                            "change",
                            this.comfortHandler,
                        );
                }
            },
        };
    },
);

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

// Public pages share the existing comfort preference without changing cosmetic themes.
Alpine.data('publicComfort', () => ({
    comfortMode: normaliseComfortMode(readStoredValue(COMFORT_STORAGE_KEY)),
    resolvedComfort: document.documentElement.dataset.comfort || 'light',
    comfortQuery: null,
    comfortHandler: null,
    storageHandler: null,
    syncComfort() {
        this.resolvedComfort = applyDocumentComfort(this.comfortMode);
        document.documentElement.style.colorScheme = this.resolvedComfort;
    },
    toggleComfort() {
        this.comfortMode = this.resolvedComfort === 'dark' ? 'light' : 'dark';
        storeValue(COMFORT_STORAGE_KEY, this.comfortMode);
        this.syncComfort();
    },
    init() {
        this.syncComfort();
        this.comfortQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this.comfortHandler = () => {
            if (this.comfortMode === 'auto') this.syncComfort();
        };
        this.storageHandler = (event) => {
            if (event.key === COMFORT_STORAGE_KEY || event.key === null) {
                this.comfortMode = normaliseComfortMode(readStoredValue(COMFORT_STORAGE_KEY));
                this.syncComfort();
            }
        };
        this.comfortQuery.addEventListener('change', this.comfortHandler);
        window.addEventListener('storage', this.storageHandler);
    },
    destroy() {
        this.comfortQuery?.removeEventListener('change', this.comfortHandler);
        window.removeEventListener('storage', this.storageHandler);
    },
}));

Alpine.start();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAssessmentGuard, { once: true });
} else {
    initializeAssessmentGuard();
}
// One confirmation per form submission; validation still runs before opening it.
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('workflow-confirm');
    if (!dialog) return;
    let pending = null;
    document.querySelectorAll('form[data-bulk]').forEach(form => {
        const rows = () => [...form.querySelectorAll('[data-row-select]:not(:disabled)')];
        const update = () => {
            const checked = rows().filter(row => row.checked);
            const target = form.querySelector('[data-cohort-target]')?.value;
            const moves = checked.filter(row => row.dataset.cohort !== target).length;
            const counter = form.querySelector('[data-selected-count]');
            if (counter) counter.textContent = `${checked.length} selected` + (target ? ` · ${moves} will move to the chosen cohort` : '');
            const all = form.querySelector('[data-select-all]');
            if (all) { all.checked = rows().length > 0 && checked.length === rows().length; all.indeterminate = checked.length > 0 && checked.length < rows().length; }
            form.querySelectorAll('[data-bulk-submit]').forEach(button => { button.disabled = checked.length === 0; });
        };
        form.addEventListener('change', event => {
            if (event.target.matches('[data-select-all]')) rows().forEach(row => { row.checked = event.target.checked; });
            update();
        });
        update();
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmed === 'yes') { delete form.dataset.confirmed; return; }
        event.preventDefault();
        if (!form.reportValidity()) return;
        pending = { form, submitter: event.submitter };
        const count = form.querySelectorAll('input[data-row-select]:checked:not(:disabled)').length;
        document.getElementById('workflow-confirm-message').textContent = form.dataset.confirm + (form.hasAttribute('data-bulk') ? `\n${form.querySelector('[data-selected-count]')?.textContent || 'Selected records: ' + count}` : '');
        dialog.showModal();
    });
    document.getElementById('workflow-confirm-cancel').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { pending?.submitter?.focus(); pending = null; });
    document.getElementById('workflow-confirm-accept').addEventListener('click', () => {
        if (!pending) return;
        const { form, submitter } = pending;
        form.dataset.confirmed = 'yes';
        dialog.close();
        form.requestSubmit(submitter || undefined);
    });
});
