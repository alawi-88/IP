"use client";

import React from "react";

interface PricingTier {
  name?: string;
  tier?: string;
  price?: string | number;
  period?: string;
  features?: string[];
  highlighted?: boolean;
  cta?: string;
  description?: string;
}

interface PricingCardsRendererProps {
  content: any;
}

export default function PricingCardsRenderer({ content }: PricingCardsRendererProps) {
  const tiers: PricingTier[] = Array.isArray(content)
    ? content
    : content?.tiers || content?.plans || content?.pricing || [];

  if (!tiers.length) {
    return <div className="text-gray-500 dark:text-gray-400 text-sm italic">No pricing data available.</div>;
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {tiers.map((tier, idx) => {
        const isHighlighted = tier.highlighted || idx === 1;
        return (
          <div
            key={idx}
            className={`rounded-xl border-2 p-6 flex flex-col transition-shadow hover:shadow-lg bg-white dark:bg-gray-800 ${
              isHighlighted
                ? "border-[var(--dga-primary-500)] shadow-md"
                : "border-gray-200 dark:border-gray-700"
            }`}
          >
            {isHighlighted && (
              <div
                className="text-xs font-bold uppercase tracking-wider mb-2 px-3 py-1 rounded-full self-start"
                style={{
                  backgroundColor: "var(--dga-primary-100, #EBF5FF)",
                  color: "var(--dga-primary-700, #1A56DB)",
                }}
              >
                Recommended
              </div>
            )}

            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-1">
              {tier.name || tier.tier || `Tier ${idx + 1}`}
            </h3>

            {tier.description && (
              <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">{tier.description}</p>
            )}

            <div className="mb-4">
              <span className="text-3xl font-extrabold text-gray-900 dark:text-white">
                {typeof tier.price === "number" ? `$${tier.price}` : tier.price || "Custom"}
              </span>
              {tier.period && (
                <span className="text-sm text-gray-500 dark:text-gray-400 ml-1">/{tier.period}</span>
              )}
            </div>

            {tier.features && tier.features.length > 0 && (
              <ul className="space-y-2 mb-6 flex-1">
                {tier.features.map((feature, fIdx) => (
                  <li key={fIdx} className="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <svg
                      className="w-4 h-4 mt-0.5 flex-shrink-0"
                      style={{ color: "var(--dga-primary-500, #3B82F6)" }}
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2}
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{feature}</span>
                  </li>
                ))}
              </ul>
            )}

            {tier.cta && (
              <div
                className={`mt-auto text-center py-2 px-4 rounded-lg text-sm font-semibold ${
                  isHighlighted
                    ? "text-white"
                    : "text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700"
                }`}
                style={
                  isHighlighted
                    ? { backgroundColor: "var(--dga-primary-500, #3B82F6)" }
                    : undefined
                }
              >
                {tier.cta}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
