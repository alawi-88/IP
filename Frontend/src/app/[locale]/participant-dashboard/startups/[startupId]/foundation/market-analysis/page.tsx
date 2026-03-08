"use client";

import { Form, Input, Button, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback, useEffect } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import AiGenerateButton from "@/components/va/AiGenerateButton";
import * as startupApi from "@/config/startup-api";

export default function MarketAnalysisPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "foundation", "market-analysis"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "foundation", "market-analysis")});

  useEffect(() => {
    if (page) {
      form.setFieldsValue(page.content || {});
      setIsCompleted(Number(page.completion_percentage) >= 100);
    }
  }, [page, form]);

  const updateMutation = useMutation({
    mutationFn: (content: any) =>
      startupApi.updateVaPage(startupId, "foundation", "market-analysis", content)
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "foundation", "market-analysis"),
    onSuccess: () => {
      setIsCompleted(true);
      message.success(t("va.markedComplete", "Marked as complete!"));
    }
  });

  const aiGenerateMutation = useMutation({
    mutationFn: (data: any) =>
      startupApi.generateAi(
        startupId,
        "foundation",
        "market-analysis",
        data.fieldKey,
        data.prompt
      )
  });

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

  if (isLoading) {
    return <Spin />;
  }

  return (
    <VaPageLayout
      title={t("va.marketAnalysis", "Market Analysis")}
      breadcrumbs={[
        { title: t("va.foundation", "Foundation") },
        { title: t("va.marketAnalysis", "Market Analysis") },
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
          name="tam"
          label={t("va.tam", "Total Addressable Market (TAM)")}
          rules={[
            {
              required: true,
              message: t("required-field", "This field is required")
            },
          ]}
        >
          <Input.TextArea
            rows={3}
            placeholder={t(
              "va.tamDescription",
              "What is the total addressable market size?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="sam"
          label={t("va.sam", "Serviceable Available Market (SAM)")}
        >
          <Input.TextArea
            rows={3}
            placeholder={t(
              "va.samDescription",
              "What is your serviceable available market?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="som"
          label={t("va.som", "Serviceable Obtainable Market (SOM)")}
        >
          <Input.TextArea
            rows={3}
            placeholder={t(
              "va.somDescription",
              "What is your achievable market share?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="targetMarket"
          label={t("va.targetMarket", "Target Market")}
        >
          <Input.TextArea
            rows={3}
            placeholder={t(
              "va.describeTargetMarket",
              "Describe your target market in detail"
            )}
          />
        </Form.Item>

        <Form.Item
          name="marketTrends"
          label={t("va.marketTrends", "Market Trends")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.describeMarketTrends",
              "What are the current market trends?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="industryAnalysis"
          label={t("va.industryAnalysis", "Industry Analysis")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.analyzeIndustry",
              "Analyze your industry landscape"
            )}
          />
        </Form.Item>
      </Form>
    </VaPageLayout>
  );
}
