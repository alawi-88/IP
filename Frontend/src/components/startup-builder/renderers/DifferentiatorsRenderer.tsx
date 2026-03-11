"use client";

interface Props {
  content: any;
}

const diffColors = [
  { bg: "bg-emerald-50", border: "border-emerald-200", badge: "bg-emerald-100 text-emerald-700", icon: "🎯" },
  { bg: "bg-blue-50", border: "border-blue-200", badge: "bg-blue-100 text-blue-700", icon: "⚡" },
  { bg: "bg-purple-50", border: "border-purple-200", badge: "bg-purple-100 text-purple-700", icon: "💎" },
  { bg: "bg-orange-50", border: "border-orange-200", badge: "bg-orange-100 text-orange-700", icon: "🔑" },
  { bg: "bg-pink-50", border: "border-pink-200", badge: "bg-pink-100 text-pink-700", icon: "🌟" },
];

export default function DifferentiatorsRenderer({ content }: Props) {
  const items = content.differentiators || content.items || (Array.isArray(content) ? content : []);

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {items.map((item: any, i: number) => {
        const color = diffColors[i % diffColors.length];
        return (
          <div key={i} className={`${color.bg} ${color.border} border rounded-xl p-5`}>
            <div className="flex items-start gap-3">
              <span className="text-2xl">{color.icon}</span>
              <div className="flex-1">
                <h4 className="font-bold text-gray-800 mb-1">
                  {item.title || item.name || item.feature}
                </h4>
                {item.description && (
                  <p className="text-sm text-gray-600 mb-3 leading-relaxed">{item.description}</p>
                )}
                <div className="flex flex-wrap gap-2">
                  {item.metric && (
                    <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold ${color.badge}`}>
                      ↑ {item.metric}
                    </span>
                  )}
                  {item.impact && (
                    <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold ${color.badge}`}>
                      ↑ {item.impact}
                    </span>
                  )}
                  {item.advantage && (
                    <span className="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">
                      {item.advantage}
                    </span>
                  )}
                </div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
