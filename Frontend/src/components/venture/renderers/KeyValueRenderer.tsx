"use client";

import React from "react";

interface KeyValueRendererProps {
  content: any;
}

export default function KeyValueRenderer({ content }: KeyValueRendererProps) {
  let pairs: Array<{ key: string; value: any }> = [];

  if (Array.isArray(content)) {
    pairs = content.map((item) => {
      if (item.key && item.value !== undefined) return { key: item.key, value: item.value };
      if (item.label && item.value !== undefined) return { key: item.label, value: item.value };
      if (item.name && item.value !== undefined) return { key: item.name, value: item.value };
      return null;
    }).filter(Boolean) as Array<{ key: string; value: any }>;
  } else if (content?.items && Array.isArray(content.items)) {
    pairs = content.items.map((item: any) => ({
      key: item.key || item.label || item.name || "",
      value: item.value ?? "",
    }));
  } else if (typeof content === "object" && content !== null) {
    pairs = Object.entries(content)
      .filter(([k]) => !["_type", "component_type", "title"].includes(k))
      .map(([key, value]) => ({ key: key.replace(/_/g, " "), value }));
  }

  if (!pairs.length) {
    return <div className="text-gray-500 dark:text-gray-400 text-sm italic">No data available.</div>;
  }

  const formatValue = (val: any): string => {
    if (val === null || val === undefined) return "—";
    if (typeof val === "boolean") return val ? "Yes" : "No";
    if (Array.isArray(val)) return val.join(", ");
    if (typeof val === "object") return JSON.stringify(val);
    return String(val);
  };

  return (
    <div className="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      {pairs.map((pair, idx) => (
        <div
          key={idx}
          className={`flex items-start gap-4 px-4 py-3 ${
            idx % 2 === 0 ? "bg-white dark:bg-gray-800" : "bg-gray-50/50 dark:bg-gray-700/50"
          }`}
        >
          <span className="text-sm font-medium text-gray-500 dark:text-gray-400 capitalize min-w-[140px] flex-shrink-0">
            {pair.key}
          </span>
          <span className="text-sm text-gray-900 dark:text-white flex-1">
            {formatValue(pair.value)}
          </span>
        </div>
      ))}
    </div>
  );
}
