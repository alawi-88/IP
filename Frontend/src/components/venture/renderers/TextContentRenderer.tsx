"use client";

import React from "react";

interface TextContentRendererProps {
  content: any;
}

/**
 * TextContentRenderer
 * Generic recursive JSON-to-prose renderer.
 * - Handles strings directly
 * - Renders arrays as lists/paragraphs
 * - Renders objects by showing key-value pairs with keys as headings
 */
export default function TextContentRenderer({ content }: TextContentRendererProps) {
  const renderValue = (value: any, key?: string, depth: number = 0): React.ReactNode => {
    if (value === null || value === undefined) return null;

    if (typeof value === "string") {
      if (value.includes("\n")) {
        return (
          <div className="whitespace-pre-wrap break-words text-gray-700 dark:text-gray-300 leading-relaxed">
            {value}
          </div>
        );
      }
      return (
        <p className="text-gray-700 dark:text-gray-300 leading-relaxed break-words">
          {value}
        </p>
      );
    }

    if (typeof value === "number" || typeof value === "boolean") {
      return (
        <span className="font-semibold text-gray-900 dark:text-white">
          {String(value)}
        </span>
      );
    }

    if (Array.isArray(value)) {
      return (
        <div className="space-y-2 ml-4">
          {value.map((item, idx) => {
            if (
              typeof item === "string" ||
              typeof item === "number" ||
              typeof item === "boolean"
            ) {
              return (
                <div key={idx} className="flex items-start gap-3">
                  <span
                    className="text-base flex-shrink-0 mt-1"
                    style={{ color: "var(--dga-primary-500, #3B82F6)" }}
                  >
                    •
                  </span>
                  <span className="text-gray-700 dark:text-gray-300">
                    {String(item)}
                  </span>
                </div>
              );
            }
            return (
              <div key={idx} className="mb-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                {renderValue(item, undefined, depth + 1)}
              </div>
            );
          })}
        </div>
      );
    }

    if (typeof value === "object") {
      const entries = Object.entries(value);
      if (entries.length === 0) {
        return <p className="text-gray-500 dark:text-gray-400 italic">(Empty)</p>;
      }

      return (
        <div className={`space-y-4 ${depth > 0 ? "ml-4" : ""}`}>
          {entries.map(([objKey, objValue]) => {
            const isHeading = depth < 2 && typeof objValue !== "string";
            return (
              <div key={objKey}>
                {isHeading ? (
                  <h4 className="font-semibold text-gray-900 dark:text-white mb-2 text-sm uppercase tracking-wide">
                    {formatLabel(objKey)}
                  </h4>
                ) : (
                  <span className="font-medium text-gray-800 dark:text-gray-200">
                    {formatLabel(objKey)}:{" "}
                  </span>
                )}
                <div
                  className={isHeading ? "border-l-4 pl-4 py-2" : "inline"}
                  style={isHeading ? { borderColor: "var(--dga-primary-500, #3B82F6)" } : {}}
                >
                  {renderValue(objValue, objKey, depth + 1)}
                </div>
              </div>
            );
          })}
        </div>
      );
    }

    return <span className="text-gray-700 dark:text-gray-300">{String(value)}</span>;
  };

  const formatLabel = (key: string): string => {
    return key
      .replace(/_/g, " ")
      .replace(/([A-Z])/g, " $1")
      .replace(/\b\w/g, (char) => char.toUpperCase())
      .trim();
  };

  return (
    <div className="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
      {renderValue(content, undefined, 0)}
    </div>
  );
}
