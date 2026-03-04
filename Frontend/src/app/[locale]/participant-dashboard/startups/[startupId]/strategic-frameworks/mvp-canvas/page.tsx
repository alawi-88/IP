"use client";

import { Form, Input, Spin, message } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "@/i18n/routing";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
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
      startupApi.getVaPage(startupId, "strategic-frameworks", "mvp-canvas"),
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
        "mvp-canvas",
        content
      ),
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "strategic-frameworks", "mvp-canvas"),
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
          label={t("va.userSegment", "User Segment")}
        >
          <Input.TextArea rows={3} placeholder="Define your primary user" />
        </Form.Item>

        <Form.Item
          name="painPoints"
          label={t("va.painPoints", "Pain Points")}
        >
          <Input.TextArea
            rows={3}
            placeholder="What problems do users face?"
          />
        </Form.Item>

        <Form.Item
          name="mvpFeatures"
          label={t("va.mvpFeatures", "MVP Features")}
        >
          <Input.TextArea
            rows={3}
            placeholder="What are the essential features?"
          />
        </Form.Item>

        <Form.Item
          name="solution"
          label={t("va.solution", "Solution")}
        >
          <Input.TextArea
            rows={3}
            placeholder="How does your MVP solve the pain points?"
          />
        </Form.Item>

        <Form.Item
          name="successMetrics"
          label={t("va.successMetrics", "Success Metrics")}
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
