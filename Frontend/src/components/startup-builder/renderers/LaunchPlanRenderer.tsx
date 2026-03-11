"use client";

interface Props {
  content: any;
}

export default function LaunchPlanRenderer({ content }: Props) {
  const phases = content.phases || content.stages || content.items || (Array.isArray(content) ? content : []);

  const phaseStyles = [
    { bg: "bg-blue-50", border: "border-blue-200", badge: "bg-blue-100 text-blue-700", dot: "bg-blue-500", icon: "📋" },
    { bg: "bg-emerald-50", border: "border-emerald-200", badge: "bg-emerald-100 text-emerald-700", dot: "bg-emerald-500", icon: "🚀" },
    { bg: "bg-purple-50", border: "border-purple-200", badge: "bg-purple-100 text-purple-700", dot: "bg-purple-500", icon: "📈" },
    { bg: "bg-orange-50", border: "border-orange-200", badge: "bg-orange-100 text-orange-700", dot: "bg-orange-500", icon: "🎯" },
  ];

  return (
    <div className="space-y-4">
      {phases.map((phase: any, i: number) => {
        const style = phaseStyles[i % phaseStyles.length];
        const items = phase.items || phase.activities || phase.tasks || phase.steps || [];
        return (
          <div key={i} className={`${style.bg} ${style.border} border rounded-xl overflow-hidden`}>
            {/* Phase header */}
            <div className="flex items-center justify-between px-5 py-4">
              <div className="flex items-center gap-3">
                <span className="text-xl">{style.icon}</span>
                <h4 className="font-bold text-gray-800">
                  {phase.title || phase.name || phase.phase || `Phase ${i + 1}`}
                </h4>
              </div>
              <div className="flex items-center gap-2">
                {phase.timeline && (
                  <span className={`px-2.5 py-1 rounded-lg text-xs font-bold ${style.badge}`}>
                    {phase.timeline}
                  </span>
                )}
                {phase.target && (
                  <span className="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">
                    {phase.target}
                  </span>
                )}
              </div>
            </div>
            {/* Phase content */}
            <div className="px-5 pb-4">
              {phase.description && (
                <p className="text-sm text-gray-600 mb-3 leading-relaxed">{phase.description}</p>
              )}
              {items.length > 0 && (
                <div className="space-y-1.5">
                  {items.map((item: any, j: number) => (
                    <div key={j} className="flex items-start gap-2.5 bg-white/60 rounded-lg px-3 py-2">
                      <span className={`w-1.5 h-1.5 rounded-full ${style.dot} mt-1.5 flex-shrink-0`} />
                      <span className="text-sm text-gray-600">
                        {typeof item === "string" ? item : item.text || item.title || item.name}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}
