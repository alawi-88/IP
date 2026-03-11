"use client";

interface Props {
  content: any;
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
                {section.content}
              </p>
            )}
            {section.items && Array.isArray(section.items) && (
              <ul className="mt-2 space-y-1">
                {section.items.map((item: any, j: number) => (
                  <li key={j} className="text-sm text-gray-600 flex items-start gap-2">
                    <span className="text-gray-400 mt-1.5">•</span>
                    <span>{typeof item === "string" ? item : item.text || item.name || item.title || JSON.stringify(item)}</span>
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
            {content.description || content.content || content.summary}
          </p>
        )}
        {content.key_points && Array.isArray(content.key_points) && (
          <ul className="space-y-1 mt-2">
            {content.key_points.map((point: string, i: number) => (
              <li key={i} className="text-sm text-gray-600 flex items-start gap-2">
                <span className="text-gray-400 mt-1.5">•</span>
                <span>{point}</span>
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
                      <span>{typeof item === "string" ? item : item.text || item.name || item.title || JSON.stringify(item)}</span>
                    </li>
                  ))}
                </ul>
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
