"use client";

import React from "react";

interface ComparisonTableRendererProps {
  content: any;
}

export default function ComparisonTableRenderer({ content }: ComparisonTableRendererProps) {
  let headers: string[] = [];
  let rows: string[][] = [];

  if (content?.headers && content?.rows) {
    headers = content.headers;
    // Handle rows as array of objects with 'values' key (VRIO format)
    // OR as flat arrays (standard format)
    rows = content.rows.map((row: any) => {
      if (Array.isArray(row)) {
        return row.map((cell: any) => String(cell ?? ""));
      }
      if (row && typeof row === "object" && Array.isArray(row.values)) {
        return row.values.map((cell: any) => String(cell ?? ""));
      }
      // Single object - extract values
      if (row && typeof row === "object") {
        return headers.map((h: string) => String(row[h] ?? row[h.toLowerCase()] ?? ""));
      }
      return [String(row ?? "")];
    });
  } else if (content?.features && Array.isArray(content.features)) {
    const featureList = content.features;
    if (featureList.length > 0) {
      headers = Object.keys(featureList[0]);
      rows = featureList.map((f: Record<string, any>) =>
        headers.map((h) => String(f[h] ?? ""))
      );
    }
  } else if (Array.isArray(content) && content.length > 0) {
    // Array of objects
    if (typeof content[0] === "object" && !Array.isArray(content[0])) {
      headers = Object.keys(content[0]);
      rows = content.map((item: Record<string, any>) =>
        headers.map((h) => String(item[h] ?? ""))
      );
    }
    // Array of arrays (first row as headers)
    else if (Array.isArray(content[0])) {
      headers = content[0].map((h: any) => String(h));
      rows = content.slice(1).map((row: any[]) =>
        row.map((cell: any) => String(cell ?? ""))
      );
    }
  }

  if (!headers.length || !rows.length) {
    return (
      <div className="text-gray-500 dark:text-gray-400 text-sm italic">No comparison data available.</div>
    );
  }

  const renderCellValue = (val: string) => {
    const lower = val.toLowerCase();
    if (lower === "true" || lower === "yes" || val === "✓" || val === "✅") {
      return (
        <svg className="w-5 h-5 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      );
    }
    if (lower === "false" || lower === "no" || val === "✗" || val === "❌") {
      return (
        <svg className="w-5 h-5 mx-auto text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      );
    }
    // Handle Medium, Developing, etc. with amber color
    if (lower === "medium" || lower === "developing" || lower === "partial" || lower === "limited" || lower === "some" || lower === "basic") {
      return (
        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
          {val}
        </span>
      );
    }
    return <span>{val}</span>;
  };

  return (
    <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
      <table className="w-full text-sm">
        <thead>
          <tr>
            {headers.map((header, hIdx) => (
              <th
                key={hIdx}
                className={`px-4 py-3 text-left font-semibold text-xs uppercase tracking-wider ${
                  hIdx === 0
                    ? "text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700"
                    : hIdx === 1
                    ? "text-white"
                    : "text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700"
                }`}
                style={
                  hIdx === 1
                    ? { backgroundColor: "var(--dga-primary-500, #3B82F6)" }
                    : undefined
                }
              >
                {header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row, rIdx) => (
            <tr
              key={rIdx}
              className={rIdx % 2 === 0 ? "bg-white dark:bg-gray-800" : "bg-gray-50/50 dark:bg-gray-700/50"}
            >
              {row.map((cell, cIdx) => (
                <td
                  key={cIdx}
                  className={`px-4 py-3 ${
                    cIdx === 0
                      ? "font-medium text-gray-900 dark:text-white"
                      : "text-gray-600 dark:text-gray-400 text-center"
                  }`}
                >
                  {renderCellValue(cell)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}