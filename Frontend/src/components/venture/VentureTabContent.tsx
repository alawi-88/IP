import { Empty, Space } from 'antd';
import { useLocale } from 'next-intl';
import { useTranslations } from 'next-intl';
import { VentureTab } from '@/types/venture';
import { SectionCard } from './SectionCard';
import { resolveLabel } from '@/utils/venture';

interface VentureTabContentProps {
  tab: VentureTab;
  ventureId: string;
}

export const VentureTabContent = ({
  tab,
  ventureId,
}: VentureTabContentProps) => {
  const locale = useLocale();
  const t = useTranslations();

  const visibleSections = (tab.sections || [])
    .filter((section) => !section.is_hidden)
    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

  const tabLabel = resolveLabel(tab, locale);

  if (visibleSections.length === 0) {
    return (
      <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-8">
        <Empty
          description={t('venture.noSections')}
          style={{ marginTop: '32px' }}
        />
      </div>
    );
  }

  return (
    <Space direction="vertical" className="w-full" size="large">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white">{tabLabel}</h2>
      </div>

      <div className="space-y-4">
        {visibleSections.map((section) => (
          <SectionCard
            key={section.id}
            section={section}
            ventureId={ventureId}
          />
        ))}
      </div>
    </Space>
  );
};