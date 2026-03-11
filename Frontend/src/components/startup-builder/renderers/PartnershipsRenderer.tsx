"use client";

interface Props {
  content: any;
}

const typeColors: Record<string, { bg: string; text: string; icon: string }> = {
  strategic: { bg: "bg-blue-50 border-blue-200", text: "bg-blue-100 text-blue-700", icon: "🎯" },
  distribution: { bg: "bg-purple-50 border-purple-200", text: "bg-purple-100 text-purple-700", icon: "📦" },
  endorsement: { bg: "bg-emerald-50 border-emerald-200", text: "bg-emerald-100 text-emerald-700", icon: "⭐" },
  investment: { bg: "bg-orange-50 border-orange-200", text: "bg-orange-100 text-orange-700", icon: "💰" },
  technology: { bg: "bg-pink-50 border-pink-200", text: "bg-pink-100 text-pink-700", icon: "💻" },
  content: { bg: "bg-cyan-50 border-cyan-200", text: "bg-cyan-100 text-cyan-700", icon: "📝" },
  government: { bg: "bg-indigo-50 border-indigo-200", text: "bg-indigo-100 text-indigo-700", icon: "🏛️" },
};

const defaultPartnerStyle = { bg: "bg-gray-50 border-gray-200", text: "bg-gray-100 text-gray-700", icon: "🤝" };

export default function PartnershipsRenderer({ content }: Props) {
  const partnerships = content.partnerships || content.items || (Array.isArray(content) ? content : []);

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {partnerships.map((p: any, i: number) => {
        const type = (p.type || p.category || "").toLowerCase();
        const style = typeColors[type] || defaultPartnerStyle;
        return (
          <div key={i} className={`${style.bg} border rounded-xl p-5`}>
            <div className="flex items-start gap-3">
              <span className="text-2xl">{style.icon}</span>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <h4 className="font-bold text-gray-800">
                    {p.name || p.title || p.partner}
                  </h4>
                  {type && (
                    <span className={`px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase ${style.text}`}>
                      {p.type || p.category}
                    </span>
                  )}
                </div>
                {p.description && (
                  <p className="text-sm text-gray-600 leading-relaxed">{p.description}</p>
                )}
                {p.value && (
                  <p className="text-xs text-gray-500 mt-2 font-medium">{p.value}</p>
                )}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
