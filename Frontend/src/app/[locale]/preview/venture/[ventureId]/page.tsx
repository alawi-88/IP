'use client';

import { useEffect, useState } from 'react';
import { Empty, Spin, Space } from 'antd';
import { LeftOutlined, RightOutlined, EyeOutlined } from '@ant-design/icons';
import { useParams, useSearchParams } from 'next/navigation';
import axios from 'axios';
import { VentureTabNav } from '@/components/venture/VentureTabNav';
import { VentureTabContent } from '@/components/venture/VentureTabContent';
import { Venture, VentureTab } from '@/types/venture';

/**
 * Admin Preview Page
 * Renders venture data exactly as a participant would see it.
 * Accessed via signed URL from Filament admin panel.
 * No JWT auth required — signature validates admin origin.
 */
const VenturePreviewPage = () => {
  const params = useParams();
  const searchParams = useSearchParams();
  const ventureId = params.ventureId as string;

  const [venture, setVenture] = useState<Venture | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTabIndex, setActiveTabIndex] = useState(0);

  useEffect(() => {
    const fetchPreview = async () => {
      try {
        setIsLoading(true);
        const expires = searchParams.get('expires');
        const signature = searchParams.get('signature');

        if (!expires || !signature) {
          setError('Missing preview credentials. Please generate a new preview link from the admin panel.');
          return;
        }

        const apiBase = process.env.NEXT_PUBLIC_API_ENDPOINT || 'http://localhost:8080/api';
        const response = await axios.get(
          `${apiBase}/ventures/${ventureId}/admin-preview`,
          {
            params: { expires, signature },
            headers: { Accept: 'application/json' },
          }
        );

        const data = response.data?.data || response.data;
        setVenture(data as Venture);
      } catch (err: any) {
        if (err?.response?.status === 403) {
          setError('Preview link has expired. Please generate a new one from the admin panel.');
        } else if (err?.response?.status === 404) {
          setError('Venture not found.');
        } else {
          setError('Failed to load preview. Please try again.');
        }
        console.error('Preview fetch error:', err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchPreview();
  }, [ventureId, searchParams]);

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
        <Empty description={error || 'Unable to load preview'} />
      </div>
    );
  }

  const tabs: VentureTab[] = (venture.tabs || [])
    .slice()
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
  const activeTab = tabs[activeTabIndex];

  return (
    <div className="w-full min-h-screen bg-gray-50">
      {/* Admin Preview Banner */}
      <div
        className="sticky top-0 z-50 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white shadow-md"
        style={{ backgroundColor: '#7C3AED' }}
      >
        <EyeOutlined />
        <span>Admin Preview Mode</span>
        <span className="opacity-75">—</span>
        <span className="opacity-75">This is how participants see &quot;{venture.title}&quot;</span>
      </div>

      {/* Venture Hero Section (simplified for preview) */}
      <div
        className="px-6 py-8"
        style={{
          background: 'linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%)',
        }}
      >
        <div className="max-w-5xl mx-auto">
          <h1 className="text-2xl font-bold text-white mb-2">
            {venture.title}
          </h1>
          <p className="text-blue-100 text-sm max-w-2xl">
            {venture.idea_prompt}
          </p>
          <div className="flex items-center gap-4 mt-4">
            {venture.industry && (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white">
                {venture.industry}
              </span>
            )}
            {venture.target_market && (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white">
                {venture.target_market}
              </span>
            )}
            {venture.viability_score != null && (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white">
                Score: {venture.viability_score}/100
              </span>
            )}
          </div>
        </div>
      </div>

      {/* Content area */}
      <div className="px-4 py-6 sm:px-6 lg:px-8 max-w-7xl mx-auto">
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
                  Previous
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
                  Next
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
          <Empty description="No tabs available for this venture" />
        )}
      </div>
    </div>
  );
};

export default VenturePreviewPage;
