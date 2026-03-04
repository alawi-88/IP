"use client";

import { Form, Input, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "@/i18n/routing";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import * as startupApi from "@/config/startup-api";

export default function Business-planPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "strategic-frameworks", "business-plan"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "strategic-frameworks", "business-plan"),
    onSuccess: (data) => {
      form.setFieldsValue(data.content || {});
      setIsCompleted(!!data.completedAt);
    },
  });

  const updateMutation = useMutation({
    mutationFn: (content: any) =>
      startupApi.updateVaPage(
        startupId,
        "strategic-frameworks",
        "business-plan",
        content
      ),
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "strategic-frameworks", "business-plan"),
    onSuccess: () => {
      setIsCompleted(true);
      message.success(t("va.markedComplete", "Marked as complete!"));
    },
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

  if (isLoading) {
    return <Spin />;
  }

  return (
    <VaPageLayout
      title={t("va.business-plan", "business-plan")}
      breadcrumbs={[
        { title: t("va.strategicFrameworks", "Strategic Frameworks") },
        { title: t("va.business-plan", "business-plan") },
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
          label={t("va.content", "Content")}
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
