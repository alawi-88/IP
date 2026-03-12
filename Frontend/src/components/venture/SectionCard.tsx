import {
  Button,
  Space,
  Spin,
  Alert,
  Popconfirm,
  Tooltip,
  message,
} from 'antd';
import {
  EditOutlined,
  CopyOutlined,
  ReloadOutlined,
  RobotOutlined,
  CheckCircleOutlined,
  CloseCircleOutlined,
  SyncOutlined,
  ClockCircleOutlined,
} from '@ant-design/icons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { useLocale } from 'next-intl';
import { useState } from 'react';
import axios from '@/lib/axios';
import { VentureSection } from '@/types/venture';
import SectionRenderer from '@/components/venture/renderers/SectionRenderer';
import { EditSectionModal } from '@/components/venture/EditSectionModal';
import { resolveLabel, flattenContentToText } from '@/utils/venture';
import HeroIcon from '@/components/venture/HeroIcon';

interface SectionCardProps {
  section: VentureSection;
  ventureId: string;
}

const StatusTag = ({ status }: { status: string }) => {
  const t = useTranslations();

  const config: Record<
    string,
    { bg: string; text: string; border: string; icon: React.ReactNode; label: string }
  > = {
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

export const SectionCard = ({
  section,
  ventureId,
}: SectionCardProps) => {
  const locale = useLocale();
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);

  const regenerateMutation = useMutation({
    mutationFn: async () => {
      await axios.post(
        `/participants/ventures/${ventureId}/sections/${section.id}/regenerate`
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['venture', ventureId],
      });
      message.success(t('venture.regeneratingSection'));
    },
    onError: (error: any) => {
      message.error(
        error.response?.data?.message ||
          t('error.regeneratingSection')
      );
    },
  });

  const handleCopyToClipboard = async () => {
    try {
      const text = flattenContentToText(section.content);
      await navigator.clipboard.writeText(text);
      message.success(t('venture.copiedToClipboard'));
    } catch (error) {
      message.error(t('error.copyingToClipboard'));
    }
  };

  const sectionLabel = resolveLabel(section, locale);
  const displayConfig = section.display_config || {} as any;
  const iconName = displayConfig.icon as string | undefined;
  const color = displayConfig.color || 'var(--dga-primary-500, #1890ff)';
  const componentType = section.component_type || displayConfig.component_type || 'text_content';

  const isGenerating = section.status === 'generating' || section.status === 'pending';
  const isFailed = section.status === 'failed';
  const isCompleted = section.status === 'completed';

  return (
    <>
      <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm transition-all hover:shadow-md overflow-hidden">
        {/* Section Header */}
        <div className="flex items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-700 px-6 py-4">
          <div className="flex items-center gap-3 min-w-0">
            {iconName && (
              <span className="flex-shrink-0" style={{ color }}>
                <HeroIcon name={iconName} size={22} />
              </span>
            )}
            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">
              {sectionLabel}
            </h3>
            <StatusTag status={section.status} />
          </div>

          <Space size="small" className="flex-shrink-0">
            {isCompleted && (
              <>
                <Tooltip title={t('venture.edit')}>
                  <Button
                    size="small"
                    type="text"
                    icon={<EditOutlined />}
                    onClick={() => setIsEditModalOpen(true)}
                  />
                </Tooltip>

                <Tooltip title={t('venture.copy')}>
                  <Button
                    size="small"
                    type="text"
                    icon={<CopyOutlined />}
                    onClick={handleCopyToClipboard}
                  />
                </Tooltip>

                <Popconfirm
                  title={t('venture.regenerateConfirm')}
                  description={t('venture.regenerateConfirmMessage')}
                  onConfirm={() => regenerateMutation.mutate()}
                  okText={t('confirm')}
                  cancelText={t('cancel')}
                >
                  <Tooltip title={t('venture.regenerate')}>
                    <Button
                      size="small"
                      type="text"
                      icon={<ReloadOutlined />}
                      loading={regenerateMutation.isPending}
                      disabled={regenerateMutation.isPending}
                    />
                  </Tooltip>
                </Popconfirm>
              </>
            )}

            {isFailed && (
              <Tooltip title={t('venture.tryAgain')}>
                <Button
                  size="small"
                  type="text"
                  icon={<RobotOutlined />}
                  onClick={() => regenerateMutation.mutate()}
                  loading={regenerateMutation.isPending}
                  disabled={regenerateMutation.isPending}
                />
              </Tooltip>
            )}
          </Space>
        </div>

        {/* Section Content */}
        <div className="px-6 py-5">
          {isGenerating && (
            <div className="flex items-center gap-3 py-8 justify-center text-gray-500 dark:text-gray-400">
              <Spin size="small" />
              <span className="text-sm">{t('venture.generatingContent')}</span>
            </div>
          )}

          {isFailed && (
            <Alert
              message={t('venture.generationFailed')}
              description={t('venture.tryAgain')}
              type="error"
              showIcon
              action={
                <Button
                  size="small"
                  type="primary"
                  danger
                  icon={<ReloadOutlined />}
                  onClick={() => regenerateMutation.mutate()}
                  loading={regenerateMutation.isPending}
                >
                  {t('venture.retry')}
                </Button>
              }
            />
          )}

          {isCompleted && (
            <SectionRenderer
              content={section.content}
              componentType={componentType}
            />
          )}
        </div>
      </div>

      <EditSectionModal
        section={section}
        ventureId={ventureId}
        open={isEditModalOpen}
        onClose={() => setIsEditModalOpen(false)}
        onSaved={() => {
          queryClient.invalidateQueries({
            queryKey: ['venture', ventureId],
          });
          setIsEditModalOpen(false);
        }}
      />
    </>
  );
};
