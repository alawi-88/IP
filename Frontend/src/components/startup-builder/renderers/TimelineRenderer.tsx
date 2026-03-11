"use client";

interface Props {
  content: any;
}

const stageColors = [
  { bg: "bg-emerald-500", light: "bg-emerald-50", text: "text-emerald-700" },
  { bg: "bg-blue-500", light: "bg-blue-50", text: "text-blue-700" },
  { bg: "bg-purple-500", light: "bg-purple-50", text: "text-purple-700" },
  { bg: "bg-orange-500", light: "bg-orange-50", text: "text-orange-700" },
  { bg: "bg-pink-500", light: "bg-pink-50", text: "text-pink-700" },
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
            {/* Timeline line */}
            <div className="flex flex-col items-center">
              <div className={`w-3 h-3 rounded-full ${color.bg} ring-4 ring-white`} />
              {i < phases.length - 1 && (
                <div className="w-0.5 flex-1 bg-gray-200 my-1" />
              )}
            </div>
            {/* Content */}
            <div className="pb-6 flex-1">
              <div className="flex items-center gap-2 mb-1">
                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${color.light} ${color.text}`}>
                  {phase.stage || phase.phase || phase.title || phase.name || `Phase ${i + 1}`}
                </span>
              </div>
              {phase.description && (
                <p className="text-sm text-gray-600 mt-1">{phase.description}</p>
              )}
              {phase.channels && (
                <p className="text-xs text-gray-400 mt-1">
                  <span className="font-medium">Channels:</span> {phase.channels}
                </p>
              )}
              {phase.activities && Array.isArray(phase.activities) && (
                <ul className="mt-2 space-y-1">
                  {phase.activities.map((a: any, j: number) => (
                    <li key={j} className="text-xs text-gray-500 flex items-start gap-1.5">
                      <span className="mt-1.5">•</span>
                      <span>{typeof a === "string" ? a : a.text || a.title}</span>
                    </li>
                  ))}
                </ul>
              )}
              {phase.items && Array.isArray(phase.items) && (
                <ul className="mt-2 space-y-1">
                  {phase.items.map((item: any, j: number) => (
                    <li key={j} className="text-xs text-gray-500 flex items-start gap-1.5">
                      <span className="mt-1.5">•</span>
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
