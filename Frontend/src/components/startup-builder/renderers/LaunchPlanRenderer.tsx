"use client";

interface Props {
  content: any;
}

export default function LaunchPlanRenderer({ content }: Props) {
  const phases = content.phases || content.stages || content.items || (Array.isArray(content) ? content : []);

  const phaseColors = [
    { bg: "bg-blue-50", border: "border-blue-200", badge: "bg-blue-100 text-blue-700" },
    { bg: "bg-emerald-50", border: "border-emerald-200", badge: "bg-emerald-100 text-emerald-700" },
    { bg: "bg-purple-50", border: "border-purple-200", badge: "bg-purple-100 text-purple-700" },
    { bg: "bg-orange-50", border: "border-orange-200", badge: "bg-orange-100 text-orange-700" },
  ];

  return (
    <div className="space-y-4">
      {phases.map((phase: any, i: number) => {
        const color = phaseColors[i % phaseColors.length];
        const items = phase.items || phase.activities || phase.tasks || phase.steps || [];
        return (
          <div key={i} className={`${color.bg} ${color.border} border rounded-xl p-5`}>
            <div className="flex items-center justify-between mb-3">
              <h4 className="font-bold text-gray-800">
                {phase.title || phase.name || phase.phase || `Phase ${i + 1}`}
              </h4>
              {phase.timeline && (
                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${color.badge}`}>
                  {phase.timeline}
                </span>
              )}
            </div>
            {phase.description && (
              <p className="text-sm text-gray-600 mb-3">{phase.description}</p>
            )}
            {items.length > 0 && (
              <ul className="space-y-1.5">
                {items.map((item: any, j: number) => (
                  <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                    <span className="text-gray-400 mt-1">•</span>
                    <span>{typeof item === "string" ? item : item.text || item.title || item.name}</span>
                  </li>
                ))}
              </ul>
            )}
            {phase.target && (
              <p className="text-xs text-gray-500 mt-2 font-medium">Target: {phase.target}</p>
            )}
          </div>
        );
      })}
    </div>
  );
}
