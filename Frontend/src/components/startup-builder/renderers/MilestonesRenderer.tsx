"use client";

interface Props {
  content: any;
}

export default function MilestonesRenderer({ content }: Props) {
  const projections = content.projections || content.highlights || content.summary_cards || [];
  const milestones = content.milestones || content.revenue_milestones || content.monthly_milestones || [];

  return (
    <div className="space-y-6">
      {/* Projection Stats */}
      {projections.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {projections.map((p: any, i: number) => (
            <div key={i} className="bg-gray-50 rounded-xl p-4 text-center border border-gray-100">
              <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">
                {p.label || p.title || p.name}
              </p>
              <p className="text-2xl font-bold text-gray-800">
                {p.value || p.amount}
              </p>
              {p.description && (
                <p className="text-xs text-gray-400 mt-0.5">{p.description}</p>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Milestones */}
      {milestones.length > 0 && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-3">
            Monthly Revenue Milestones
          </h4>
          <div className="space-y-3">
            {milestones.map((m: any, i: number) => (
              <div key={i} className="flex items-center justify-between bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div>
                  <p className="font-semibold text-gray-800">{m.month || m.period || m.title}</p>
                  <div className="flex items-center gap-4 mt-1 text-xs text-gray-500">
                    {m.total_users && <span>Total Users: {m.total_users}</span>}
                    {m.paying && <span>Paying: {m.paying}</span>}
                    {m.conversion && <span>Conversion: {m.conversion}</span>}
                  </div>
                </div>
                {(m.revenue || m.mrr) && (
                  <span className="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm font-bold">
                    {m.revenue || m.mrr}
                  </span>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
