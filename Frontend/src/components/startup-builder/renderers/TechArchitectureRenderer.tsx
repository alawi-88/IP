"use client";

interface Props {
  content: any;
}

export default function TechArchitectureRenderer({ content }: Props) {
  const stack = content.technology_stack || content.stack || content;
  const devCost = content.estimated_development_cost || content.cost;

  // Extract tech categories
  const categories: Array<{ label: string; items: string }> = [];

  const techKeys = [
    { key: "frontend", label: "Frontend" },
    { key: "backend", label: "Backend" },
    { key: "database", label: "Database" },
    { key: "apis", label: "APIs" },
    { key: "cloud", label: "Cloud" },
    { key: "messaging", label: "Messaging" },
    { key: "analytics", label: "Analytics" },
    { key: "auth", label: "Auth" },
    { key: "infrastructure", label: "Infrastructure" },
    { key: "devops", label: "DevOps" },
    { key: "ai_ml", label: "AI/ML" },
    { key: "testing", label: "Testing" },
  ];

  for (const { key, label } of techKeys) {
    const val = stack[key];
    if (val) {
      categories.push({
        label,
        items: typeof val === "string" ? val : Array.isArray(val) ? val.join(", ") : JSON.stringify(val),
      });
    }
  }

  // If no known keys, try all object keys
  if (categories.length === 0 && typeof stack === "object") {
    Object.entries(stack).forEach(([key, val]) => {
      if (key === "estimated_development_cost") return;
      categories.push({
        label: key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()),
        items: typeof val === "string" ? val : Array.isArray(val) ? val.join(", ") : JSON.stringify(val),
      });
    });
  }

  return (
    <div className="space-y-5">
      <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider">Technology Stack</h4>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {categories.map((cat, i) => (
          <div key={i} className="bg-gray-50 rounded-lg p-4 border border-gray-100">
            <p className="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">
              {cat.label}
            </p>
            <p className="text-sm text-gray-800">{cat.items}</p>
          </div>
        ))}
      </div>

      {devCost && (
        <div className="bg-blue-50 rounded-xl p-4 border border-blue-200">
          <h4 className="text-sm font-semibold text-blue-800 uppercase tracking-wider mb-1">
            Estimated Development Cost
          </h4>
          <p className="text-sm text-blue-600">{typeof devCost === "string" ? devCost : JSON.stringify(devCost)}</p>
        </div>
      )}
    </div>
  );
}
