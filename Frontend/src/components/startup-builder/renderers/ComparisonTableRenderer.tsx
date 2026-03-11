"use client";

interface Props {
  content: any;
}

export default function ComparisonTableRenderer({ content }: Props) {
  const headers = content.headers || content.columns || [];
  const rows = content.rows || content.data || content.items || [];

  if ((!headers.length && !rows.length) || !Array.isArray(rows)) return null;

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200">
      <table className="w-full text-sm">
        <thead>
          <tr className="bg-gray-50">
            {headers.map((h: any, i: number) => (
              <th
                key={i}
                className={`px-4 py-3.5 text-left font-bold text-xs uppercase tracking-wider ${
                  i === 1 ? "text-[#25935F] bg-emerald-50/50" : "text-gray-500"
                } ${i === 0 ? "rounded-tl-xl" : ""} ${i === headers.length - 1 ? "rounded-tr-xl" : ""}`}
              >
                {typeof h === "string" ? h : h.label || h.name}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {rows.map((row: any, i: number) => {
            const cells = Array.isArray(row) ? row : row.cells || row.values || Object.values(row);
            return (
              <tr key={i} className="hover:bg-gray-50/50 transition-colors">
                {cells.map((cell: any, j: number) => (
                  <td key={j} className={`px-4 py-3.5 ${j === 0 ? "font-medium text-gray-800" : "text-gray-600"} ${j === 1 ? "bg-emerald-50/30" : ""}`}>
                    {renderCell(cell)}
                  </td>
                ))}
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

function renderCell(cell: any) {
  if (typeof cell === "boolean" || cell === "true" || cell === "false") {
    const val = cell === true || cell === "true";
    return val ? (
      <span className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
        <span className="text-green-600 font-bold text-sm">✓</span>
      </span>
    ) : (
      <span className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
        <span className="text-gray-400 text-sm">✗</span>
      </span>
    );
  }
  if (typeof cell === "string") return cell;
  if (typeof cell === "number") return String(cell);
  if (cell && typeof cell === "object") {
    return cell.value || cell.text || cell.label || JSON.stringify(cell);
  }
  return String(cell || "");
}
