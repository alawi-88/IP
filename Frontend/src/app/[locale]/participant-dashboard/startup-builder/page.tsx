'use client';

import { useQuery } from '@tanstack/react-query';
import { Button, Empty, Card, Progress, Spin, Grid } from 'antd';
import { RocketOutlined, PlusOutlined, CheckCircleOutlined, SyncOutlined, CloseCircleOutlined, ClockCircleOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useState } from 'react';
import axios from '@/lib/axios';
import { CreateVentureModal } from '@/components/venture/CreateVentureModal';
import { Venture } from '@/types/venture';

/** Styled status tag using primary color for completed */
const VentureStatusTag = ({ status }: { status: string }) => {
  const t = useTranslations();

  const config: Record<string, { bg: string; text: string; border: string; icon: React.ReactNode; label: string }> = {
    completed: {
      bg: 'rgba(var(--dga-primary-500-rgb, 34, 197, 94), 0.1)',
      text: 'var(--dga-primary-600, #16a34a)',
      border: 'var(--dga-primary-300, #86efac)',
      icon: <CheckCircleOutlined />,
      label: t('venture.completed'),
    },
    generating: {
      bg: '#eff6ff',
      text: '#2563eb',
      border: '#93c5fd',
      icon: <SyncOutlined spin />,
      label: t('venture.generating' as any) || 'Generating',
    },
    pending: {
      bg: '#fefce8',
      text: '#ca8a04',
      border: '#fde047',
      icon: <ClockCircleOutlined />,
      label: t('venture.pending'),
    },
    failed: {
      bg: '#fef2f2',
      text: '#dc2626',
      border: '#fca5a5',
      icon: <CloseCircleOutlined />,
      label: t('venture.failed'),
    },
  };

  const c = config[status] || config.pending;

  return (
    <span
      className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
      style={{
        backgroundColor: c.bg,
        color: c.text,
        border: `1px solid ${c.border}`,
      }}
    >
      {c.icon}
      {c.label}
    </span>
  );
};

const VentureListPage = () => {
  const router = useRouter();
  const t = useTranslations();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const screens = Grid.useBreakpoint();

  const { data: ventures, isLoading, error } = useQuery({
    queryKey: ['ventures'],
    queryFn: async () => {
      const response = await axios.get('/participants/ventures');
      const raw = response.data;
      return (Array.isArray(raw) ? raw : raw?.data || []) as Venture[];
    },
  });

  const handleVentureClick = (ventureId: string) => {
    router.push(`./startup-builder/${ventureId}`);
  };

  const handleModalClose = () => {
    setIsModalOpen(false);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Spin size="large" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Empty description={t('error.loadingVentures')} />
      </div>
    );
  }

  const isEmpty = !ventures || ventures.length === 0;

  return (
    <div className="w-full px-4 py-8 sm:px-6 lg:px-8">
      <div className="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">
            {t('startupBuilder.title')}
          </h1>
          <p className="mt-2 text-gray-600">
            {t('startupBuilder.subtitle')}
          </p>
        </div>
        <Button
          type="primary"
          size="middle"
          icon={<PlusOutlined />}
          onClick={() => setIsModalOpen(true)}
        >
          {t('startupBuilder.createVenture')}
        </Button>
      </div>

      {isEmpty ? (
        <Empty
          icon={<RocketOutlined style={{ fontSize: '64px' }} />}
          description={t('startupBuilder.emptyState')}
          style={{ marginTop: '64px' }}
        >
          <Button
            type="primary"
            size="middle"
            icon={<PlusOutlined />}
            onClick={() => setIsModalOpen(true)}
          >
            {t('startupBuilder.startFirstVenture')}
          </Button>
        </Empty>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {ventures.map((venture) => (
            <Card
              key={venture.id}
              hoverable
              onClick={() => handleVentureClick(venture.id)}
              className="cursor-pointer transition-all hover:shadow-lg overflow-hidden"
              styles={{ body: { padding: '16px 20px' } }}
              cover={
                <div
                  className="h-28 p-5 text-white flex flex-col justify-end"
                  style={{
                    backgroundImage: `linear-gradient(135deg, var(--dga-primary-500), var(--dga-primary-700))`,
                  }}
                >
                  <h2 className="truncate text-xl font-bold leading-tight">
                    {venture.title}
                  </h2>
                </div>
              }
            >
              <div className="space-y-3">
                {/* Status row */}
                <div className="flex items-center justify-between">
                  <span className="text-sm text-gray-500">
                    {t('venture.status')}
                  </span>
                  <VentureStatusTag status={venture.status} />
                </div>

                {/* Progress bar */}
                <div>
                  <div className="mb-1.5 flex items-center justify-between">
                    <span className="text-sm font-medium text-gray-600">
                      {t('venture.progress')}
                    </span>
                    <span className="text-sm font-semibold text-gray-900">
                      {venture.progress_percentage || 0}%
                    </span>
                  </div>
                  <Progress
                    percent={venture.progress_percentage || 0}
                    showInfo={false}
                    strokeColor={{
                      '0%': 'var(--dga-primary-500)',
                      '100%': 'var(--dga-primary-700)',
                    }}
                    size="small"
                  />
                </div>

                {/* Viability score */}
                {venture.viability_score !== null && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-500">
                      {t('venture.viabilityScore')}
                    </span>
                    <span
                      className="text-lg font-bold"
                      style={{ color: 'var(--dga-primary-600)' }}
                    >
                      {venture.viability_score}%
                    </span>
                  </div>
                )}

                {/* Industry tag */}
                {venture.industry && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-500">
                      {t('venture.industry')}
                    </span>
                    <span
                      className="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                      style={{
                        backgroundColor: 'rgba(var(--dga-primary-500-rgb, 59, 130, 246), 0.1)',
                        color: 'var(--dga-primary-600, #2563eb)',
                      }}
                    >
                      {venture.industry}
                    </span>
                  </div>
                )}

                {/* Date */}
                {venture.created_at && (
                  <div className="border-t border-gray-100 pt-3 text-xs text-gray-400">
                    {new Date(venture.created_at).toLocaleDateString()}
                  </div>
                )}
              </div>
            </Card>
          ))}
        </div>
      )}

      <CreateVentureModal
        open={isModalOpen}
        onClose={handleModalClose}
      />
    </div>
  );
};

export default VentureListPage;
