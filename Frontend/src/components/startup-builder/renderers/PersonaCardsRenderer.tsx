"use client";

interface Props {
  content: any;
}

export default function PersonaCardsRenderer({ content }: Props) {
  const personas = Array.isArray(content)
    ? content
    : content.personas || content.items || content.cards || [content];

  return (
    <div className="space-y-6">
      {personas.map((persona: any, i: number) => (
        <div key={i} className="rounded-xl border border-gray-100 overflow-hidden">
          {/* Persona Header */}
          <div className="flex flex-col md:flex-row gap-6 p-6">
            {/* Avatar */}
            <div className="flex flex-col items-center md:min-w-[180px]">
              <div className="w-24 h-24 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center text-4xl mb-3">
                {persona.avatar || (i === 0 ? "👩‍💻" : "👨‍🔬")}
              </div>
              <h4 className="text-lg font-bold text-gray-800 text-center">
                {persona.name || `Persona ${i + 1}`}
              </h4>
              {(persona.age || persona.role || persona.title) && (
                <p className="text-xs text-gray-500 text-center mt-0.5">
                  {[persona.age, persona.role || persona.title].filter(Boolean).join(", ")}
                </p>
              )}
              {persona.label && (
                <span className="mt-1 px-2 py-0.5 rounded-full text-xs bg-emerald-50 text-emerald-700 font-medium">
                  {persona.label}
                </span>
              )}
            </div>

            {/* Details */}
            <div className="flex-1 space-y-4">
              {persona.background && (
                <div>
                  <h5 className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Background</h5>
                  <p className="text-sm text-gray-600">{persona.background}</p>
                </div>
              )}
              {persona.quote && (
                <div>
                  <h5 className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Quote</h5>
                  <p className="text-sm text-gray-600 italic border-l-2 border-emerald-300 pl-3">
                    &ldquo;{persona.quote}&rdquo;
                  </p>
                </div>
              )}
              {persona.demographics && typeof persona.demographics === "object" && (
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {Object.entries(persona.demographics).map(([key, val]: [string, any]) => (
                    <div key={key} className="bg-gray-50 rounded-lg p-2">
                      <p className="text-xs text-gray-400 uppercase">{key.replace(/_/g, " ")}</p>
                      <p className="text-sm font-medium text-gray-700">{String(val)}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Pain Points & Goals */}
          {(persona.pain_points || persona.goals) && (
            <div className="grid grid-cols-1 md:grid-cols-2 border-t border-gray-100">
              {persona.pain_points && (
                <div className="p-5 border-b md:border-b-0 md:border-r border-gray-100">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-red-500 mb-2 flex items-center gap-1.5">
                    🔴 Pain Points
                  </h5>
                  <ul className="space-y-1.5">
                    {(Array.isArray(persona.pain_points) ? persona.pain_points : []).map((p: string, j: number) => (
                      <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                        <span className="text-gray-400 mt-1">•</span>
                        <span>{p}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              {persona.goals && (
                <div className="p-5">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-green-500 mb-2 flex items-center gap-1.5">
                    🟢 Goals
                  </h5>
                  <ul className="space-y-1.5">
                    {(Array.isArray(persona.goals) ? persona.goals : []).map((g: string, j: number) => (
                      <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                        <span className="text-gray-400 mt-1">•</span>
                        <span>{g}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}

          {/* Digital Behavior */}
          {persona.digital_behavior && typeof persona.digital_behavior === "object" && (
            <div className="border-t border-gray-100 p-5">
              <h5 className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">
                Digital Behavior
              </h5>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {Object.entries(persona.digital_behavior).map(([key, val]: [string, any]) => (
                  <div key={key} className="bg-gray-50 rounded-lg p-3 text-center">
                    <p className="text-xs text-gray-400 uppercase">{key.replace(/_/g, " ")}</p>
                    <p className="text-sm font-semibold text-gray-700 mt-0.5">{String(val)}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
