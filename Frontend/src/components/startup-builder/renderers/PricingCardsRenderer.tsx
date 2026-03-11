"use client";

interface Props {
  content: any;
}

export default function PricingCardsRenderer({ content }: Props) {
  const tiers = content.tiers || content.pricing_tiers || content.plans || content.items || [];
  const businessModel = content.business_model || content.description || content.model;

  return (
    <div className="space-y-6">
      {businessModel && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">Business Model</h4>
          <p className="text-sm text-gray-600">{typeof businessModel === "string" ? businessModel : businessModel.description || JSON.stringify(businessModel)}</p>
        </div>
      )}

      {tiers.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {tiers.map((tier: any, i: number) => {
            const isPopular = tier.popular || tier.recommended || tier.highlighted || i === 1;
            return (
              <div
                key={i}
                className={`relative rounded-xl border-2 p-5 ${
                  isPopular
                    ? "border-[#25935F] shadow-lg"
                    : "border-gray-200"
                }`}
              >
                {isPopular && (
                  <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-[#25935F] text-white text-xs rounded-full font-medium">
                    Most Popular
                  </div>
                )}
                <h4 className={`text-lg font-bold ${isPopular ? "text-[#25935F]" : "text-gray-800"}`}>
                  {tier.name || tier.title}
                </h4>
                <div className="flex items-baseline gap-1 mt-2">
                  <span className="text-3xl font-bold text-gray-800">
                    {tier.price || tier.amount || "$0"}
                  </span>
                  {tier.period && (
                    <span className="text-sm text-gray-400">{tier.period}</span>
                  )}
                </div>
                {tier.features && Array.isArray(tier.features) && (
                  <ul className="mt-4 space-y-2">
                    {tier.features.map((f: any, j: number) => (
                      <li key={j} className="flex items-start gap-2 text-sm text-gray-600">
                        <span className="text-green-500 mt-0.5">✓</span>
                        <span>{typeof f === "string" ? f : f.name || f.text}</span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
