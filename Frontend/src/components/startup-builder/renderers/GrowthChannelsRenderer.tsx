"use client";

interface Props {
  content: any;
}

export default function GrowthChannelsRenderer({ content }: Props) {
  const summary = content.strategy_summary || content.summary || content.description;
  const channels = content.channels || content.growth_channels || content.items || [];

  return (
    <div className="space-y-5">
      {summary && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">Strategy Summary</h4>
          <p className="text-sm text-gray-600">{summary}</p>
        </div>
      )}

      {channels.length > 0 && (
        <div className="space-y-3">
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider">Growth Channels</h4>
          {channels.map((ch: any, i: number) => (
            <div key={i} className="bg-gray-50 rounded-xl p-4 border border-gray-100">
              <div className="flex items-start justify-between mb-2">
                <h5 className="font-semibold text-gray-800">{ch.name || ch.title || ch.channel}</h5>
                <div className="flex items-center gap-2">
                  {ch.new_users_percentage && (
                    <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                      {ch.new_users_percentage} of new users
                    </span>
                  )}
                  {ch.cac && (
                    <span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                      {ch.cac} CAC
                    </span>
                  )}
                </div>
              </div>
              {ch.description && (
                <p className="text-sm text-gray-600">{ch.description}</p>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
