'use client';

import { useQuery } from '@tanstack/react-query';
import { Empty, Spin, Space } from 'antd';
import { LeftOutlined, RightOutlined, InfoCircleOutlined } from '@ant-design/icons';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useState, useEffect } from 'react';
import axios from '@/lib/axios';
import { VentureHeroBanner } from '@/components/venture/VentureHeroBanner';
import { VentureTabNav } from '@/components/venture/VentureTabNav';
import { VentureTabContent } from '@/components/venture/VentureTabContent';
import { Venture, VentureTab } from '@/types/venture';

const VentureDetailPage = () => {
  const params = useParams();
  const ventureId = params.ventureId as string;
  const t = useTranslations();
  const [activeTabIndex, setActiveTabIndex] = useState(0);

  const {
    data: venture,
    isLoading,
    error,
    refetch: refetchVenture,
  } = useQuery({
    queryKey: ['venture', ventureId],
    queryFn: async () => {
      const response = await axios.get(
        `/participants/ventures/${ventureId}`
      );
      return (response.data?.data || response.data) as Venture;
    },
  });

  const { data: progressData, refetch: refetchProgress } = useQuery({
    queryKey: ['ventureProgress', ventureId],
    queryFn: async () => {
      const response = await axios.get(
        `/participants/ventures/${ventureId}/progress`
      );
      return (response.data?.data || response.data) as {
        progress_percentage: number;
        status: string;
      };
    },
    refetchInterval: venture?.status === 'generating' ? 5000 : false,
    enabled: !!venture,
  });

  useEffect(() => {
    if (progressData) {
      if (
        progressData.progress_percentage === 100 ||
        progressData.status !== 'generating'
      ) {
        refetchVenture();
      }
    }
  }, [progressData, refetchVenture]);

  const handleRefresh = () => {
    refetchVenture();
    refetchProgress();
  };

  const handleArchive = async () => {
    try {
      await axios.patch(`/participants/ventures/${ventureId}`, {
        is_archived: !(venture?.is_archived || false),
      });
      refetchVenture();
    } catch (error) {
      console.error('Failed to archive venture:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Spin size="large" />
      </div>
    );
  }

  if (error || !venture) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Empty description={t('error.loadingVenture')} />
      </div>
    );
  }

  const tabs: VentureTab[] = (venture.tabs || []).slice().sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
  const activeTab = tabs[activeTabIndex];

  const failedSectionCount =
    venture.tabs?.reduce(
      (count, tab) =>
        count +
        (tab.sections?.filter((s) => s.status === 'failed')?.length || 0),
      0
    ) || 0;

  return (
    <div className="w-full">
      <VentureHeroBanner
        venture={venture}
        onRefresh={handleRefresh}
        onArchive={handleArchive}
      />

      <div className="px-4 py-6 sm:px-6 lg:px-8">
        {/* Failed sections warning */}
        {failedSectionCount > 0 && (
          <div
            className="mb-6 flex items-start gap-3 rounded-lg border px-4 py-3"
            style={{
              backgroundColor: '#f0f5ff',
              borderColor: '#adc6ff',
            }}
          >
            <InfoCircleOutlined
              style={{ color: '#1890ff', fontSize: 18, marginTop: 2 }}
            />
            <div>
              <p className="text-sm font-medium text-gray-800">
                {failedSectionCount} section(s) failed to generate
              </p>
              <p className="text-xs text-gray-600">
                You can retry failed sections by clicking the &apos;Retry&apos; button on each section card.
              </p>
            </div>
          </div>
        )}

        {tabs.length > 0 ? (
          <Space direction="vertical" className="w-full" size="large">
            {/* Tab navigation pills + Previous/Next */}
            <div className="flex items-center justify-between gap-4">
              <div className="flex-1 overflow-hidden">
                <VentureTabNav
              tabs={tabs}
              activeIndex={activeTabIndex}
              onTabChange={setActiveTabIndex}
                />
              </div>
              <div className="flex shrink-0 items-center gap-3">
                <button
                  onClick={() =>
                    setActiveTabIndex((prev) => Math.max(0, prev - 1))
                  }
                  disabled={activeTabIndex === 0}
                  className="flex items-center gap-1 text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                  style={{ color: 'var(--dga-primary-600, #2563eb)' }}
                >
                  <LeftOutlined style={{ fontSize: 10 }} />
                  {t('venture.prevTab')}
                </button>
                <button
                  onClick={() =>
                    setActiveTabIndex((prev) =>
                      Math.min(tabs.length - 1, prev + 1)
                    )
                  }
                  disabled={activeTabIndex === tabs.length - 1}
                  className="flex items-center gap-1 text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                  style={{ color: 'var(--dga-primary-600, #2563eb)' }}
                >
                  {t('venture.nextTab')}
                  <RightOutlined style={{ fontSize: 10 }} />
                </button>
              </div>
            </div>

            {/* Tab content */}
            {activeTab && (
              <VentureTabContent tab={activeTab} ventureId={ventureId} />
            )}
          </Space>
        ) : (
          <Empty description={t('venture.noTabsAvailable')} />
        )}
      </div>
    </div>
  );
};

export default VentureDetailPage;
