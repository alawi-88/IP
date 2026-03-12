"use client";

import * as Hi2Icons from "react-icons/hi2";
import type { IconType } from "react-icons";

interface HeroIconProps {
  name: string;
  size?: number;
  className?: string;
  style?: React.CSSProperties;
}

/**
 * Converts a kebab-case heroicon name (e.g. "chart-bar") to react-icons/hi2 component name
 * (e.g. "HiOutlineChartBar"). Falls back to a default icon if not found.
 */
function getIconComponent(name: string): IconType | null {
  if (!name) return null;

  // Convert kebab-case to PascalCase: "chart-bar" → "ChartBar"
  const pascal = name
    .split("-")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join("");

  // Try HiOutline prefix first (outline icons)
  const outlineKey = `HiOutline${pascal}` as keyof typeof Hi2Icons;
  if (Hi2Icons[outlineKey]) {
    return Hi2Icons[outlineKey] as IconType;
  }

  // Try HiMini prefix (solid/mini icons)
  const miniKey = `HiMini${pascal}` as keyof typeof Hi2Icons;
  if (Hi2Icons[miniKey]) {
    return Hi2Icons[miniKey] as IconType;
  }

  // Try Hi prefix (regular)
  const regularKey = `Hi${pascal}` as keyof typeof Hi2Icons;
  if (Hi2Icons[regularKey]) {
    return Hi2Icons[regularKey] as IconType;
  }

  return null;
}

export default function HeroIcon({ name, size = 20, className, style }: HeroIconProps) {
  const Icon = getIconComponent(name);

  if (!Icon) {
    // Fallback: render a simple document icon
    return (
      <Hi2Icons.HiOutlineDocumentText
        size={size}
        className={className}
        style={style}
      />
    );
  }

  return <Icon size={size} className={className} style={style} />;
}
