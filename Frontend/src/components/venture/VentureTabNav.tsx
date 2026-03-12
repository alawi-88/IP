import { useLocale } from 'next-intl';
import { useEffect, useRef } from 'react';
import { VentureTab } from '@/types/venture';
import { resolveLabel, getTabDotColor } from '@/utils/venture';
import HeroIcon from '@/components/venture/HeroIcon';

interface VentureTabNavProps {
  tabs: VentureTab[];
  activeIndex: number;
  onTabChange: (index: number) => void;
}

/**
 * Resolve the icon name for a tab.
 * Uses the tab's `icon` field (set by admin in section builder).
 * Falls back to a sensible default based on tab slug.
 */
function resolveTabIcon(tab: VentureTab): string {
  // Use admin-configured icon if available
  if (tab.icon) return tab.icon;

  // Fallback map from slug → heroicon name
  const fallbackMap: Record<string, string> = {
    dashboard: 'chart-bar',
    'strategic-frameworks': 'light-bulb',
    strategic_frameworks: 'light-bulb',
    'market-analysis': 'chart-pie',
    market_analysis: 'chart-pie',
    'financial-projections': 'banknotes',
    financial_projections: 'banknotes',
    finances: 'banknotes',
    'mvp-roadmap': 'rocket-launch',
    mvp_roadmap: 'rocket-launch',
    'path-to-mvp': 'rocket-launch',
    path_to_mvp: 'rocket-launch',
    'risk-assessment': 'shield-exclamation',
    risk_assessment: 'shield-exclamation',
    'go-to-market': 'megaphone',
    go_to_market: 'megaphone',
    'competitive-analysis': 'trophy',
    competitive_analysis: 'trophy',
  };

  const slug = tab.slug?.toLowerCase() || '';
  return fallbackMap[slug] || 'document-text';
}

export const VentureTabNav = ({
  tabs,
  activeIndex,
  onTabChange,
}: VentureTabNavProps) => {
  const locale = useLocale();
  const scrollContainerRef = useRef<HTMLDivElement>(null);
  const activeTabRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    if (activeTabRef.current && scrollContainerRef.current) {
      activeTabRef.current.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center',
      });
    }
  }, [activeIndex]);

  return (
    <div
      ref={scrollContainerRef}
      className="flex gap-2 overflow-x-auto pb-2"
      style={{ scrollBehavior: 'smooth' }}
    >
      {tabs.map((tab, index) => {
        const isActive = index === activeIndex;
        const dotColor = getTabDotColor(tab);
        const label = resolveLabel(tab, locale);
        const iconName = resolveTabIcon(tab);

        return (
          <button
            key={tab.id}
            ref={isActive ? activeTabRef : null}
            onClick={() => onTabChange(index)}
            className={`relative flex flex-nowrap items-center gap-2 whitespace-nowrap rounded-full px-4 py-2 text-sm font-medium transition-all ${
              isActive
                ? 'text-white shadow-sm'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            }`}
            style={
              isActive
                ? {
                    backgroundColor: 'var(--dga-primary-600, #2563eb)',
                    color: 'white',
                  }
                : {}
            }
          >
            <HeroIcon
              name={iconName}
              size={16}
              style={{ opacity: isActive ? 1 : 0.7 }}
            />
            <span
              className="inline-block h-2 w-2 rounded-full"
              style={{ backgroundColor: dotColor }}
            />
            {label}
          </button>
        );
      })}
    </div>
  );
};
