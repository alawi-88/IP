"use client";

interface Props {
  content: any;
}

export default function RiskMatrixRenderer({ content }: Props) {
  const risks = content.risks || content.items || (Array.isArray(content) ? content : []);

  const severityColors: Record<string, { bg: string; text: string; border: string }> = {
    high: { bg: "bg-red-100", text: "text-red-700", border: "border-red-200" },
    critical: { bg: "bg-red-100", text: "text-red-700", border: "border-red-200" },
    medium: { bg: "bg-yellow-100", text: "text-yellow-700", border: "border-yellow-200" },
    low: { bg: "bg-green-100", text: "text-green-700", border: "border-green-200" },
  };

  return (
    <div className="space-y-3">
      {risks.map((risk: any, i: number) => {
        const severity = (risk.severity || risk.level || risk.impact || "medium").toLowerCase();
        const colors = severityColors[severity] || severityColors.medium;
        return (
          <div key={i} className={`rounded-xl border ${colors.border} ${colors.bg} p-4`}>
            <div className="flex items-start justify-between gap-4">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <h4 className="font-semibold text-gray-800 text-sm">
                    {risk.risk || risk.title || risk.name}
                  </h4>
                  <span className={`px-2 py-0.5 rounded-full text-xs font-bold uppercase ${colors.bg} ${colors.text}`}>
                    {severity}
                  </span>
                </div>
                {risk.description && (
                  <p className="text-xs text-gray-600 mt-1">{risk.description}</p>
                )}
                {(risk.mitigation || risk.mitigation_strategy) && (
                  <p className="text-xs text-gray-500 mt-2">
                    <span className="font-medium">Mitigation:</span>{" "}
                    {risk.mitigation || risk.mitigation_strategy}
                  </p>
                )}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
