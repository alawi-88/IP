"use client";

import { Form, Input, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback, useEffect } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import AiGenerateButton from "@/components/va/AiGenerateButton";
import * as startupApi from "@/config/startup-api";

export default function Value-propositionPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "gtm-strategy", "value-proposition"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "gtm-strategy", "value-proposition")});

  useEffect(() => {
    if (page) {
      form.setFieldsValue(page.content || {});
      setIsCompleted(Number(page.completion_percentage) >= 100);
    }
  }, [page, form]);

  const updateMutation = useMutation({
    mutationFn: (content: any) =>
      startupApi.updateVaPage(
        startupId,
        "gtm-strategy",
        "value-proposition",
        content
      )
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "gtm-strategy", "value-proposition"),
    onSuccess: () => {
      setIsCompleted(true);
      message.success(t("va.markedComplete", "Marked as complete!"));
    }
  });

  const aiGenerateMutation = useMutation({
    mutationFn: (data: any) =>
      startupApi.generateAi(
        startupId,
        "gtm-strategy",
        "value-proposition",
        data.fieldKey,
        data.prompt
      )
  });

  const handleAiGenerate = useCallback(
    async (fieldKey: string, prompt: string) => {
      try {
        const result = await aiGenerateMutation.mutateAsync({
          fieldKey,
          prompt
        });
        return result.generatedContent;
      } catch (error) {
        throw error;
      }
    },
    [aiGenerateMutation]
  );

  const handleAiAccept = useCallback(
    (fieldKey: string, content: string) => {
      form.setFieldValue(fieldKey, content);
      const values = form.getFieldsValue();
      updateMutation.mutate(values);
    },
    [form, updateMutation]
  );

  const handleFieldChange = useCallback(
    (changedValues: any) => {
      if (saveTimeout) clearTimeout(saveTimeout);

      const timer = setTimeout(() => {
        const values = form.getFieldsValue();
        updateMutation.mutate(values);
      }, 2000);

      setSaveTimeout(timer);
    },
    [form, saveTimeout, updateMutation]
  );

  if (isLoading) {
    return <Spin />;
  }

  return (
    <VaPageLayout
      title={t("va.value-proposition", "value-proposition")}
      breadcrumbs={[
        { title: t("va.gtmStrategy", "GTM Strategy") },
        { title: t("va.value-proposition", "value-proposition") },
      ]}
      completionPercentage={isCompleted ? 100 : 0}
      isCompleted={isCompleted}
      onMarkComplete={() => completeMutation.mutateAsync()}
    >
      <Form
        form={form}
        layout="vertical"
        onValuesChange={handleFieldChange}
        className="space-y-6"
      >
        <Form.Item
          name="content"
          label={<div className="flex items-center justify-between w-full"><span>{t("va.content", "Content")}</span><AiGenerateButton fieldLabel={t("va.content", "Content")} onGenerate={(prompt) => handleAiGenerate("content", prompt)} onAccept={(content) => handleAiAccept("content", content)} /></div>}
        >
          <Input.TextArea
            rows={6}
            placeholder="Enter your content here"
          />
        </Form.Item>
      </Form>
    </VaPageLayout>
  );
}
