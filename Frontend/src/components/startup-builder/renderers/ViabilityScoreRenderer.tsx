"use client";

interface Props {
  content: any;
}

export default function ViabilityScoreRenderer({ content }: Props) {
  const score = content.score || content.overall_score || content.value || 0;
  const rating = content.rating || content.label || getScoreLabel(score);
  const breakdown = content.breakdown || content.categories || content.criteria || [];
  const description = content.description || content.summary;

  const scoreColor = score >= 70 ? "#25935F" : score >= 40 ? "#f59e0b" : "#ef4444";
  const ratingBg = score >= 70 ? "bg-emerald-50 text-emerald-700 border-emerald-200" : score >= 40 ? "bg-amber-50 text-amber-700 border-amber-200" : "bg-red-50 text-red-700 border-red-200";

  return (
    <div className="space-y-6">
      {/* Main Score */}
      <div className="flex flex-col md:flex-row items-center gap-8">
        <div className="relative w-36 h-36">
          <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90">
            <circle cx="50" cy="50" r="42" fill="none" stroke="#f3f4f6" strokeWidth="8" />
            <circle
              cx="50" cy="50" r="42" fill="none"
              stroke={scoreColor}
              strokeWidth="8" strokeLinecap="round"
              strokeDasharray={`${(score / 100) * 264} 264`}
            />
          </svg>
          <div className="absolute inset-0 flex flex-col items-center justify-center">
            <span className="text-4xl font-bold text-gray-800">{score}%</span>
            <span className={`mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ${ratingBg}`}>{rating}</span>
          </div>
        </div>
        {description && (
          <div className="flex-1">
            <p className="text-sm text-gray-600 leading-relaxed">{description}</p>
          </div>
        )}
      </div>

      {/* Breakdown */}
      {breakdown.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {breakdown.map((item: any, i: number) => {
            const val = item.score || item.value || item.percentage || 0;
            const barColor = val >= 70 ? "bg-emerald-500" : val >= 40 ? "bg-amber-500" : "bg-red-500";
            return (
              <div key={i} className="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm font-medium text-gray-700">{item.name || item.label || item.category}</span>
                  <span className="text-sm font-bold text-gray-800">{val}%</span>
                </div>
                <div className="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full ${barColor}`}
                    style={{ width: `${Math.min(val, 100)}%` }}
                  />
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

function getScoreLabel(score: number): string {
  if (score >= 80) return "Excellent";
  if (score >= 70) return "Strong";
  if (score >= 50) return "Fair";
  return "Needs Work";
}
