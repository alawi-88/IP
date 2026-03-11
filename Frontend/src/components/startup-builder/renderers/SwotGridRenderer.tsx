"use client";

interface Props {
  content: any;
}

export default function SwotGridRenderer({ content }: Props) {
  const quadrants = [
    { key: "strengths", label: "Strengths", icon: "💪", bg: "bg-green-50", border: "border-green-200", text: "text-green-800", headerBg: "bg-green-100" },
    { key: "weaknesses", label: "Weaknesses", icon: "⚡", bg: "bg-red-50", border: "border-red-200", text: "text-red-800", headerBg: "bg-red-100" },
    { key: "opportunities", label: "Opportunities", icon: "🌟", bg: "bg-blue-50", border: "border-blue-200", text: "text-blue-800", headerBg: "bg-blue-100" },
    { key: "threats", label: "Threats", icon: "⚠️", bg: "bg-orange-50", border: "border-orange-200", text: "text-orange-800", headerBg: "bg-orange-100" },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {quadrants.map((q) => {
        const items = content[q.key] || [];
        return (
          <div key={q.key} className={`${q.bg} ${q.border} border rounded-xl overflow-hidden`}>
            <div className={`${q.headerBg} px-4 py-3 flex items-center gap-2`}>
              <span>{q.icon}</span>
              <h4 className={`font-semibold ${q.text}`}>{q.label}</h4>
            </div>
            <div className="p-4">
              <ul className="space-y-2">
                {(Array.isArray(items) ? items : []).map((item: any, i: number) => (
                  <li key={i} className="flex items-start gap-2 text-sm text-gray-700">
                    <span className={`w-1.5 h-1.5 rounded-full ${q.text.replace("text-", "bg-")} mt-2 flex-shrink-0`} />
                    <span>{typeof item === "string" ? item : item.text || item.title || item.description || item.name || JSON.stringify(item)}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        );
      })}
    </div>
  );
}
