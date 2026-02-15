"use client";

import { Button, Tooltip } from "antd";
import { useThemeModeStore } from "@/store/theme";
import { BsSun, BsMoon } from "react-icons/bs";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";

export default function ThemeToggle() {
  const t = useTranslations();
  const { mode, toggleMode } = useThemeModeStore();
  const [mounted, setMounted] = useState(false);

  // Prevent hydration mismatch
  useEffect(() => {
    setMounted(true);
  }, []);

  // Handle theme toggle with smooth transition
  const handleToggle = () => {
    const root = document.documentElement;

    // Add transition class for smooth animation
    root.classList.add("theme-transition");

    // Toggle the theme
    toggleMode();

    // Remove transition class after animation completes
    setTimeout(() => {
      root.classList.remove("theme-transition");
    }, 300);
  };

  if (!mounted) {
    return (
      <Button
        type="text"
        className="min-w-auto theme-toggle-btn"
        aria-label="Toggle theme"
      >
        <BsSun size={20} />
      </Button>
    );
  }

  return (
    <Button
      type="text"
      onClick={handleToggle}
      className="min-w-auto theme-toggle-btn"
      aria-label={
        mode === "light" ? "Switch to dark mode" : "Switch to light mode"
      }
    >
      {mode === "light" ? <BsMoon size={20} /> : <BsSun size={20} />}
    </Button>
  );
}
