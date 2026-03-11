"use client";

interface Props {
  content: any;
}

export default function MvpDefinitionRenderer({ content }: Props) {
  const coreConcept = content.core_concept || content.description || content.summary;
  const features = content.must_have_features || content.features || content.items || [];
  const successCriteria = content.success_criteria || content.metrics;

  const priorityColors: Record<string, string> = {
    high: "bg-red-100 text-red-700",
    critical: "bg-red-100 text-red-700",
    medium: "bg-amber-100 text-amber-700",
    low: "bg-green-100 text-green-700",
    p0: "bg-red-100 text-red-700",
    p1: "bg-orange-100 text-orange-700",
    p2: "bg-amber-100 text-amber-700",
  };

  return (
    <div className="space-y-5">
      {coreConcept && (
        <div className="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-5 border border-emerald-200">
          <h4 className="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-2">Core Concept</h4>
          <p className="text-sm text-gray-700 leading-relaxed">{coreConcept}</p>
        </div>
      )}

      {features.length > 0 && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-3 flex items-center gap-2">
            <span>🎯</span> Must-Have Features
            <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">{features.length}</span>
          </h4>
          <div className="space-y-2">
            {features.map((f: any, i: number) => {
              const priority = (f.priority || "").toLowerCase();
              const priorityClass = priorityColors[priority] || "bg-gray-100 text-gray-600";
              return (
                <div key={i} className="flex items-start gap-3 bg-gray-50 rounded-xl p-4 border border-gray-100 hover:border-gray-200 transition-colors">
                  <div className="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center text-xs font-bold text-emerald-700 flex-shrink-0">
                    {i + 1}
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <h5 className="font-semibold text-gray-800 text-sm">{f.name || f.title || f.feature}</h5>
                      {f.priority && (
                        <span className={`px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase ${priorityClass}`}>
                          {f.priority}
                        </span>
                      )}
                    </div>
                    {f.description && (
                      <p className="text-xs text-gray-500 mt-1 leading-relaxed">{f.description}</p>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {successCriteria && (
        <div className="bg-blue-50 rounded-xl p-5 border border-blue-200">
          <h4 className="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2">Success Criteria</h4>
          {typeof successCriteria === "string" ? (
            <p className="text-sm text-gray-700 leading-relaxed">{successCriteria}</p>
          ) : Array.isArray(successCriteria) ? (
            <ul className="space-y-1.5">
              {successCriteria.map((c: any, i: number) => (
                <li key={i} className="text-sm text-gray-600 flex items-start gap-2">
                  <span className="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 flex-shrink-0" />
                  <span>{typeof c === "string" ? c : c.text || c.name || JSON.stringify(c)}</span>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-gray-700">{JSON.stringify(successCriteria)}</p>
          )}
        </div>
      )}
    </div>
  );
}
