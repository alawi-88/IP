"use client";

interface Props {
  content: any;
}

const techCategoryMeta: Record<string, { icon: string; color: string }> = {
  frontend: { icon: "🎨", color: "bg-blue-50 border-blue-200" },
  backend: { icon: "⚙️", color: "bg-emerald-50 border-emerald-200" },
  database: { icon: "🗄️", color: "bg-purple-50 border-purple-200" },
  apis: { icon: "🔌", color: "bg-orange-50 border-orange-200" },
  cloud: { icon: "☁️", color: "bg-cyan-50 border-cyan-200" },
  messaging: { icon: "💬", color: "bg-pink-50 border-pink-200" },
  analytics: { icon: "📊", color: "bg-indigo-50 border-indigo-200" },
  auth: { icon: "🔐", color: "bg-red-50 border-red-200" },
  infrastructure: { icon: "🏗️", color: "bg-amber-50 border-amber-200" },
  devops: { icon: "🔄", color: "bg-teal-50 border-teal-200" },
  ai_ml: { icon: "🤖", color: "bg-violet-50 border-violet-200" },
  testing: { icon: "🧪", color: "bg-lime-50 border-lime-200" },
};

export default function TechArchitectureRenderer({ content }: Props) {
  const stack = content.technology_stack || content.stack || content;
  const devCost = content.estimated_development_cost || content.cost;

  const categories: Array<{ key: string; label: string; items: string; icon: string; color: string }> = [];

  const techKeys = [
    "frontend", "backend", "database", "apis", "cloud", "messaging",
    "analytics", "auth", "infrastructure", "devops", "ai_ml", "testing",
  ];

  for (const key of techKeys) {
    const val = stack[key];
    if (val) {
      const meta = techCategoryMeta[key] || { icon: "📦", color: "bg-gray-50 border-gray-200" };
      categories.push({
        key,
        label: key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()),
        items: typeof val === "string" ? val : Array.isArray(val) ? val.join(", ") : JSON.stringify(val),
        icon: meta.icon,
        color: meta.color,
      });
    }
  }

  if (categories.length === 0 && typeof stack === "object") {
    Object.entries(stack).forEach(([key, val]) => {
      if (key === "estimated_development_cost") return;
      const meta = techCategoryMeta[key] || { icon: "📦", color: "bg-gray-50 border-gray-200" };
      categories.push({
        key,
        label: key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()),
        items: typeof val === "string" ? val : Array.isArray(val) ? (val as string[]).join(", ") : JSON.stringify(val),
        icon: meta.icon,
        color: meta.color,
      });
    });
  }

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        {categories.map((cat) => (
          <div key={cat.key} className={`${cat.color} border rounded-xl p-4`}>
            <div className="flex items-center gap-2 mb-2">
              <span className="text-lg">{cat.icon}</span>
              <p className="text-xs text-gray-500 uppercase tracking-wider font-bold">{cat.label}</p>
            </div>
            <p className="text-sm text-gray-800 font-medium">{cat.items}</p>
          </div>
        ))}
      </div>

      {devCost && (
        <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-5 text-white">
          <p className="text-xs uppercase tracking-wider text-white/70 mb-1">Estimated Development Cost</p>
          <p className="text-xl font-bold">{typeof devCost === "string" ? devCost : JSON.stringify(devCost)}</p>
        </div>
      )}
    </div>
  );
}
