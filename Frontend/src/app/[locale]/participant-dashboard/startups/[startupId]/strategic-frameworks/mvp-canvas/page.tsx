"use client";

import { Form, Input, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback, useEffect } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import AiGenerateButton from "@/components/va/AiGenerateButton";
import * as startupApi from "@/config/startup-api";

export default function MvpCanvasPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "strategic-frameworks", "mvp-canvas"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "strategic-frameworks", "mvp-canvas")});

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
        "strategic-frameworks",
        "mvp-canvas",
        content
      )
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "strategic-frameworks", "mvp-canvas"),
    onSuccess: () => {
      setIsCompleted(true);
      message.success(t("va.markedComplete", "Marked as complete!"));
    }
  });

  const aiGenerateMutation = useMutation({
    mutationFn: (data: any) =>
      startupApi.generateAi(
        startupId,
        "strategic-frameworks",
        "mvp-canvas",
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
      title={t("va.mvpCanvas", "MVP Canvas")}
      breadcrumbs={[
        { title: t("va.strategicFrameworks", "Strategic Frameworks") },
        { title: t("va.mvpCanvas", "MVP Canvas") },
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
          name="userSegment"
          label={<div className="flex items-center justify-between w-full"><span>{t("va.userSegment", "User Segment")}</span><AiGenerateButton fieldLabel={t("va.userSegment", "User Segment")} onGenerate={(prompt) => handleAiGenerate("userSegment", prompt)} onAccept={(content) => handleAiAccept("userSegment", content)} /></div>}
        >
          <Input.TextArea rows={3} placeholder="Define your primary user" />
        </Form.Item>

        <Form.Item
          name="painPoints"
          label={<div className="flex items-center justify-between w-full"><span>{t("va.painPoints", "Pain Points")}</span><AiGenerateButton fieldLabel={t("va.painPoints", "Pain Points")} onGenerate={(prompt) => handleAiGenerate("painPoints", prompt)} onAccept={(content) => handleAiAccept("painPoints", content)} /></div>}
        >
          <Input.TextArea
            rows={3}
            placeholder="What problems do users face?"
          />
        </Form.Item>

        <Form.Item
          name="mvpFeatures"
          label={<div className="flex items-center justify-between w-full"><span>{t("va.mvpFeatures", "MVP Features")}</span><AiGenerateButton fieldLabel={t("va.mvpFeatures", "MVP Features")} onGenerate={(prompt) => handleAiGenerate("mvpFeatures", prompt)} onAccept={(content) => handleAiAccept("mvpFeatures", content)} /></div>}
        >
          <Input.TextArea
            rows={3}
            placeholder="What are the essential features?"
          />
        </Form.Item>

        <Form.Item
          name="solution"
          label={<div className="flex items-center justify-between w-full"><span>{t("va.solution", "Solution")}</span><AiGenerateButton fieldLabel={t("va.solution", "Solution")} onGenerate={(prompt) => handleAiGenerate("solution", prompt)} onAccept={(content) => handleAiAccept("solution", content)} /></div>}
        >
          <Input.TextArea
            rows={3}
            placeholder="How does your MVP solve the pain points?"
          />
        </Form.Item>

        <Form.Item
          name="successMetrics"
          label={<div className="flex items-center justify-between w-full"><span>{t("va.successMetrics", "Success Metrics")}</span><AiGenerateButton fieldLabel={t("va.successMetrics", "Success Metrics")} onGenerate={(prompt) => handleAiGenerate("successMetrics", prompt)} onAccept={(content) => handleAiAccept("successMetrics", content)} /></div>}
        >
          <Input.TextArea
            rows={3}
            placeholder="How will you measure MVP success?"
          />
        </Form.Item>
      </Form>
    </VaPageLayout>
  );
}
