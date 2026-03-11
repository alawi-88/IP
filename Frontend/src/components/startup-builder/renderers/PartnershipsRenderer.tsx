"use client";

interface Props {
  content: any;
}

export default function PartnershipsRenderer({ content }: Props) {
  const partnerships = content.partnerships || content.items || (Array.isArray(content) ? content : []);

  const typeColors: Record<string, string> = {
    strategic: "bg-blue-100 text-blue-700",
    distribution: "bg-purple-100 text-purple-700",
    endorsement: "bg-emerald-100 text-emerald-700",
    investment: "bg-orange-100 text-orange-700",
    technology: "bg-pink-100 text-pink-700",
    content: "bg-cyan-100 text-cyan-700",
  };

  return (
    <div className="space-y-3">
      {partnerships.map((p: any, i: number) => {
        const type = (p.type || p.category || "").toLowerCase();
        const badgeClass = typeColors[type] || "bg-gray-100 text-gray-700";
        return (
          <div key={i} className="bg-gray-50 rounded-xl p-4 border border-gray-100">
            <div className="flex items-center gap-2 mb-1">
              <h4 className="font-semibold text-gray-800">
                {p.name || p.title || p.partner}
              </h4>
              {type && (
                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}`}>
                  {p.type || p.category}
                </span>
              )}
            </div>
            {p.description && (
              <p className="text-sm text-gray-600">{p.description}</p>
            )}
            {p.value && (
              <p className="text-xs text-gray-500 mt-1">{p.value}</p>
            )}
          </div>
        );
      })}
    </div>
  );
}
