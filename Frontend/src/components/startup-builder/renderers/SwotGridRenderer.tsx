"use client";

interface Props {
  content: any;
}

export default function SwotGridRenderer({ content }: Props) {
  const quadrants = [
    { key: "strengths", label: "Strengths", icon: "💪", bg: "bg-green-50", border: "border-green-200", text: "text-green-800", headerBg: "bg-green-100", dot: "bg-green-500" },
    { key: "weaknesses", label: "Weaknesses", icon: "⚡", bg: "bg-red-50", border: "border-red-200", text: "text-red-800", headerBg: "bg-red-100", dot: "bg-red-500" },
    { key: "opportunities", label: "Opportunities", icon: "🌟", bg: "bg-blue-50", border: "border-blue-200", text: "text-blue-800", headerBg: "bg-blue-100", dot: "bg-blue-500" },
    { key: "threats", label: "Threats", icon: "⚠️", bg: "bg-orange-50", border: "border-orange-200", text: "text-orange-800", headerBg: "bg-orange-100", dot: "bg-orange-500" },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {quadrants.map((q) => {
        const items = content[q.key] || [];
        return (
          <div key={q.key} className={`${q.bg} ${q.border} border rounded-xl overflow-hidden`}>
            <div className={`${q.headerBg} px-5 py-3.5 flex items-center gap-2.5`}>
              <span className="text-lg">{q.icon}</span>
              <h4 className={`font-bold ${q.text}`}>{q.label}</h4>
              <span className={`ml-auto px-2 py-0.5 rounded-full text-[10px] font-bold ${q.text} ${q.headerBg}`}>
                {(Array.isArray(items) ? items : []).length}
              </span>
            </div>
            <div className="p-4">
              <div className="space-y-2">
                {(Array.isArray(items) ? items : []).map((item: any, i: number) => (
                  <div key={i} className="flex items-start gap-2.5 bg-white/60 rounded-lg px-3 py-2">
                    <span className={`w-1.5 h-1.5 rounded-full ${q.dot} mt-1.5 flex-shrink-0`} />
                    <span className="text-sm text-gray-700">
                      {typeof item === "string" ? item : item.text || item.title || item.description || item.name || JSON.stringify(item)}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
