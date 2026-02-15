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
