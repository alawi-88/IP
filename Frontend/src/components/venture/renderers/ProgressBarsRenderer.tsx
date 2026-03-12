"use client";

import React from "react";

interface ProgressItem {
  label?: string;
  name?: string;
  title?: string;
  category?: string;
  value?: number;
  score?: number;
  amount?: string | number;
  percentage?: number;
  weight?: number;
  color?: string;
  description?: string;
}

interface ProgressBarsRendererProps {
  content: any;
}

export default function ProgressBarsRenderer({ content }: ProgressBarsRendererProps) {
  // Handle content that has an overall score + breakdown (viability-score-like data)
  const hasOverallScore = content?.score !== undefined || content?.overall !== undefined;

  const items: ProgressItem[] = Array.isArray(content)
    ? content
    : content?.items || content?.costs || content?.breakdown || content?.bars || content?.stages || [];

  if (!items.length && !hasOverallScore) {
    return <div className="text-gray-500 dark:text-gray-400 text-sm italic">No data available.</div>;
  }

  const maxVal = Math.max(
    ...items.map((item) => {
      const v = item.percentage ?? item.score ?? item.value ?? 0;
      return typeof v === "number" ? v : 0;
    }),
    1
  );

  const defaultColors = [
    "var(--dga-primary-500, #3B82F6)",
    "var(--dga-primary-400, #60A5FA)",
    "#10B981",
    "#F59E0B",
    "#EF4444",
    "#8B5CF6",
    "#EC4899",
    "#14B8A6",
  ];

  return (
    <div className="space-y-4">
      {/* Show overall score if present */}
      {hasOverallScore && (
        <div className="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg mb-2">
          <div className="text-3xl font-bold" style={{ color: "var(--dga-primary-500, #3B82F6)" }}>
            {content.score ?? content.overall}
          </div>
          <div>
            {content.rating && (
              <p className="text-sm font-semibold text-gray-800 dark:text-gray-200">{content.rating}</p>
            )}
            {content.summary && (
              <p className="text-xs text-gray-500 dark:text-gray-400">{content.summary}</p>
            )}
          </div>
        </div>
      )}

      {items.map((item: any, idx: number) => {
        const pct = item.percentage ?? item.score ?? item.value ?? 0;
        const barWidth = maxVal > 0 ? Math.round((pct / maxVal) * 100) : 0;
        const barColor = item.color || defaultColors[idx % defaultColors.length];
        const itemLabel = item.label || item.name || item.title || item.category || `Item ${idx + 1}`;

        return (
          <div key={idx}>
            <div className="flex items-center justify-between mb-1">
              <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {itemLabel}
              </span>
              <span className="text-sm font-semibold text-gray-900 dark:text-white">
                {item.amount
                  ? typeof item.amount === "number"
                    ? `$${item.amount.toLocaleString()}`
                    : item.amount
                  : item.percentage !== undefined
                  ? `${item.percentage}%`
                  : item.score !== undefined
                  ? `${item.score}/100`
                  : item.value !== undefined
                  ? item.value.toLocaleString()
                  : ""}
              </span>
            </div>
            <div className="w-full bg-gray-100 dark:bg-gray-600 rounded-full h-3 overflow-hidden">
              <div
                className="h-full rounded-full transition-all duration-500"
                style={{
                  width: `${Math.max(barWidth, 2)}%`,
                  backgroundColor: barColor,
                }}
              />
            </div>
            {item.description && (
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{item.description}</p>
            )}
          </div>
        );
      })}
    </div>
  );
}
