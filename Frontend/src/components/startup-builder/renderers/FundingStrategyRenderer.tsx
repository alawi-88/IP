"use client";

interface Props {
  content: any;
}

export default function FundingStrategyRenderer({ content }: Props) {
  const rounds = content.rounds || content.stages || content.funding_rounds || content.items || (Array.isArray(content) ? content : []);

  return (
    <div className="space-y-4">
      {rounds.map((round: any, i: number) => (
        <div key={i} className="bg-gray-50 rounded-xl p-5 border border-gray-100">
          <div className="flex items-center gap-3 mb-2">
            <h4 className="text-base font-bold text-gray-800">
              {round.name || round.stage || round.round || round.title}
            </h4>
            {round.amount && (
              <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                {round.amount}
              </span>
            )}
            {round.timeline && (
              <span className="px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs">
                {round.timeline}
              </span>
            )}
          </div>
          {round.use_of_funds && (
            <p className="text-sm text-gray-600 mb-1">
              <span className="font-medium">Use of Funds:</span> {round.use_of_funds}
            </p>
          )}
          {round.target_sources && (
            <p className="text-sm text-gray-500">
              <span className="font-medium">Target Sources:</span> {round.target_sources}
            </p>
          )}
          {round.description && (
            <p className="text-sm text-gray-600">{round.description}</p>
          )}
        </div>
      ))}
    </div>
  );
}
