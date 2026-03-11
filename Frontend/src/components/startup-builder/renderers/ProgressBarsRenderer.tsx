"use client";

interface Props {
  content: any;
}

export default function ProgressBarsRenderer({ content }: Props) {
  let items: any[] = [];

  if (Array.isArray(content)) {
    items = content;
  } else if (content.items || content.categories || content.bars || content.breakdown) {
    items = content.items || content.categories || content.bars || content.breakdown;
  } else if (content.progress_bars) {
    items = content.progress_bars;
  }

  if (!items.length) return null;

  const colors = [
    "from-blue-500 to-blue-400",
    "from-emerald-500 to-emerald-400",
    "from-purple-500 to-purple-400",
    "from-orange-500 to-orange-400",
    "from-pink-500 to-pink-400",
    "from-cyan-500 to-cyan-400",
  ];

  // Sort by percentage descending
  const sorted = [...items].sort((a, b) => {
    const pA = parseFloat(a.percentage || a.value || a.percent || 0);
    const pB = parseFloat(b.percentage || b.value || b.percent || 0);
    return pB - pA;
  });

  return (
    <div className="space-y-4">
      {content.title && (
        <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-3">
          {content.title}
        </h4>
      )}
      {sorted.map((item: any, i: number) => {
        const pct = parseFloat(item.percentage || item.value || item.percent || 0);
        const amount = item.amount || item.cost || item.budget;
        return (
          <div key={i}>
            <div className="flex items-center justify-between mb-1.5">
              <div>
                <span className="text-sm font-medium text-gray-800">
                  {item.label || item.name || item.title || item.category}
                </span>
                {item.description && (
                  <p className="text-xs text-gray-400">{item.description}</p>
                )}
              </div>
              <div className="flex items-center gap-3">
                <span className="text-xs text-gray-500">{pct}%</span>
                {amount && (
                  <span className="text-sm font-bold text-gray-800">{amount}</span>
                )}
              </div>
            </div>
            <div className="h-2.5 bg-gray-100 rounded-full overflow-hidden">
              <div
                className={`h-full bg-gradient-to-r ${colors[i % colors.length]} rounded-full transition-all duration-500`}
                style={{ width: `${Math.min(pct, 100)}%` }}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
}
