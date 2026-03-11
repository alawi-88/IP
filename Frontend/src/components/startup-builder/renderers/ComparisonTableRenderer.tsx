"use client";

interface Props {
  content: any;
}

export default function ComparisonTableRenderer({ content }: Props) {
  const headers = content.headers || content.columns || [];
  const rows = content.rows || content.data || content.items || [];

  if ((!headers.length && !rows.length) || !Array.isArray(rows)) return null;

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b-2 border-gray-200">
            {headers.map((h: any, i: number) => (
              <th
                key={i}
                className={`px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs ${i === 1 ? "text-[#25935F] font-bold" : ""}`}
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
              <tr key={i} className="hover:bg-gray-50 transition-colors">
                {cells.map((cell: any, j: number) => (
                  <td key={j} className="px-4 py-3 text-gray-700">
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
      <span className="text-green-500 font-bold">✓</span>
    ) : (
      <span className="text-gray-300">✗</span>
    );
  }
  if (typeof cell === "string") return cell;
  if (typeof cell === "number") return String(cell);
  if (cell && typeof cell === "object") {
    return cell.value || cell.text || cell.label || JSON.stringify(cell);
  }
  return String(cell || "");
}
