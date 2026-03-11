"use client";

interface Props {
  content: any;
}

export default function DifferentiatorsRenderer({ content }: Props) {
  const items = content.differentiators || content.items || (Array.isArray(content) ? content : []);

  return (
    <div className="space-y-4">
      {items.map((item: any, i: number) => (
        <div key={i} className="bg-gray-50 rounded-xl p-5 border border-gray-100">
          <h4 className="font-bold text-gray-800 mb-2">
            {item.title || item.name || item.feature}
          </h4>
          {item.description && (
            <p className="text-sm text-gray-600 mb-2">{item.description}</p>
          )}
          {item.metric && (
            <span className="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
              ↑ {item.metric}
            </span>
          )}
          {item.impact && (
            <span className="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
              ↑ {item.impact}
            </span>
          )}
        </div>
      ))}
    </div>
  );
}
