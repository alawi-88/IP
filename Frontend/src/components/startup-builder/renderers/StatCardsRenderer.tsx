"use client";

interface Props {
  content: any;
}

export default function StatCardsRenderer({ content }: Props) {
  const cards = Array.isArray(content)
    ? content
    : content?.cards || content?.stats || content?.items || content?.metrics ||
      (typeof content === "object" ? Object.entries(content).filter(([k]) => !["title", "description"].includes(k)).map(([k, v]: [string, any]) => ({
        title: k.replace(/_/g, " "),
        value: typeof v === "object" ? v.value || v.amount || JSON.stringify(v) : v,
        description: typeof v === "object" ? v.description || v.subtitle : undefined,
        trend: typeof v === "object" ? v.trend : undefined,
      })) : []);

  if (!cards || cards.length === 0) return null;

  const gradients = [
    "from-blue-500 to-blue-600",
    "from-emerald-500 to-emerald-600",
    "from-purple-500 to-purple-600",
    "from-orange-500 to-orange-600",
    "from-pink-500 to-pink-600",
    "from-cyan-500 to-cyan-600",
  ];

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      {cards.map((card: any, i: number) => (
        <div
          key={i}
          className={`bg-gradient-to-br ${gradients[i % gradients.length]} rounded-xl p-4 text-white`}
        >
          <p className="text-xs text-white/70 uppercase tracking-wider font-medium mb-1">
            {card.title || card.label || card.name}
          </p>
          <p className="text-2xl font-bold">
            {card.value || card.amount || card.metric}
          </p>
          {(card.description || card.subtitle || card.trend) && (
            <p className="text-xs text-white/60 mt-1">
              {card.description || card.subtitle || card.trend}
            </p>
          )}
        </div>
      ))}
    </div>
  );
}
