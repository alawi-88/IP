"use client";

interface Props {
  content: any;
}

export default function ViabilityScoreRenderer({ content }: Props) {
  const score = content.score || content.overall_score || content.value || 0;
  const rating = content.rating || content.label || getScoreLabel(score);
  const breakdown = content.breakdown || content.categories || content.criteria || [];
  const description = content.description || content.summary;

  return (
    <div className="space-y-6">
      {/* Main Score */}
      <div className="flex flex-col md:flex-row items-center gap-8">
        <div className="relative w-32 h-32">
          <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90">
            <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" strokeWidth="8" />
            <circle
              cx="50" cy="50" r="42" fill="none"
              stroke={score >= 70 ? "#25935F" : score >= 40 ? "#f59e0b" : "#ef4444"}
              strokeWidth="8" strokeLinecap="round"
              strokeDasharray={`${(score / 100) * 264} 264`}
            />
          </svg>
          <div className="absolute inset-0 flex flex-col items-center justify-center">
            <span className="text-3xl font-bold text-gray-800">{score}%</span>
            <span className="text-xs text-gray-500">{rating}</span>
          </div>
        </div>
        {description && (
          <p className="text-sm text-gray-600 flex-1">{description}</p>
        )}
      </div>

      {/* Breakdown */}
      {breakdown.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {breakdown.map((item: any, i: number) => {
            const val = item.score || item.value || item.percentage || 0;
            return (
              <div key={i} className="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                <span className="text-sm text-gray-700">{item.name || item.label || item.category}</span>
                <div className="flex items-center gap-2">
                  <div className="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      className="h-full rounded-full bg-[#25935F]"
                      style={{ width: `${Math.min(val, 100)}%` }}
                    />
                  </div>
                  <span className="text-xs font-medium text-gray-600 w-8 text-right">{val}%</span>
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
  if (score >= 70) return "Good";
  if (score >= 50) return "Fair";
  return "Needs Work";
}
