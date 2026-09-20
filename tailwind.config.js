import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: "#6D4AFF",
                    lip: "#4A2FC4",
                    tint: "#F1EDFF",
                },
                gold: { DEFAULT: "#F5A623", tint: "#FDF1DC", ink: "#7A4B06" },
                clinical: {
                    DEFAULT: "#0EA5A4",
                    tint: "#E4FBF6",
                    ink: "#0B5C52",
                },
                strong: { DEFAULT: "#22C55E", tint: "#EAF9EE", ink: "#146C2E" },
                weak: { DEFAULT: "#F0524F", tint: "#FDECEC", ink: "#A32D2D" },
                muted: { DEFAULT: "#7A7A85", ink: "#6E6E78", line: "#E6E6EC" },
                canvas: "#F8FAFC",
            },
            fontFamily: {
                display: ["Figtree", ...defaultTheme.fontFamily.sans],
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: "1rem",
                hero: "1.25rem",
            },
            screens: {
                xs: "480px",
            },

            borderWidth: {
                3: "3px",
            },

            boxShadow: {
                "2xs": "0 1px rgb(0 0 0 / 0.05)",
                xs: "0 1px 2px 0 rgb(0 0 0 / 0.05)",
            },
        },
    },

    plugins: [forms],
};
