import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import("tailwindcss").Config} */
export default {
    future: {
        hoverOnlyWhenSupported: true,
    },

    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
    ],

    theme: {
        extend: {
            colors: {
                canvas:
                    "rgb(var(--color-canvas) / <alpha-value>)",

                surface: {
                    DEFAULT:
                        "rgb(var(--color-surface) / <alpha-value>)",
                    2: "rgb(var(--color-surface-2) / <alpha-value>)",
                },

                ink: {
                    DEFAULT:
                        "rgb(var(--color-ink) / <alpha-value>)",
                    muted:
                        "rgb(var(--color-ink-muted) / <alpha-value>)",
                },

                line: {
                    DEFAULT:
                        "rgb(var(--color-line) / <alpha-value>)",
                    strong:
                        "rgb(var(--color-line-strong) / <alpha-value>)",
                },

                primary: {
                    DEFAULT:
                        "rgb(var(--color-primary-default) / <alpha-value>)",
                    strong:
                        "rgb(var(--color-primary-strong) / <alpha-value>)",
                    lip:
                        "rgb(var(--color-primary-lip) / <alpha-value>)",
                    tint:
                        "rgb(var(--color-primary-tint) / <alpha-value>)",
                    ink:
                        "rgb(var(--color-primary-ink) / <alpha-value>)",
                },

                success: {
                    strong:
                        "rgb(var(--color-success-strong) / <alpha-value>)",
                    tint:
                        "rgb(var(--color-success-tint) / <alpha-value>)",
                    ink:
                        "rgb(var(--color-success-ink) / <alpha-value>)",
                },

                danger: {
                    strong:
                        "rgb(var(--color-danger-strong) / <alpha-value>)",
                    tint:
                        "rgb(var(--color-danger-tint) / <alpha-value>)",
                    ink:
                        "rgb(var(--color-danger-ink) / <alpha-value>)",
                },

                warning: {
                    strong:
                        "rgb(var(--color-warning-strong) / <alpha-value>)",
                    tint:
                        "rgb(var(--color-warning-tint) / <alpha-value>)",
                    ink:
                        "rgb(var(--color-warning-ink) / <alpha-value>)",
                },

                info: {
                    strong:
                        "rgb(var(--color-info-strong) / <alpha-value>)",
                    tint:
                        "rgb(var(--color-info-tint) / <alpha-value>)",
                    ink:
                        "rgb(var(--color-info-ink) / <alpha-value>)",
                },

                /*
                 * Compatibility aliases. Keep these until every
                 * existing view has migrated to semantic tokens.
                 */
                gold: {
                    DEFAULT: "#F5A623",
                    tint: "#FDF1DC",
                    ink: "#7A4B06",
                },

                clinical: {
                    DEFAULT: "#0EA5A4",
                    tint: "#E4FBF6",
                    ink: "#0B5C52",
                },

                strong: {
                    DEFAULT: "#22C55E",
                    tint: "#EAF9EE",
                    ink: "#146C2E",
                },

                weak: {
                    DEFAULT: "#F0524F",
                    tint: "#FDECEC",
                    ink: "#A32D2D",
                },

                muted: {
                    DEFAULT: "#7A7A85",
                    ink: "#6E6E78",
                    line: "#E6E6EC",
                },
            },

            fontFamily: {
                display: [
                    "Figtree",
                    ...defaultTheme.fontFamily.sans,
                ],
                sans: [
                    "Figtree",
                    ...defaultTheme.fontFamily.sans,
                ],
            },

            borderRadius: {
                card: "var(--radius-card)",
                control: "var(--radius-control)",
            },

            minHeight: {
                dvh: "100dvh",
            },

            boxShadow: {
                soft:
                    "0 8px 24px rgb(15 23 42 / 0.08)",
                raised:
                    "0 14px 36px rgb(15 23 42 / 0.1)",
            },

            transitionTimingFunction: {
                "ui-out":
                    "cubic-bezier(0.23, 1, 0.32, 1)",
                drawer:
                    "cubic-bezier(0.32, 0.72, 0, 1)",
            },

            screens: {
                xs: "480px",
            },

            borderWidth: {
                3: "3px",
            },
        },
    },

    plugins: [forms],
};
