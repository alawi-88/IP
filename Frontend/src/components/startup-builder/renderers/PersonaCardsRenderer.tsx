"use client";

interface Props {
  content: any;
}

export default function PersonaCardsRenderer({ content }: Props) {
  const personas = Array.isArray(content)
    ? content
    : content.personas || content.items || content.cards
      ? (content.personas || content.items || content.cards)
      : (content.name || content.role) ? [content] : [content];

  const avatarColors = [
    "from-emerald-400 to-teal-500",
    "from-blue-400 to-indigo-500",
    "from-purple-400 to-pink-500",
    "from-orange-400 to-red-500",
  ];

  return (
    <div className="space-y-8">
      {personas.map((persona: any, i: number) => (
        <div key={i} className="rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
          {/* Persona Header with gradient accent */}
          <div className={`h-2 bg-gradient-to-r ${avatarColors[i % avatarColors.length]}`} />

          <div className="flex flex-col md:flex-row gap-6 p-6">
            {/* Avatar & Identity */}
            <div className="flex flex-col items-center md:min-w-[200px]">
              <div className={`w-20 h-20 rounded-2xl bg-gradient-to-br ${avatarColors[i % avatarColors.length]} flex items-center justify-center text-3xl shadow-lg mb-3`}>
                {persona.avatar || (i === 0 ? "👩‍💻" : i === 1 ? "👨‍🔬" : "👤")}
              </div>
              <h4 className="text-lg font-bold text-gray-800 text-center">
                {persona.name || `Persona ${i + 1}`}
              </h4>
              {(persona.age || persona.role || persona.title) && (
                <p className="text-sm text-gray-500 text-center mt-0.5">
                  {[persona.age, persona.role || persona.title].filter(Boolean).join(", ")}
                </p>
              )}
              {persona.label && (
                <span className="mt-2 px-3 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                  {persona.label}
                </span>
              )}
              {persona.location && (
                <p className="text-xs text-gray-400 mt-2 flex items-center gap-1">
                  <span>📍</span> {persona.location}
                </p>
              )}
            </div>

            {/* Details */}
            <div className="flex-1 space-y-4">
              {persona.background && (
                <div className="bg-gray-50 rounded-xl p-4">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1.5">Background</h5>
                  <p className="text-sm text-gray-700 leading-relaxed">{persona.background}</p>
                </div>
              )}
              {persona.quote && (
                <div className="bg-emerald-50/50 rounded-xl p-4 border border-emerald-100">
                  <p className="text-sm text-gray-700 italic leading-relaxed">
                    &ldquo;{persona.quote}&rdquo;
                  </p>
                </div>
              )}
              {persona.demographics && typeof persona.demographics === "object" && (
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  {Object.entries(persona.demographics).map(([key, val]: [string, any]) => (
                    <div key={key} className="bg-gray-50 rounded-xl p-3 text-center">
                      <p className="text-[10px] text-gray-400 uppercase font-semibold tracking-wider">{key.replace(/_/g, " ")}</p>
                      <p className="text-sm font-semibold text-gray-700 mt-0.5">{String(val)}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Pain Points & Goals - Side by Side */}
          {(persona.pain_points || persona.goals) && (
            <div className="grid grid-cols-1 md:grid-cols-2 border-t border-gray-100">
              {persona.pain_points && (
                <div className="p-5 border-b md:border-b-0 md:border-r border-gray-100 bg-red-50/30">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-red-500 mb-3 flex items-center gap-2">
                    <span className="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center text-[10px]">🔴</span>
                    Pain Points
                  </h5>
                  <div className="space-y-2">
                    {(Array.isArray(persona.pain_points) ? persona.pain_points : []).map((p: string, j: number) => (
                      <div key={j} className="flex items-start gap-2.5 bg-white rounded-lg px-3 py-2 border border-red-100/50">
                        <span className="w-1.5 h-1.5 rounded-full bg-red-400 mt-1.5 flex-shrink-0" />
                        <span className="text-sm text-gray-600">{p}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              {persona.goals && (
                <div className="p-5 bg-green-50/30">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-green-600 mb-3 flex items-center gap-2">
                    <span className="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center text-[10px]">🟢</span>
                    Goals
                  </h5>
                  <div className="space-y-2">
                    {(Array.isArray(persona.goals) ? persona.goals : []).map((g: string, j: number) => (
                      <div key={j} className="flex items-start gap-2.5 bg-white rounded-lg px-3 py-2 border border-green-100/50">
                        <span className="w-1.5 h-1.5 rounded-full bg-green-400 mt-1.5 flex-shrink-0" />
                        <span className="text-sm text-gray-600">{g}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Motivations & Frustrations */}
          {(persona.motivations || persona.frustrations) && (
            <div className="grid grid-cols-1 md:grid-cols-2 border-t border-gray-100">
              {persona.motivations && (
                <div className="p-5 border-b md:border-b-0 md:border-r border-gray-100 bg-blue-50/30">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-blue-600 mb-3 flex items-center gap-2">
                    <span className="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-[10px]">💡</span>
                    Motivations
                  </h5>
                  <div className="space-y-2">
                    {(Array.isArray(persona.motivations) ? persona.motivations : []).map((m: string, j: number) => (
                      <div key={j} className="flex items-start gap-2.5 bg-white rounded-lg px-3 py-2 border border-blue-100/50">
                        <span className="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 flex-shrink-0" />
                        <span className="text-sm text-gray-600">{m}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              {persona.frustrations && (
                <div className="p-5 bg-orange-50/30">
                  <h5 className="text-xs font-bold uppercase tracking-wider text-orange-600 mb-3 flex items-center gap-2">
                    <span className="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center text-[10px]">😤</span>
                    Frustrations
                  </h5>
                  <div className="space-y-2">
                    {(Array.isArray(persona.frustrations) ? persona.frustrations : []).map((f: string, j: number) => (
                      <div key={j} className="flex items-start gap-2.5 bg-white rounded-lg px-3 py-2 border border-orange-100/50">
                        <span className="w-1.5 h-1.5 rounded-full bg-orange-400 mt-1.5 flex-shrink-0" />
                        <span className="text-sm text-gray-600">{f}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Preferred Channels */}
          {persona.preferred_channels && Array.isArray(persona.preferred_channels) && (
            <div className="border-t border-gray-100 p-5">
              <h5 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">
                Preferred Channels
              </h5>
              <div className="flex flex-wrap gap-2">
                {persona.preferred_channels.map((ch: string, j: number) => (
                  <span key={j} className="px-3 py-1.5 bg-gray-50 text-gray-600 rounded-lg text-xs font-medium border border-gray-200">
                    {ch}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Digital Behavior */}
          {persona.digital_behavior && typeof persona.digital_behavior === "object" && (
            <div className="border-t border-gray-100 p-5">
              <h5 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">
                Digital Behavior
              </h5>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                {Object.entries(persona.digital_behavior).map(([key, val]: [string, any]) => (
                  <div key={key} className="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                    <p className="text-[10px] text-gray-400 uppercase font-semibold tracking-wider">{key.replace(/_/g, " ")}</p>
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
