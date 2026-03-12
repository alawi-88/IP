"use client";

import React from "react";

interface ScoreDimension {
  label?: string;
  name?: string;
  score?: number;
  value?: number;
  max?: number;
  description?: string;
}

interface ViabilityScoreRendererProps {
  content: any;
}

export default function ViabilityScoreRenderer({ content }: ViabilityScoreRendererProps) {
  const overall =
    content?.overall ??
    content?.score ??
    content?.total ??
    content?.viability_score ??
    null;

  const dimensions: ScoreDimension[] =
    content?.dimensions ||
    content?.breakdown ||
    content?.criteria ||
    (Array.isArray(content) ? content : []);

  const getScoreColor = (score: number, max: number = 100): string => {
    const pct = (score / max) * 100;
    if (pct >= 80) return "#10B981";
    if (pct >= 60) return "#F59E0B";
    if (pct >= 40) return "#F97316";
    return "#EF4444";
  };

  const getScoreLabel = (score: number, max: number = 100): string => {
    const pct = (score / max) * 100;
    if (pct >= 80) return "Excellent";
    if (pct >= 60) return "Good";
    if (pct >= 40) return "Fair";
    return "Needs Work";
  };

  return (
    <div className="space-y-6">
      {/* Overall Score */}
      {overall !== null && (
        <div className="flex items-center gap-6 p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
          <div className="relative w-28 h-28 flex-shrink-0">
            <svg className="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
              <circle
                cx="50"
                cy="50"
                r="42"
                fill="none"
                stroke="#E5E7EB"
                strokeWidth="8"
                className="dark:stroke-gray-600"
              />
              <circle
                cx="50"
                cy="50"
                r="42"
                fill="none"
                stroke={getScoreColor(overall)}
                strokeWidth="8"
                strokeLinecap="round"
                strokeDasharray={`${(overall / 100) * 264} 264`}
              />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <span className="text-2xl font-extrabold text-gray-900 dark:text-white">{overall}</span>
              <span className="text-xs text-gray-500 dark:text-gray-400">/100</span>
            </div>
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-900 dark:text-white">Overall Viability</h3>
            <p
              className="text-sm font-semibold"
              style={{ color: getScoreColor(overall) }}
            >
              {getScoreLabel(overall)}
            </p>
            {content?.summary && (
              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{content.summary}</p>
            )}
          </div>
        </div>
      )}

      {/* Dimension Breakdown */}
      {dimensions.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {dimensions.map((dim, idx) => {
            const score = dim.score ?? dim.value ?? 0;
            const max = dim.max ?? 100;
            const pct = Math.round((score / max) * 100);
            const color = getScoreColor(score, max);

            return (
              <div key={idx} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <h4 className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    {dim.label || dim.name || `Dimension ${idx + 1}`}
                  </h4>
                  <span className="text-sm font-bold" style={{ color }}>
                    {score}/{max}
                  </span>
                </div>
                <div className="w-full bg-gray-100 dark:bg-gray-600 rounded-full h-2.5 overflow-hidden mb-2">
                  <div
                    className="h-full rounded-full transition-all duration-500"
                    style={{
                      width: `${pct}%`,
                      backgroundColor: color,
                    }}
                  />
                </div>
                {dim.description && (
                  <p className="text-xs text-gray-500 dark:text-gray-400">{dim.description}</p>
                )}
              </div>
            );
          })}
        </div>
      )}

      {overall === null && dimensions.length === 0 && (
        <div className="text-gray-500 dark:text-gray-400 text-sm italic">No viability score data available.</div>
      )}
    </div>
  );
}
