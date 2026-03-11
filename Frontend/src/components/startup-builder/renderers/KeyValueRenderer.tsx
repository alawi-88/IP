"use client";

interface Props {
  content: any;
}

export default function KeyValueRenderer({ content }: Props) {
  let pairs: Array<{ key: string; value: string }> = [];

  if (Array.isArray(content)) {
    pairs = content.map((item: any) => ({
      key: item.key || item.label || item.title || item.name || "",
      value: item.value || item.description || item.text || "",
    }));
  } else if (content.pairs || content.items) {
    const items = content.pairs || content.items;
    pairs = items.map((item: any) => ({
      key: item.key || item.label || item.title || item.name || "",
      value: item.value || item.description || item.text || "",
    }));
  } else if (typeof content === "object") {
    pairs = Object.entries(content)
      .filter(([k]) => !["title", "description", "type"].includes(k))
      .map(([key, value]) => ({
        key: key.replace(/_/g, " ").replace(/-/g, " "),
        value: typeof value === "string" ? value : typeof value === "number" ? String(value) : JSON.stringify(value),
      }));
  }

  if (!pairs.length) return null;

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      {pairs.map((pair, i) => (
        <div key={i} className="bg-gray-50 rounded-lg p-4 border border-gray-100">
          <p className="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">
            {pair.key}
          </p>
          <p className="text-sm text-gray-800 font-medium">{pair.value}</p>
        </div>
      ))}
    </div>
  );
}
