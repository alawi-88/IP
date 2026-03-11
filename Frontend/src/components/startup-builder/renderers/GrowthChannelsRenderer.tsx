"use client";

interface Props {
  content: any;
}

const channelIcons: Record<string, string> = {
  "content marketing": "📝", seo: "🔍", "social media": "📱",
  partnerships: "🤝", referral: "📣", "paid advertising": "💰",
  email: "✉️", events: "🎪", pr: "📰", community: "👥",
  "direct sales": "🎯", influencer: "⭐",
};

function getChannelIcon(name: string): string {
  const lower = (name || "").toLowerCase();
  for (const [key, icon] of Object.entries(channelIcons)) {
    if (lower.includes(key)) return icon;
  }
  return "📈";
}

function parsePercentage(val: string | number | undefined): number | null {
  if (val === undefined || val === null) return null;
  const str = String(val).replace(/[^0-9.]/g, "");
  const num = parseFloat(str);
  return isNaN(num) ? null : num;
}

const barColors = [
  "bg-emerald-500", "bg-blue-500", "bg-purple-500",
  "bg-orange-500", "bg-pink-500", "bg-cyan-500",
];

export default function GrowthChannelsRenderer({ content }: Props) {
  const summary = content.strategy_summary || content.summary || content.description;
  const channels = content.channels || content.growth_channels || content.items || [];

  return (
    <div className="space-y-5">
      {summary && (
        <div className="bg-gray-50 rounded-xl p-4 border border-gray-100">
          <p className="text-sm text-gray-600 leading-relaxed">{summary}</p>
        </div>
      )}

      {channels.length > 0 && (
        <div className="space-y-3">
          {channels.map((ch: any, i: number) => {
            const pct = parsePercentage(ch.new_users_percentage || ch.percentage || ch.share);
            const color = barColors[i % barColors.length];
            return (
              <div key={i} className="bg-white rounded-xl p-5 border border-gray-100 hover:border-gray-200 transition-colors">
                <div className="flex items-start justify-between mb-2">
                  <div className="flex items-center gap-3">
                    <span className="text-xl">{getChannelIcon(ch.name || ch.title || ch.channel || "")}</span>
                    <div>
                      <h5 className="font-semibold text-gray-800">{ch.name || ch.title || ch.channel}</h5>
                      {ch.description && <p className="text-sm text-gray-500 mt-0.5">{ch.description}</p>}
                    </div>
                  </div>
                  <div className="flex items-center gap-2 flex-shrink-0">
                    {ch.new_users_percentage && (
                      <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-200">
                        {ch.new_users_percentage}
                      </span>
                    )}
                    {ch.cac && (
                      <span className="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold border border-blue-200">
                        CAC: {ch.cac}
                      </span>
                    )}
                  </div>
                </div>
                {pct !== null && (
                  <div className="mt-3">
                    <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                      <div className={`h-full rounded-full ${color}`} style={{ width: `${Math.min(pct, 100)}%` }} />
                    </div>
                  </div>
                )}
                {(ch.roi || ch.timeline || ch.effort) && (
                  <div className="flex items-center gap-4 mt-3 pt-3 border-t border-gray-50">
                    {ch.roi && <span className="text-xs text-gray-500"><span className="font-medium text-gray-600">ROI:</span> {ch.roi}</span>}
                    {ch.timeline && <span className="text-xs text-gray-500"><span className="font-medium text-gray-600">Timeline:</span> {ch.timeline}</span>}
                    {ch.effort && <span className="text-xs text-gray-500"><span className="font-medium text-gray-600">Effort:</span> {ch.effort}</span>}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
