"use client";
import React from "react";
import HeroIcon from "@/components/venture/HeroIcon";

interface GridQuadrant {
  title: string;
  items: string[];
  color: string;
  bgColor: string;
  borderColor: string;
  textColor: string;
  icon: string; // HeroIcon name (outline)
}

interface SwotGridRendererProps {
  content: any;
}

/**
 * SwotGridRenderer
 * Renders SWOT, PESTEL, or any category-based colored grid.
 * Uses HeroIcon outline icons for consistent monospace design.
 */
export default function SwotGridRenderer({ content }: SwotGridRendererProps) {
  // SWOT color definitions with HeroIcon names
  const swotDefs: Record<string, Omit<GridQuadrant, "items">> = {
    strengths: {
      title: "Strengths",
      color: "var(--dga-success-600, #059669)",
      bgColor: "var(--dga-success-50, #F0FDF4)",
      borderColor: "var(--dga-success-200, #BBFADC)",
      textColor: "var(--dga-success-900, #064E3B)",
      icon: "shield-check",
    },
    weaknesses: {
      title: "Weaknesses",
      color: "var(--dga-warning-600, #D97706)",
      bgColor: "var(--dga-warning-50, #FFFBEB)",
      borderColor: "var(--dga-warning-200, #FED7AA)",
      textColor: "var(--dga-warning-900, #78350F)",
      icon: "exclamation-triangle",
    },
    opportunities: {
      title: "Opportunities",
      color: "var(--dga-primary-600, #2563EB)",
      bgColor: "var(--dga-primary-50, #EFF6FF)",
      borderColor: "var(--dga-primary-200, #BFDBFE)",
      textColor: "var(--dga-primary-900, #1E3A8A)",
      icon: "rocket-launch",
    },
    threats: {
      title: "Threats",
      color: "var(--dga-error-600, #DC2626)",
      bgColor: "var(--dga-error-50, #FEF2F2)",
      borderColor: "var(--dga-error-200, #FECACA)",
      textColor: "var(--dga-error-900, #7F1D1D)",
      icon: "fire",
    },
  };

  // PESTEL color definitions with HeroIcon names
  const pestelDefs: Record<string, Omit<GridQuadrant, "items">> = {
    political: {
      title: "Political",
      color: "#7C3AED",
      bgColor: "#F5F3FF",
      borderColor: "#C4B5FD",
      textColor: "#4C1D95",
      icon: "building-library",
    },
    economic: {
      title: "Economic",
      color: "#059669",
      bgColor: "#F0FDF4",
      borderColor: "#A7F3D0",
      textColor: "#064E3B",
      icon: "currency-dollar",
    },
    social: {
      title: "Social",
      color: "#2563EB",
      bgColor: "#EFF6FF",
      borderColor: "#BFDBFE",
      textColor: "#1E3A8A",
      icon: "user-group",
    },
    technological: {
      title: "Technological",
      color: "#0891B2",
      bgColor: "#ECFEFF",
      borderColor: "#A5F3FC",
      textColor: "#155E75",
      icon: "cpu-chip",
    },
    environmental: {
      title: "Environmental",
      color: "#16A34A",
      bgColor: "#F0FDF4",
      borderColor: "#BBF7D0",
      textColor: "#14532D",
      icon: "globe-alt",
    },
    legal: {
      title: "Legal",
      color: "#DC2626",
      bgColor: "#FEF2F2",
      borderColor: "#FECACA",
      textColor: "#7F1D1D",
      icon: "scale",
    },
  };

  // Alternative key mappings
  const keyAliases: Record<string, string> = {
    strength: "strengths",
    weakness: "weaknesses",
    opportunity: "opportunities",
    threat: "threats",
    technology: "technological",
    environment: "environmental",
    politics: "political",
    economics: "economic",
    sociology: "social",
    law: "legal",
  };

  // Combine all definitions
  const allDefs = { ...swotDefs, ...pestelDefs };

  // Parse quadrants from content
  let quadrants: GridQuadrant[] = [];

  if (typeof content === "object" && content !== null && !Array.isArray(content)) {
    // Define desired order for PESTEL
    const pestelOrder = ["political", "economic", "social", "technological", "environmental", "legal"];
    const swotOrder = ["strengths", "weaknesses", "opportunities", "threats"];

    // Detect if this is PESTEL or SWOT based on keys
    const contentKeys = Object.keys(content).map(k => k.toLowerCase().replace(/\s+/g, "_"));
    const isPestel = contentKeys.some(k => pestelOrder.includes(k) || pestelOrder.includes(keyAliases[k] || ""));
    const isSwot = contentKeys.some(k => swotOrder.includes(k) || swotOrder.includes(keyAliases[k] || ""));

    // Choose iteration order
    const orderToUse = isPestel ? pestelOrder : isSwot ? swotOrder : Object.keys(content);

    // First pass: try ordered keys
    for (const orderedKey of orderToUse) {
      // Find matching content key (case insensitive)
      const matchingEntry = Object.entries(content).find(([k]) => {
        const normalized = k.toLowerCase().replace(/\s+/g, "_");
        return normalized === orderedKey || keyAliases[normalized] === orderedKey;
      });

      if (!matchingEntry) continue;
      const [, value] = matchingEntry;
      const def = allDefs[orderedKey];
      if (!def) continue;

      const quadrant: GridQuadrant = { ...def, items: [] };

      // Extract items from various formats
      if (Array.isArray(value)) {
        quadrant.items = value.map((item: any) => String(item).trim()).filter(Boolean);
      } else if (typeof value === "string") {
        quadrant.items = value.split(/[\n,;]/).map((item: string) => item.trim()).filter(Boolean);
      } else if (typeof value === "object" && value !== null) {
        // Nested object: {title: "...", factors: [...]} or {title: "...", items: [...]}
        const nested = value as Record<string, any>;
        if (nested.title && typeof nested.title === "string") {
          quadrant.title = nested.title;
        }
        const itemsArray = nested.factors || nested.items || nested.points || nested.details;
        if (Array.isArray(itemsArray)) {
          quadrant.items = itemsArray.map((item: any) => String(item).trim()).filter(Boolean);
        } else {
          // Try extracting all string values
          quadrant.items = Object.values(nested)
            .filter((v) => typeof v === "string" && v !== nested.title)
            .map((v) => String(v).trim())
            .filter(Boolean);
          // If still empty, try nested arrays
          if (quadrant.items.length === 0) {
            Object.values(nested).forEach((v) => {
              if (Array.isArray(v)) {
                quadrant.items.push(...v.map((item: any) => String(item).trim()).filter(Boolean));
              }
            });
          }
        }
      }

      if (quadrant.items.length > 0) {
        quadrants.push(quadrant);
      }
    }

    // Second pass: pick up any remaining unmatched keys
    if (quadrants.length === 0) {
      const fallbackColors = [
        { color: "#7C3AED", bgColor: "#F5F3FF", borderColor: "#C4B5FD", textColor: "#4C1D95", icon: "squares-2x2" },
        { color: "#059669", bgColor: "#F0FDF4", borderColor: "#A7F3D0", textColor: "#064E3B", icon: "chart-bar" },
        { color: "#2563EB", bgColor: "#EFF6FF", borderColor: "#BFDBFE", textColor: "#1E3A8A", icon: "light-bulb" },
        { color: "#DC2626", bgColor: "#FEF2F2", borderColor: "#FECACA", textColor: "#7F1D1D", icon: "bolt" },
        { color: "#0891B2", bgColor: "#ECFEFF", borderColor: "#A5F3FC", textColor: "#155E75", icon: "cog-6-tooth" },
        { color: "#D97706", bgColor: "#FFFBEB", borderColor: "#FED7AA", textColor: "#78350F", icon: "star" },
      ];
      Object.entries(content).forEach(([key, value], idx) => {
        const colorSet = fallbackColors[idx % fallbackColors.length];
        let items: string[] = [];
        if (Array.isArray(value)) {
          items = value.map((item: any) => String(item).trim()).filter(Boolean);
        } else if (typeof value === "object" && value !== null) {
          const nested = value as Record<string, any>;
          const itemsArray = nested.factors || nested.items || nested.points || nested.details;
          if (Array.isArray(itemsArray)) {
            items = itemsArray.map((item: any) => String(item).trim()).filter(Boolean);
          }
        } else if (typeof value === "string") {
          items = value.split(/[\n,;]/).map((item: string) => item.trim()).filter(Boolean);
        }
        if (items.length > 0) {
          quadrants.push({
            title: key.replace(/[_-]/g, " ").replace(/\b\w/g, (l: string) => l.toUpperCase()),
            items,
            ...colorSet,
          });
        }
      });
    }
  }

  if (quadrants.length === 0) {
    return (
      <div className="text-gray-500 text-center py-8">
        No grid data to display
      </div>
    );
  }

  // Determine grid columns based on count
  const gridCols =
    quadrants.length <= 2
      ? "grid-cols-1 md:grid-cols-2"
      : quadrants.length === 3
      ? "grid-cols-1 md:grid-cols-3"
      : quadrants.length <= 4
      ? "grid-cols-1 md:grid-cols-2"
      : "grid-cols-1 md:grid-cols-2 lg:grid-cols-3";

  return (
    <div className={`grid gap-4 ${gridCols}`}>
      {quadrants.map((quadrant, idx) => (
        <div
          key={idx}
          className="rounded-lg border-2 p-5 overflow-hidden transition-all hover:shadow-md"
          style={{
            backgroundColor: quadrant.bgColor,
            borderColor: quadrant.borderColor,
          }}
        >
          {/* Header */}
          <div
            className="flex items-center gap-2 mb-4 pb-3 border-b-2"
            style={{ borderColor: quadrant.color }}
          >
            {quadrant.icon && (
              <HeroIcon
                name={quadrant.icon}
                size={18}
                style={{ color: quadrant.color, flexShrink: 0 }}
              />
            )}
            <div
              className="w-3 h-3 rounded-full flex-shrink-0"
              style={{ backgroundColor: quadrant.color }}
            />
            <h3
              className="font-bold text-base"
              style={{ color: quadrant.color }}
            >
              {quadrant.title}
            </h3>
          </div>

          {/* Items */}
          <div className="space-y-2">
            {quadrant.items.length > 0 ? (
              quadrant.items.map((item, itemIdx) => (
                <div
                  key={itemIdx}
                  className="flex items-start gap-2.5 text-sm"
                  style={{ color: quadrant.textColor }}
                >
                  <HeroIcon
                    name="check"
                    size={16}
                    className="flex-shrink-0 mt-0.5"
                    style={{ color: quadrant.color }}
                  />
                  <span className="leading-relaxed">{item}</span>
                </div>
              ))
            ) : (
              <p className="text-sm italic opacity-60" style={{ color: quadrant.textColor }}>
                No items listed
              </p>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}
