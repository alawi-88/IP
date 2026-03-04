"use client";

import { Form, Input, Button, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "@/i18n/routing";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback, useEffect } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import AiGenerateButton from "@/components/va/AiGenerateButton";
import * as startupApi from "@/config/startup-api";

export default function OverviewPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "foundation", "overview"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "foundation", "overview"),
    onSuccess: (data) => {
      form.setFieldsValue(data.content || {});
      setIsCompleted(!!data.completedAt);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (content: any) =>
      startupApi.updateVaPage(startupId, "foundation", "overview", content),
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "foundation", "overview"),
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
        "overview",
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
      title={t("va.overview", "Overview")}
      breadcrumbs={[
        { title: t("va.startups", "Startups") },
        { title: t("va.foundation", "Foundation") },
        { title: t("va.overview", "Overview") },
      ]}
      completionPercentage={isCompleted ? 100 : 0}
      isCompleted={isCompleted}
      onMarkComplete={() => completeMutation.mutateAsync()}
    >
      <Form
        form={form}
        layout="vertical"
        onValuesChange={handleFieldChange}
        className="space-y-4"
      >
        <Form.Item
          name="businessDescription"
          label={t("va.businessDescription", "Business Description")}
          rules={[
            {
              required: true,
              message: t("required-field", "This field is required"),
            },
          ]}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.describeBusiness",
              "Describe what your business does"
            )}
          />
        </Form.Item>

        <div className="flex justify-between items-center">
          <span className="font-medium text-gray-700">
            {t("va.businessDescription", "Business Description")}
          </span>
          <AiGenerateButton
            fieldLabel={t("va.businessDescription", "Business Description")}
            onGenerate={(prompt) =>
              handleAiGenerate("businessDescription", prompt)
            }
            onAccept={(content) =>
              handleAiAccept("businessDescription", content)
            }
          />
        </div>

        <Form.Item
          name="problemStatement"
          label={t("va.problemStatement", "Problem Statement")}
          rules={[
            {
              required: true,
              message: t("required-field", "This field is required"),
            },
          ]}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.describeProblem",
              "What problem does your business solve?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="solutionDescription"
          label={t("va.solutionDescription", "Solution Description")}
        >
          <Input.TextArea
            rows={4}
            placeholder={t(
              "va.describeSolution",
              "How does your solution solve the problem?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="targetAudience"
          label={t("va.targetAudience", "Target Audience")}
        >
          <Input.TextArea
            rows={3}
            placeholder={t(
              "va.describeAudience",
              "Who is your primary target audience?"
            )}
          />
        </Form.Item>

        <Form.Item
          name="uniqueValueProposition"
          label={t("va.uniqueValueProposition", "Unique Value Proposition")}
        >
          <Input.TextArea
            rows={3}
            placeholder={t(
              "va.describeUvp",
              "What makes your solution unique?"
            )}
          />
        </Form.Item>
      </Form>
    </VaPageLayout>
  );
}
