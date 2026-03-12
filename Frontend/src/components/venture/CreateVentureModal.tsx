import {
  Modal,
  Form,
  Input,
  Select,
  Button,
  message,
  Space,
} from 'antd';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useState } from 'react';
import axios from '@/lib/axios';
import { Venture } from '@/types/venture';

interface CreateVentureModalProps {
  open: boolean;
  onClose: () => void;
}

const INDUSTRY_OPTIONS = [
  { label: 'Technology', value: 'technology' },
  { label: 'Healthcare', value: 'healthcare' },
  { label: 'Finance', value: 'finance' },
  { label: 'E-commerce', value: 'ecommerce' },
  { label: 'Education', value: 'education' },
  { label: 'Manufacturing', value: 'manufacturing' },
  { label: 'Other', value: 'other' },
];

const TARGET_MARKET_OPTIONS = [
  { label: 'B2B', value: 'b2b' },
  { label: 'B2C', value: 'b2c' },
  { label: 'B2B2C', value: 'b2b2c' },
  { label: 'D2C', value: 'd2c' },
];

const BUSINESS_MODEL_OPTIONS = [
  { label: 'SaaS', value: 'saas' },
  { label: 'Marketplace', value: 'marketplace' },
  { label: 'Subscription', value: 'subscription' },
  { label: 'Freemium', value: 'freemium' },
  { label: 'Advertising', value: 'advertising' },
  { label: 'Licensing', value: 'licensing' },
];

export const CreateVentureModal = ({
  open,
  onClose,
}: CreateVentureModalProps) => {
  const [form] = Form.useForm();
  const t = useTranslations();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const createMutation = useMutation({
    mutationFn: async (data: {
      title: string;
      idea_prompt: string;
      industry?: string;
      target_market?: string;
      business_model?: string;
    }) => {
      const response = await axios.post<Venture>(
        '/participants/ventures',
        data
      );
      return response.data;
    },
    onSuccess: (newVenture) => {
      queryClient.invalidateQueries({ queryKey: ['ventures'] });
      form.resetFields();
      message.success(t('startupBuilder.ventureCreated'));
      onClose();
      router.push(`./startup-builder/${newVenture.id}`);
    },
    onError: (error: any) => {
      const errorMessage =
        error.response?.data?.message ||
        t('error.creatingVenture');
      message.error(errorMessage);
    },
  });

  const handleSubmit = async () => {
    try {
      setIsSubmitting(true);
      const values = await form.validateFields();
      await createMutation.mutateAsync(values);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleModalClose = () => {
    form.resetFields();
    onClose();
  };

  return (
    <Modal
      title={t('startupBuilder.createVentureTitle')}
      open={open}
      onCancel={handleModalClose}
      footer={[
        <Button key="back" onClick={handleModalClose}>
          {t('cancel')}
        </Button>,
        <Button
          key="submit"
          type="primary"
          loading={isSubmitting || createMutation.isPending}
          onClick={handleSubmit}
        >
          {t('create')}
        </Button>,
      ]}
      width={500}
    >
      <Form
        form={form}
        layout="vertical"
        requiredMark="optional"
      >
        <Form.Item
          name="title"
          label={t('startupBuilder.form.ventureTitle')}
          rules={[
            {
              required: true,
              message: t('startupBuilder.form.titleRequired'),
            },
          ]}
        >
          <Input
            placeholder={t('startupBuilder.form.titlePlaceholder')}
            size="large"
          />
        </Form.Item>

        <Form.Item
          name="idea_prompt"
          label={t('startupBuilder.form.ideaDescription')}
          rules={[
            {
              required: true,
              message: t('startupBuilder.form.ideaRequired'),
            },
            {
              min: 20,
              message: t('startupBuilder.form.ideaMinLength'),
            },
          ]}
        >
          <Input.TextArea
            placeholder={t('startupBuilder.form.ideaPlaceholder')}
            rows={4}
            maxLength={1000}
            showCount
          />
        </Form.Item>

        <Form.Item
          name="industry"
          label={t('startupBuilder.form.industry')}
        >
          <Select
            placeholder={t('startupBuilder.form.selectIndustry')}
            options={INDUSTRY_OPTIONS}
          />
        </Form.Item>

        <Form.Item
          name="target_market"
          label={t('startupBuilder.form.targetMarket')}
        >
          <Select
            placeholder={t('startupBuilder.form.selectMarket')}
            options={TARGET_MARKET_OPTIONS}
          />
        </Form.Item>

        <Form.Item
          name="business_model"
          label={t('startupBuilder.form.businessModel')}
        >
          <Select
            placeholder={t('startupBuilder.form.selectModel')}
            options={BUSINESS_MODEL_OPTIONS}
          />
        </Form.Item>
      </Form>
    </Modal>
  );
};