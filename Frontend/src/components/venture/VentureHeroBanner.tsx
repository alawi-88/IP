import { Button, Badge } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Venture } from '@/types/venture';

interface VentureHeroBannerProps {
  venture: Venture;
  onRefresh: () => void;
  onArchive: () => void;
}

export const VentureHeroBanner = ({
  venture,
}: VentureHeroBannerProps) => {
  const router = useRouter();
  const t = useTranslations();

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'success';
      case 'generating':
        return 'processing';
      case 'failed':
        return 'error';
      default:
        return 'default';
    }
  };

  return (
    <div
      className="relative px-4 py-10 text-white sm:px-6 lg:px-8"
      style={{
        backgroundImage: `linear-gradient(135deg, var(--dga-primary-500), var(--dga-primary-700))`,
      }}
    >
      <div className="mx-auto max-w-6xl">
        <Button
          type="text"
          icon={<ArrowLeftOutlined />}
          style={{ color: 'white', marginBottom: '1rem' }}
          onClick={() => router.back()}
        >
          {t('venture.back')}
        </Button>

        <h1 className="mb-3 text-3xl font-bold">{venture.title}</h1>

        <div className="mb-3">
          <Badge
            status={getStatusColor(venture.status)}
            text={
              <span style={{ color: 'white' }}>
                {t(`venture.${venture.status}` as any) || venture.status}
              </span>
            }
          />
        </div>

        {venture.idea_prompt && (
          <p className="mb-4 line-clamp-2 text-base text-gray-200">
            {venture.idea_prompt}
          </p>
        )}

        <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-200">
          {venture.industry && (
            <p>
              <span className="font-semibold">{t('venture.industry')}:</span>{' '}
              {venture.industry}
            </p>
          )}
          {venture.target_market && (
            <p>
              <span className="font-semibold">{t('venture.targetMarket')}:</span>{' '}
              {venture.target_market}
            </p>
          )}
        </div>
      </div>
    </div>
  );
};
