"use client";

interface Props {
  content: any;
}

const phaseColors = [
  { bg: "bg-blue-50", border: "border-blue-200", badge: "bg-blue-100 text-blue-700", dot: "bg-blue-500" },
  { bg: "bg-emerald-50", border: "border-emerald-200", badge: "bg-emerald-100 text-emerald-700", dot: "bg-emerald-500" },
  { bg: "bg-purple-50", border: "border-purple-200", badge: "bg-purple-100 text-purple-700", dot: "bg-purple-500" },
  { bg: "bg-orange-50", border: "border-orange-200", badge: "bg-orange-100 text-orange-700", dot: "bg-orange-500" },
];

export default function DevelopmentRoadmapRenderer({ content }: Props) {
  const phases = content.phases || content.milestones || content.stages || content.items || (Array.isArray(content) ? content : []);
  const label = content.label || content.title;

  return (
    <div className="space-y-4">
      {label && <span className="inline-block px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">{label}</span>}
      {phases.map((phase: any, i: number) => {
        const color = phaseColors[i % phaseColors.length];
        const items = phase.items || phase.tasks || phase.activities || phase.deliverables || [];
        return (
          <div key={i} className={`${color.bg} ${color.border} border rounded-xl p-5`}>
            <div className="flex items-center justify-between mb-3">
              <h4 className="font-bold text-gray-800">{phase.title || phase.name || phase.phase}</h4>
              {phase.timeline && (
                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${color.badge}`}>
                  {phase.timeline}
                </span>
              )}
            </div>
            {phase.description && (
              <p className="text-sm text-gray-600 mb-2">{phase.description}</p>
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
          </div>
        );
      })}
    </div>
  );
}
