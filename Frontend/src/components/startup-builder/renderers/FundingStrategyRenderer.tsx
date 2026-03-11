"use client";

interface Props {
  content: any;
}

const roundColors = [
  { bg: "bg-blue-50", border: "border-blue-200", badge: "bg-blue-100 text-blue-700", icon: "🌱" },
  { bg: "bg-emerald-50", border: "border-emerald-200", badge: "bg-emerald-100 text-emerald-700", icon: "🌿" },
  { bg: "bg-purple-50", border: "border-purple-200", badge: "bg-purple-100 text-purple-700", icon: "🚀" },
  { bg: "bg-orange-50", border: "border-orange-200", badge: "bg-orange-100 text-orange-700", icon: "🏢" },
];

export default function FundingStrategyRenderer({ content }: Props) {
  const rounds = content.rounds || content.stages || content.funding_rounds || content.items || (Array.isArray(content) ? content : []);
  const totalFunding = content.total_funding || content.total;

  return (
    <div className="space-y-4">
      {totalFunding && (
        <div className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-5 text-white">
          <p className="text-xs uppercase tracking-wider text-white/70 mb-1">Total Funding Target</p>
          <p className="text-2xl font-bold">{totalFunding}</p>
        </div>
      )}

      <div className="relative">
        {rounds.map((round: any, i: number) => {
          const color = roundColors[i % roundColors.length];
          return (
            <div key={i} className="flex gap-4 mb-4 last:mb-0">
              <div className="flex flex-col items-center pt-1">
                <div className={`w-8 h-8 rounded-full ${color.bg} ${color.border} border-2 flex items-center justify-center text-sm`}>
                  {color.icon}
                </div>
                {i < rounds.length - 1 && <div className="w-0.5 flex-1 bg-gray-200 my-1" />}
              </div>
              <div className={`flex-1 ${color.bg} ${color.border} border rounded-xl p-5`}>
                <div className="flex items-center flex-wrap gap-2 mb-2">
                  <h4 className="text-base font-bold text-gray-800">
                    {round.name || round.stage || round.round || round.title}
                  </h4>
                  {round.amount && (
                    <span className={`px-2.5 py-1 rounded-lg text-xs font-bold ${color.badge}`}>{round.amount}</span>
                  )}
                  {round.timeline && (
                    <span className="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">{round.timeline}</span>
                  )}
                </div>
                {round.use_of_funds && (
                  <p className="text-sm text-gray-600 mb-1">
                    <span className="font-medium text-gray-700">Use of Funds:</span> {round.use_of_funds}
                  </p>
                )}
                {round.target_sources && (
                  <p className="text-sm text-gray-500">
                    <span className="font-medium text-gray-600">Target Sources:</span> {round.target_sources}
                  </p>
                )}
                {round.description && !round.use_of_funds && (
                  <p className="text-sm text-gray-600">{round.description}</p>
                )}
                {round.valuation && (
                  <p className="text-xs text-gray-500 mt-2"><span className="font-medium">Valuation:</span> {round.valuation}</p>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
