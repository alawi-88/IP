"use client";

import { Form, Input, InputNumber, Button, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "@/i18n/routing";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import AiGenerateButton from "@/components/va/AiGenerateButton";
import * as startupApi from "@/config/startup-api";

export default function FinancialModelPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "foundation", "financial-model"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "foundation", "financial-model"),
    onSuccess: (data) => {
      form.setFieldsValue(data.content || {});
      setIsCompleted(!!data.completedAt);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (content: any) =>
      startupApi.updateVaPage(startupId, "foundation", "financial-model", content),
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "foundation", "financial-model"),
    onSuccess: () => {
      setIsCompleted(true);
      message.success(t("va.markedComplete", "Marked as complete!"));
    },
  });

  const aiGenerateMutation = useMutation({
    mutationFn: (data: any) =>
      startupApi.generateAi(
        startupId,
        "foundation",
        "financial-model",
        data.fieldKey,
        data.prompt
      ),
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
          prompt,
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
      title={t("va.financialModel", "Financial Model")}
      breadcrumbs={[
        { title: t("va.foundation", "Foundation") },
        { title: t("va.financialModel", "Financial Model") },
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
          name="year1Revenue"
          label={t("va.year1Revenue", "Year 1 Revenue Projection (USD)")}
        >
          <InputNumber
            min={0}
            step={1000}
            placeholder="Enter projected revenue"
            className="w-full"
          />
        </Form.Item>

        <Form.Item
          name="year2Revenue"
          label={t("va.year2Revenue", "Year 2 Revenue Projection (USD)")}
        >
          <InputNumber
            min={0}
            step={1000}
            placeholder="Enter projected revenue"
            className="w-full"
          />
        </Form.Item>

        <Form.Item
          name="year3Revenue"
          label={t("va.year3Revenue", "Year 3 Revenue Projection (USD)")}
        >
          <InputNumber
            min={0}
            step={1000}
            placeholder="Enter projected revenue"
            className="w-full"
          />
        </Form.Item>

        <Form.Item
          name="costStructure"
          label={t("va.costStructure", "Cost Structure")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.describeCostStructure",
              "Breakdown of your costs (fixed, variable, etc.)"
            )}
          />
        </Form.Item>

        <Form.Item
          name="breakEvenAnalysis"
          label={t("va.breakEvenAnalysis", "Break-even Analysis")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.breakEvenDescription",
              "When do you expect to break even?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="financialAssumptions"
          label={t("va.financialAssumptions", "Financial Assumptions")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.assumptionsDescription",
              "List your key financial assumptions"
            )}
          />
        </Form.Item>

        <Form.Item
          name="fundingNeeds"
          label={t("va.fundingNeeds", "Funding Needs (USD)")}
        >
          <InputNumber
            min={0}
            step={10000}
            placeholder="How much funding do you need?"
            className="w-full"
          />
        </Form.Item>

        <Form.Item
          name="usageOfFunds"
          label={t("va.usageOfFunds", "Usage of Funds")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.fundAllocation",
              "How will you use the funding?"
            )}
          />
        </Form.Item>
      </Form>
    </VaPageLayout>
  );
}
