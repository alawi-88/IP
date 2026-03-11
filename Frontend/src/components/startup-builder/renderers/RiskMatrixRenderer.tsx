"use client";

interface Props {
  content: any;
}

export default function RiskMatrixRenderer({ content }: Props) {
  const risks = content.risks || content.items || (Array.isArray(content) ? content : []);

  const severityConfig: Record<string, { bg: string; text: string; border: string; badge: string; icon: string }> = {
    high: { bg: "bg-red-50", text: "text-red-700", border: "border-red-200", badge: "bg-red-100 text-red-700", icon: "🔴" },
    critical: { bg: "bg-red-50", text: "text-red-700", border: "border-red-200", badge: "bg-red-100 text-red-700", icon: "🔴" },
    medium: { bg: "bg-amber-50", text: "text-amber-700", border: "border-amber-200", badge: "bg-amber-100 text-amber-700", icon: "🟡" },
    low: { bg: "bg-green-50", text: "text-green-700", border: "border-green-200", badge: "bg-green-100 text-green-700", icon: "🟢" },
  };

  const defaultConfig = severityConfig.medium;

  return (
    <div className="space-y-3">
      {risks.map((risk: any, i: number) => {
        const severity = (risk.severity || risk.level || risk.impact || "medium").toLowerCase();
        const config = severityConfig[severity] || defaultConfig;
        return (
          <div key={i} className={`rounded-xl border ${config.border} ${config.bg} overflow-hidden`}>
            <div className="p-5">
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-2">
                    <span>{config.icon}</span>
                    <h4 className="font-bold text-gray-800">
                      {risk.risk || risk.title || risk.name}
                    </h4>
                    <span className={`px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase ${config.badge}`}>
                      {severity}
                    </span>
                  </div>
                  {risk.description && (
                    <p className="text-sm text-gray-600 leading-relaxed">{risk.description}</p>
                  )}
                </div>
              </div>
              {(risk.mitigation || risk.mitigation_strategy) && (
                <div className="mt-3 bg-white/60 rounded-lg p-3">
                  <p className="text-xs text-gray-600">
                    <span className="font-bold text-gray-700">🛡️ Mitigation:</span>{" "}
                    {risk.mitigation || risk.mitigation_strategy}
                  </p>
                </div>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}
