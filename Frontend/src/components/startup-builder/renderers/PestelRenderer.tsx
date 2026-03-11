"use client";

interface Props {
  content: any;
}

const pestelCategories = [
  { key: "political", label: "Political", icon: "🏛️", bg: "bg-blue-50", border: "border-blue-200", text: "text-blue-700", headerBg: "bg-blue-100", dot: "bg-blue-500" },
  { key: "economic", label: "Economic", icon: "💰", bg: "bg-emerald-50", border: "border-emerald-200", text: "text-emerald-700", headerBg: "bg-emerald-100", dot: "bg-emerald-500" },
  { key: "social", label: "Social", icon: "👥", bg: "bg-purple-50", border: "border-purple-200", text: "text-purple-700", headerBg: "bg-purple-100", dot: "bg-purple-500" },
  { key: "technological", label: "Technological", icon: "💻", bg: "bg-orange-50", border: "border-orange-200", text: "text-orange-700", headerBg: "bg-orange-100", dot: "bg-orange-500" },
  { key: "environmental", label: "Environmental", icon: "🌍", bg: "bg-teal-50", border: "border-teal-200", text: "text-teal-700", headerBg: "bg-teal-100", dot: "bg-teal-500" },
  { key: "legal", label: "Legal", icon: "⚖️", bg: "bg-red-50", border: "border-red-200", text: "text-red-700", headerBg: "bg-red-100", dot: "bg-red-500" },
];

export default function PestelRenderer({ content }: Props) {
  if (content.categories && Array.isArray(content.categories)) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {content.categories.map((cat: any, i: number) => {
          const meta = pestelCategories[i] || pestelCategories[0];
          return (
            <div key={i} className={`${meta.bg} ${meta.border} border rounded-xl overflow-hidden`}>
              <div className={`${meta.headerBg} px-4 py-3 flex items-center gap-2`}>
                <span className="text-lg">{meta.icon}</span>
                <h4 className={`font-bold ${meta.text}`}>{cat.name || cat.title || meta.label}</h4>
              </div>
              <div className="p-4 space-y-2">
                {(cat.factors || cat.items || []).map((f: any, j: number) => (
                  <div key={j} className="flex items-start gap-2.5 bg-white/60 rounded-lg px-3 py-2">
                    <span className={`w-1.5 h-1.5 rounded-full ${meta.dot} mt-1.5 flex-shrink-0`} />
                    <span className="text-sm text-gray-600">{typeof f === "string" ? f : f.text || f.factor || f.title}</span>
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      {pestelCategories.map((meta) => {
        const items = content[meta.key];
        if (!items) return null;
        const list = Array.isArray(items) ? items : items.factors || items.items || [];
        return (
          <div key={meta.key} className={`${meta.bg} ${meta.border} border rounded-xl overflow-hidden`}>
            <div className={`${meta.headerBg} px-4 py-3 flex items-center gap-2`}>
              <span className="text-lg">{meta.icon}</span>
              <h4 className={`font-bold ${meta.text}`}>{meta.label}</h4>
            </div>
            <div className="p-4 space-y-2">
              {list.map((f: any, j: number) => (
                <div key={j} className="flex items-start gap-2.5 bg-white/60 rounded-lg px-3 py-2">
                  <span className={`w-1.5 h-1.5 rounded-full ${meta.dot} mt-1.5 flex-shrink-0`} />
                  <span className="text-sm text-gray-600">{typeof f === "string" ? f : f.text || f.factor || f.title}</span>
                </div>
              ))}
            </div>
          </div>
        );
      })}
    </div>
  );
}
