"use client";

import React from "react";

interface Persona {
  name?: string;
  age?: string | number;
  occupation?: string;
  background?: string;
  quote?: string;
  avatar?: string;
  pain_points?: string[];
  goals?: string[];
  motivations?: string[];
  behaviors?: string[];
  demographics?: Record<string, string>;
  channels?: string[];
}

interface PersonaCardRendererProps {
  content: any;
}

export default function PersonaCardRenderer({ content }: PersonaCardRendererProps) {
  const personas: Persona[] = Array.isArray(content)
    ? content
    : content?.personas || [content];

  if (!personas.length || (!personas[0]?.name && !personas[0]?.background)) {
    return <div className="text-gray-500 dark:text-gray-400 text-sm italic">No persona data available.</div>;
  }

  return (
    <div className="space-y-8">
      {personas.map((persona, idx) => (
        <div key={idx} className="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          {/* Header */}
          <div
            className="px-6 py-5 flex items-center gap-4"
            style={{
              background: `linear-gradient(135deg, var(--dga-primary-500, #3B82F6), var(--dga-primary-700, #1D4ED8))`,
            }}
          >
            <div className="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
              {persona.avatar || persona.name?.charAt(0)?.toUpperCase() || "?"}
            </div>
            <div className="text-white">
              <h3 className="text-lg font-bold">
                {persona.name || `Persona ${idx + 1}`}
              </h3>
              <p className="text-sm opacity-90">
                {[persona.age && `Age ${persona.age}`, persona.occupation]
                  .filter(Boolean)
                  .join(" · ")}
              </p>
            </div>
          </div>

          <div className="p-6 space-y-5 bg-white dark:bg-gray-800">
            {persona.quote && (
              <blockquote className="border-l-4 pl-4 italic text-gray-600 dark:text-gray-400 text-sm" style={{ borderColor: "var(--dga-primary-300, #93C5FD)" }}>
                &ldquo;{persona.quote}&rdquo;
              </blockquote>
            )}

            {persona.background && (
              <div>
                <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Background</h4>
                <p className="text-sm text-gray-700 dark:text-gray-300">{persona.background}</p>
              </div>
            )}

            {persona.demographics && Object.keys(persona.demographics).length > 0 && (
              <div>
                <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Demographics</h4>
                <div className="grid grid-cols-2 gap-2">
                  {Object.entries(persona.demographics).map(([key, val]) => (
                    <div key={key} className="bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2">
                      <span className="text-xs text-gray-500 dark:text-gray-400 capitalize">{key.replace(/_/g, " ")}</span>
                      <p className="text-sm font-medium text-gray-800 dark:text-gray-200">{val}</p>
                    </div>
                  ))}
                </div>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {persona.pain_points && persona.pain_points.length > 0 && (
                <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400 mb-2">Pain Points</h4>
                  <ul className="space-y-1">
                    {persona.pain_points.map((point, pIdx) => (
                      <li key={pIdx} className="text-sm text-red-800 dark:text-red-300 flex items-start gap-2">
                        <span className="text-red-400 mt-1 flex-shrink-0">&#x2022;</span>
                        {point}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {persona.goals && persona.goals.length > 0 && (
                <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-green-700 dark:text-green-400 mb-2">Goals</h4>
                  <ul className="space-y-1">
                    {persona.goals.map((goal, gIdx) => (
                      <li key={gIdx} className="text-sm text-green-800 dark:text-green-300 flex items-start gap-2">
                        <span className="text-green-400 mt-1 flex-shrink-0">&#x2022;</span>
                        {goal}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>

            {persona.motivations && persona.motivations.length > 0 && (
              <div>
                <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Motivations</h4>
                <div className="flex flex-wrap gap-2">
                  {persona.motivations.map((m, mIdx) => (
                    <span
                      key={mIdx}
                      className="text-xs px-3 py-1 rounded-full"
                      style={{
                        backgroundColor: "var(--dga-primary-50, #EFF6FF)",
                        color: "var(--dga-primary-700, #1D4ED8)",
                      }}
                    >
                      {m}
                    </span>
                  ))}
                </div>
              </div>
            )}

            {persona.channels && persona.channels.length > 0 && (
              <div>
                <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Preferred Channels</h4>
                <div className="flex flex-wrap gap-2">
                  {persona.channels.map((ch, chIdx) => (
                    <span key={chIdx} className="text-xs px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                      {ch}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}
