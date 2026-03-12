"use client";

import React from "react";

interface JourneyStage {
  stage?: string;
  name?: string;
  description?: string;
  touchpoints?: string[];
  actions?: string[];
  emotions?: string;
  opportunities?: string[];
  pain_points?: string[];
}

interface JourneyTimelineRendererProps {
  content: any;
}

export default function JourneyTimelineRenderer({ content }: JourneyTimelineRendererProps) {
  const stages: JourneyStage[] = Array.isArray(content)
    ? content
    : content?.stages || content?.journey || content?.phases || [];

  if (!stages.length) {
    return <div className="text-gray-500 dark:text-gray-400 text-sm italic">No journey data available.</div>;
  }

  const emotionToEmoji = (emotion?: string): string => {
    if (!emotion) return "";
    const lower = emotion.toLowerCase();
    if (lower.includes("happy") || lower.includes("delight") || lower.includes("excited")) return "😊";
    if (lower.includes("neutral") || lower.includes("curious")) return "😐";
    if (lower.includes("frustrated") || lower.includes("anxious") || lower.includes("confused")) return "😟";
    if (lower.includes("satisfied") || lower.includes("confident")) return "😌";
    return "💭";
  };

  return (
    <div className="space-y-0">
      {stages.map((stage, idx) => {
        const isLast = idx === stages.length - 1;
        return (
          <div key={idx} className="flex gap-4">
            <div className="flex flex-col items-center flex-shrink-0">
              <div
                className="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm"
                style={{ backgroundColor: "var(--dga-primary-500, #3B82F6)" }}
              >
                {idx + 1}
              </div>
              {!isLast && (
                <div
                  className="w-0.5 flex-1 min-h-[40px]"
                  style={{ backgroundColor: "var(--dga-primary-200, #BFDBFE)" }}
                />
              )}
            </div>

            <div className={`flex-1 ${isLast ? "pb-0" : "pb-6"}`}>
              <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <div className="flex items-center gap-2 mb-2">
                  <h4 className="font-semibold text-gray-900 dark:text-white">
                    {stage.stage || stage.name || `Stage ${idx + 1}`}
                  </h4>
                  {stage.emotions && (
                    <span className="text-lg" title={stage.emotions}>
                      {emotionToEmoji(stage.emotions)}
                    </span>
                  )}
                </div>

                {stage.description && (
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">{stage.description}</p>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {stage.touchpoints && stage.touchpoints.length > 0 && (
                    <div>
                      <h5 className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Touchpoints</h5>
                      <div className="flex flex-wrap gap-1">
                        {stage.touchpoints.map((tp, tpIdx) => (
                          <span
                            key={tpIdx}
                            className="text-xs px-2 py-0.5 rounded-full"
                            style={{
                              backgroundColor: "var(--dga-primary-50, #EFF6FF)",
                              color: "var(--dga-primary-700, #1D4ED8)",
                            }}
                          >
                            {tp}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}

                  {stage.actions && stage.actions.length > 0 && (
                    <div>
                      <h5 className="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Actions</h5>
                      <ul className="space-y-0.5">
                        {stage.actions.map((action, aIdx) => (
                          <li key={aIdx} className="text-xs text-gray-600 dark:text-gray-400 flex items-start gap-1">
                            <span className="text-gray-400 dark:text-gray-500">&#x2192;</span> {action}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}

                  {stage.opportunities && stage.opportunities.length > 0 && (
                    <div>
                      <h5 className="text-xs font-semibold uppercase text-green-600 mb-1">Opportunities</h5>
                      <ul className="space-y-0.5">
                        {stage.opportunities.map((opp, oIdx) => (
                          <li key={oIdx} className="text-xs text-green-700 dark:text-green-400 flex items-start gap-1">
                            <span>&#x2713;</span> {opp}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}

                  {stage.pain_points && stage.pain_points.length > 0 && (
                    <div>
                      <h5 className="text-xs font-semibold uppercase text-red-600 mb-1">Pain Points</h5>
                      <ul className="space-y-0.5">
                        {stage.pain_points.map((pp, ppIdx) => (
                          <li key={ppIdx} className="text-xs text-red-700 dark:text-red-400 flex items-start gap-1">
                            <span>&#x2717;</span> {pp}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
