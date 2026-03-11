"use client";

interface Props {
  content: any;
}

/** Helper to render an individual item that could be string or object */
function renderItem(item: any): string {
  if (typeof item === "string") return item;
  if (!item || typeof item !== "object") return String(item ?? "");

  // key-value pair: "Backend: Laravel 10"
  if (item.key && item.value) return `${item.key}: ${item.value}`;
  // label-value pair: "Thought Leadership: 25%"
  if (item.label && item.value) {
    const desc = item.description ? ` — ${item.description}` : "";
    return `${item.label}: ${item.value}${desc}`;
  }
  // Common text fields
  return item.text || item.name || item.title || item.description || JSON.stringify(item);
}

export default function TextContentRenderer({ content }: Props) {
  if (!content) return null;

  // Handle string content
  if (typeof content === "string") {
    return (
      <div className="prose prose-sm max-w-none text-gray-700">
        {content.split("\n").map((p: string, i: number) => (
          <p key={i} className="mb-3 leading-relaxed">{p}</p>
        ))}
      </div>
    );
  }

  // Handle array content directly
  if (Array.isArray(content)) {
    return (
      <ul className="space-y-1.5">
        {content.map((item: any, i: number) => (
          <li key={i} className="text-sm text-gray-600 flex items-start gap-2">
            <span className="text-gray-400 mt-1.5">•</span>
            <span>{renderItem(item)}</span>
          </li>
        ))}
      </ul>
    );
  }

  // Handle sections array
  if (content.sections && Array.isArray(content.sections)) {
    return (
      <div className="space-y-5">
        {content.sections.map((section: any, i: number) => (
          <div key={i}>
            {section.title && (
              <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">
                {section.title}
              </h4>
            )}
            {section.content && (
              <p className="text-sm text-gray-600 leading-relaxed">
                {typeof section.content === "string" ? section.content : renderItem(section.content)}
              </p>
            )}
            {section.items && Array.isArray(section.items) && (
              <ul className="mt-2 space-y-1">
                {section.items.map((item: any, j: number) => (
                  <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                    <span className="text-gray-400 mt-1.5">•</span>
                    <span>{renderItem(item)}</span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        ))}
      </div>
    );
  }

  // Handle object with title/content/description
  if (content.title || content.description || content.content || content.summary) {
    return (
      <div className="space-y-3">
        {content.title && (
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider">
            {content.title}
          </h4>
        )}
        {(content.description || content.content || content.summary) && (
          <p className="text-sm text-gray-600 leading-relaxed">
            {typeof (content.description || content.content || content.summary) === "string"
              ? (content.description || content.content || content.summary)
              : renderItem(content.description || content.content || content.summary)}
          </p>
        )}
        {content.key_points && Array.isArray(content.key_points) && (
          <ul className="space-y-1 mt-2">
            {content.key_points.map((point: string, i: number) => (
              <li key={i} className="text-sm text-gray-600 flex items-start gap-2">
                <span className="text-gray-400 mt-1.5">•</span>
                <span>{renderItem(point)}</span>
              </li>
            ))}
          </ul>
        )}
      </div>
    );
  }

  // Handle plain object - render key-values
  if (typeof content === "object" && !Array.isArray(content)) {
    return (
      <div className="space-y-4">
        {Object.entries(content).map(([key, value]: [string, any]) => {
          if (typeof value === "string") {
            return (
              <div key={key}>
                <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-1">
                  {key.replace(/_/g, " ").replace(/-/g, " ")}
                </h4>
                <p className="text-sm text-gray-600 leading-relaxed">{value}</p>
              </div>
            );
          }
          if (typeof value === "number") {
            return (
              <div key={key}>
                <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-1">
                  {key.replace(/_/g, " ").replace(/-/g, " ")}
                </h4>
                <p className="text-sm text-gray-600 leading-relaxed">{value}</p>
              </div>
            );
          }
          if (Array.isArray(value)) {
            return (
              <div key={key}>
                <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">
                  {key.replace(/_/g, " ").replace(/-/g, " ")}
                </h4>
                <ul className="space-y-1">
                  {value.map((item: any, i: number) => (
                    <li key={i} className="text-sm text-gray-600 flex items-start gap-2">
                      <span className="text-gray-400 mt-1.5">•</span>
                      <span>{renderItem(item)}</span>
                    </li>
                  ))}
                </ul>
              </div>
            );
          }
          if (typeof value === "object" && value !== null) {
            return (
              <div key={key}>
                <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">
                  {key.replace(/_/g, " ").replace(/-/g, " ")}
                </h4>
                <div className="pl-3 border-l-2 border-gray-200 space-y-1">
                  {Object.entries(value).map(([subKey, subVal]: [string, any]) => (
                    <p key={subKey} className="text-sm text-gray-600">
                      <span className="font-medium text-gray-700">{subKey.replace(/_/g, " ")}: </span>
                      {typeof subVal === "string" ? subVal : Array.isArray(subVal) ? subVal.map(renderItem).join(", ") : JSON.stringify(subVal)}
                    </p>
                  ))}
                </div>
              </div>
            );
          }
          return null;
        })}
      </div>
    );
  }

  return <pre className="text-xs text-gray-500 whitespace-pre-wrap">{JSON.stringify(content, null, 2)}</pre>;
}
