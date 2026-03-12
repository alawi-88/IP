"use client";

import React from "react";

interface StatCard {
  label?: string;
  name?: string;
  value?: string | number;
  change?: string | number;
  trend?: string | number;
  description?: string;
  subtitle?: string;
}

interface StatCardsRendererProps {
  content: any;
}

export default function StatCardsRenderer({ content }: StatCardsRendererProps) {
  let cards: StatCard[] = [];

  if (Array.isArray(content)) {
    cards = content;
  } else if (typeof content === "object" && content !== null) {
    // Extract from known container arrays
    const arr =
      content.metrics || content.cards || content.items || content.data;
    if (Array.isArray(arr)) {
      cards = arr;
    } else {
      cards = Object.entries(content).map(([key, value]) => {
        if (typeof value === "object" && value !== null) {
          return value as StatCard;
        }
        return { name: key, value: value };
      });
    }
  }

  if (cards.length === 0) {
    return (
      <div className="text-gray-500 dark:text-gray-400 text-center py-8">
        No stat cards to display
      </div>
    );
  }

  const parseTrend = (trend: any): { value: number; isPositive: boolean } | null => {
    if (!trend) return null;
    const str = String(trend).trim();
    const match = str.match(/([+-]?\d+(?:\.\d+)?)/);
    if (match) {
      const num = parseFloat(match[1]);
      return { value: Math.abs(num), isPositive: num >= 0 };
    }
    return null;
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {cards.map((card, idx) => {
        const label = card.label || card.name || (card as any).title || (card as any).category || `Metric ${idx + 1}`;
        const value = card.value || "—";
        const description = card.description || card.subtitle || "";
        const trend = parseTrend(card.change || card.trend);

        return (
          <div
            key={idx}
            className="rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow bg-white dark:bg-gray-800"
          >
            <div className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wide">
              {label}
            </div>
            <div className="flex items-baseline gap-3 mb-4">
              <span className="text-4xl font-bold text-gray-900 dark:text-white">
                {value}
              </span>
              {trend && (
                <span
                  className="text-sm font-semibold flex items-center gap-1"
                  style={{
                    color: trend.isPositive
                      ? "var(--dga-success-500, #10B981)"
                      : "var(--dga-error-500, #EF4444)",
                  }}
                >
                  <span className="text-lg">{trend.isPositive ? "↑" : "↓"}</span>
                  {trend.value}%
                </span>
              )}
            </div>
            {description && (
              <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                {description}
              </p>
            )}
          </div>
        );
      })}
    </div>
  );
}
