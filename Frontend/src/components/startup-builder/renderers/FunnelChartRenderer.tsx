"use client";

interface Props {
  content: any;
}

export default function FunnelChartRenderer({ content }: Props) {
  const stages = content.stages || content.levels || content.items || (Array.isArray(content) ? content : []);

  const colors = ["bg-blue-500", "bg-blue-400", "bg-blue-300", "bg-emerald-400", "bg-emerald-300"];
  const widths = [100, 85, 70, 55, 40];

  return (
    <div className="space-y-2 py-2">
      {stages.map((stage: any, i: number) => (
        <div key={i} className="flex items-center gap-4">
          <div
            className={`${colors[i % colors.length]} rounded-lg py-3 px-4 text-white text-sm font-medium text-center transition-all`}
            style={{ width: `${widths[i % widths.length]}%` }}
          >
            {stage.label || stage.name || stage.title}
          </div>
          <span className="text-sm font-bold text-gray-700 whitespace-nowrap">
            {stage.value || stage.amount || stage.size || ""}
          </span>
        </div>
      ))}
    </div>
  );
}
