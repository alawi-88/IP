"use client";

interface Props {
  content: any;
}

const pestelCategories = [
  { key: "political", label: "Political", icon: "🏛️", bg: "bg-blue-50", border: "border-blue-200", text: "text-blue-700" },
  { key: "economic", label: "Economic", icon: "💰", bg: "bg-emerald-50", border: "border-emerald-200", text: "text-emerald-700" },
  { key: "social", label: "Social", icon: "👥", bg: "bg-purple-50", border: "border-purple-200", text: "text-purple-700" },
  { key: "technological", label: "Technological", icon: "💻", bg: "bg-orange-50", border: "border-orange-200", text: "text-orange-700" },
  { key: "environmental", label: "Environmental", icon: "🌍", bg: "bg-teal-50", border: "border-teal-200", text: "text-teal-700" },
  { key: "legal", label: "Legal", icon: "⚖️", bg: "bg-red-50", border: "border-red-200", text: "text-red-700" },
];

export default function PestelRenderer({ content }: Props) {
  // Handle array format
  if (content.categories && Array.isArray(content.categories)) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {content.categories.map((cat: any, i: number) => {
          const meta = pestelCategories[i] || pestelCategories[0];
          return (
            <div key={i} className={`${meta.bg} ${meta.border} border rounded-xl p-4`}>
              <div className="flex items-center gap-2 mb-2">
                <span className="text-lg">{meta.icon}</span>
                <h4 className={`font-bold ${meta.text}`}>{cat.name || cat.title || meta.label}</h4>
              </div>
              <ul className="space-y-1.5">
                {(cat.factors || cat.items || []).map((f: any, j: number) => (
                  <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                    <span className="text-gray-400 mt-1.5">•</span>
                    <span>{typeof f === "string" ? f : f.text || f.factor || f.title}</span>
                  </li>
                ))}
              </ul>
            </div>
          );
        })}
      </div>
    );
  }

  // Handle object with direct keys
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      {pestelCategories.map((meta) => {
        const items = content[meta.key];
        if (!items) return null;
        const list = Array.isArray(items) ? items : items.factors || items.items || [];
        return (
          <div key={meta.key} className={`${meta.bg} ${meta.border} border rounded-xl p-4`}>
            <div className="flex items-center gap-2 mb-2">
              <span className="text-lg">{meta.icon}</span>
              <h4 className={`font-bold ${meta.text}`}>{meta.label}</h4>
            </div>
            <ul className="space-y-1.5">
              {list.map((f: any, j: number) => (
                <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                  <span className="text-gray-400 mt-1.5">•</span>
                  <span>{typeof f === "string" ? f : f.text || f.factor || f.title}</span>
                </li>
              ))}
            </ul>
          </div>
        );
      })}
    </div>
  );
}
