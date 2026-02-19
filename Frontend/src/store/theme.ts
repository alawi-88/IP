// utils/theme.ts
import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import { Theme } from "@/lib/interfaces";

export const defaultTheme: Theme = {
  logo: "/logo.svg",
  white_logo: "/logo-white.svg",
  favicon: "",
  primary_color: "#6e62e5",
  secondary_color: "#08bcb8",
  font: "IBM Plex Sans Arabic",
  theme_status: undefined,
  mode: "light",
};

export function getTheme(): Theme {
  try {
    const theme = localStorage.getItem("theme") || "";
    return JSON.parse(theme) as Theme;
  } catch (error) {
    console.error("Error getting theme from store", error);
    return defaultTheme;
  }
}

// Color utility functions for DGA design system variables
function hexToRgb(hex: string): string {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  if (!result) return "37, 147, 95";
  return `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}`;
}

function adjustColor(hex: string, percent: number): string {
  const num = parseInt(hex.replace("#", ""), 16);
  const amt = Math.round(2.55 * percent);
  const R = Math.min(255, Math.max(0, (num >> 16) + amt));
  const G = Math.min(255, Math.max(0, ((num >> 8) & 0x00ff) + amt));
  const B = Math.min(255, Math.max(0, (num & 0x0000ff) + amt));
  return `#${(0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1)}`;
}

/**
 * Applies theme to root element, setting BOTH Tailwind CSS variables (--primary-color, --secondary-color)
 * AND DGA design system variables (--dga-primary-500, etc.) to ensure unified branding.
 */
function applyThemeToRoot(theme: Theme, el: string = "html") {
  if (typeof window === "undefined") return;
  const root = document.querySelector(el) as HTMLElement;
  if (!root) return;

  const escapeKeys = ["is_published", "theme_status", "mode"];

  Object.entries(theme).forEach(([key, value]) => {
    if (value && !escapeKeys.includes(key as keyof Theme)) {
      const kebabKey = key
        .replace(/_/g, "-")
        .replace(/([a-z])([A-Z])/g, "$1-$2")
        .toLowerCase();
      root.style.setProperty(`--${kebabKey}`, String(value));
    }
  });

  // Sync DGA design system variables with the theme primary/secondary colors
  const primary = theme.primary_color;
  const secondary = theme.secondary_color;
  if (primary) {
    root.style.setProperty("--dga-primary-500", primary);
    root.style.setProperty("--dga-primary-400", adjustColor(primary, 20));
    root.style.setProperty("--dga-primary-300", adjustColor(primary, 40));
    root.style.setProperty("--dga-primary-200", adjustColor(primary, 60));
    root.style.setProperty("--dga-primary-100", adjustColor(primary, 80));
    root.style.setProperty("--dga-primary-50", adjustColor(primary, 90));
    root.style.setProperty("--dga-primary-700", adjustColor(primary, -20));
    root.style.setProperty("--dga-primary-800", adjustColor(primary, -40));
    root.style.setProperty("--dga-primary-900", adjustColor(primary, -60));
    root.style.setProperty("--dga-primary-rgb", hexToRgb(primary));
  }
  if (secondary) {
    root.style.setProperty("--dga-primary-600", secondary);
  }
}

type ThemeModeState = {
  mode: "light" | "dark";
  setMode: (mode: "light" | "dark") => void;
  toggleMode: () => void;
};

// Separate store for theme mode with persistence
export const useThemeModeStore = create(
  persist<ThemeModeState>(
    (set, get) => ({
      mode: "light",
      setMode: (mode) => {
        set({ mode });
        // Apply dark class to HTML element
        if (typeof window !== "undefined") {
          const root = document.documentElement;
          if (mode === "dark") {
            root.classList.add("dark");
          } else {
            root.classList.remove("dark");
          }
        }
      },
      toggleMode: () => {
        const currentMode = get().mode;
        const newMode = currentMode === "light" ? "dark" : "light";
        get().setMode(newMode);
      },
    }),
    {
      name: "theme-mode-storage",
      storage: createJSONStorage(() => localStorage),
    }
  )
);

type ThemeState = {
  theme: Theme | null;
  setTheme: (theme: any) => void;
};

export const useThemeStore = create<ThemeState>((set) => ({
  theme: null,
  setTheme: (theme) => {
    applyThemeToRoot(theme);
    set({
      theme: {
        ...theme,
        theme_status: "stored",
        mode: useThemeModeStore.getState().mode,
      },
    });
  },
}));
