import type { Config } from "tailwindcss";

export default {
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/hooks/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/providers.tsx"
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        background: "var(--background-color)",
        foreground: "var(--text-color)",
        primary: "var(--primary-color)",
        secondary: "var(--secondary-color)",
        success: "var(--success-color)",
        warning: "var(--warning-color)",
        danger: "var(--danger-color)",
        accent: "var(--accent-color)",
      },
      gridTemplateColumns: {
        "auto-fit-300": "repeat(auto-fit, minmax(300px, 300px))",
        "auto-fit-330": "repeat(auto-fit, minmax(300px, 332px))",
        "auto-fit-348": "repeat(auto-fit, minmax(348px, 348px))",
      },
    },
  },
  plugins: [],
} satisfies Config;
