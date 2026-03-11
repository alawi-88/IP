"use client";

interface Props {
  content: any;
}

const stageColors = [
  { bg: "bg-emerald-500", light: "bg-emerald-50", text: "text-emerald-700", border: "border-emerald-200" },
  { bg: "bg-blue-500", light: "bg-blue-50", text: "text-blue-700", border: "border-blue-200" },
  { bg: "bg-purple-500", light: "bg-purple-50", text: "text-purple-700", border: "border-purple-200" },
  { bg: "bg-orange-500", light: "bg-orange-50", text: "text-orange-700", border: "border-orange-200" },
  { bg: "bg-pink-500", light: "bg-pink-50", text: "text-pink-700", border: "border-pink-200" },
];

export default function TimelineRenderer({ content }: Props) {
  const phases =
    content.phases ||
    content.stages ||
    content.items ||
    content.milestones ||
    content.journey ||
    content.steps ||
    (Array.isArray(content) ? content : []);

  if (!phases.length) return null;

  return (
    <div className="space-y-0">
      {phases.map((phase: any, i: number) => {
        const color = stageColors[i % stageColors.length];
        return (
          <div key={i} className="flex gap-4">
            <div className="flex flex-col items-center">
              <div className={`w-4 h-4 rounded-full ${color.bg} ring-4 ring-white shadow-sm`} />
              {i < phases.length - 1 && <div className="w-0.5 flex-1 bg-gray-200 my-1" />}
            </div>
            <div className={`flex-1 ${color.light} ${color.border} border rounded-xl p-4 mb-3`}>
              <div className="flex items-center gap-2 mb-1.5">
                <span className={`px-2.5 py-0.5 rounded-lg text-xs font-bold ${color.text}`}>
                  {phase.stage || phase.phase || phase.title || phase.name || `Phase ${i + 1}`}
                </span>
              </div>
              {phase.description && (
                <p className="text-sm text-gray-600 leading-relaxed">{phase.description}</p>
              )}
              {phase.channels && (
                <p className="text-xs text-gray-400 mt-2">
                  <span className="font-medium text-gray-500">Channels:</span> {phase.channels}
                </p>
              )}
              {phase.activities && Array.isArray(phase.activities) && (
                <ul className="mt-2 space-y-1">
                  {phase.activities.map((a: any, j: number) => (
                    <li key={j} className="text-xs text-gray-500 flex items-start gap-1.5">
                      <span className={`w-1 h-1 rounded-full ${color.bg} mt-1.5 flex-shrink-0`} />
                      <span>{typeof a === "string" ? a : a.text || a.title}</span>
                    </li>
                  ))}
                </ul>
              )}
              {phase.items && Array.isArray(phase.items) && (
                <ul className="mt-2 space-y-1">
                  {phase.items.map((item: any, j: number) => (
                    <li key={j} className="text-xs text-gray-500 flex items-start gap-1.5">
                      <span className={`w-1 h-1 rounded-full ${color.bg} mt-1.5 flex-shrink-0`} />
                      <span>{typeof item === "string" ? item : item.text || item.title || item.name}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}
