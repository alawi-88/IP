"use client";

import { Form, Input, Button, Spin, message, Row, Col, Card, Space } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useCallback, useEffect } from "react";
import VaPageLayout from "@/components/va/VaPageLayout";
import AiGenerateButton from "@/components/va/AiGenerateButton";
import * as startupApi from "@/config/startup-api";
import { FiPlus, FiX } from "react-icons/fi";

export default function SwotPage() {
  const t = useTranslations();
  const params = useParams();
  const startupId = params.startupId as string;
  const [form] = Form.useForm();
  const [saveTimeout, setSaveTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCompleted, setIsCompleted] = useState(false);

  const { data: page, isLoading } = useQuery({
    queryKey: ["startup", startupId, "strategic-frameworks", "swot"],
    queryFn: () =>
      startupApi.getVaPage(startupId, "strategic-frameworks", "swot")});

  useEffect(() => {
    if (page) {
      const swotData = {
        strengths: page.content?.strengths || ["", "", ""],
        weaknesses: page.content?.weaknesses || ["", "", ""],
        opportunities: page.content?.opportunities || ["", "", ""],
        threats: page.content?.threats || ["", "", ""],
      };
      form.setFieldsValue(swotData);
      setIsCompleted(Number(page.completion_percentage) >= 100);
    }
  }, [page, form]);

  const updateMutation = useMutation({
    mutationFn: (content: any) =>
      startupApi.updateVaPage(
        startupId,
        "strategic-frameworks",
        "swot",
        content
      )
  });

  const completeMutation = useMutation({
    mutationFn: () =>
      startupApi.completeVaPage(startupId, "strategic-frameworks", "swot"),
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
        "swot",
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

  const addItem = (quadrant: string) => {
    const current = form.getFieldValue(quadrant) || [];
    form.setFieldValue(quadrant, [...current, ""]);
  };

  const removeItem = (quadrant: string, index: number) => {
    const current = form.getFieldValue(quadrant) || [];
    form.setFieldValue(
      quadrant,
      current.filter((_: any, i: number) => i !== index)
    );
    const values = form.getFieldsValue();
    updateMutation.mutate(values);
  };

  if (isLoading) {
    return <Spin />;
  }

  const SwotQuadrant = ({
    title,
    quadrant,
    color
  }: {
    title: string;
    quadrant: string;
    color: string;
  }) => (
    <Card
      className={`${color} h-full`}
      title={title}
      extra={
        <AiGenerateButton
          fieldLabel={title}
          onGenerate={(prompt) => handleAiGenerate(quadrant, prompt)}
          onAccept={(content) => {
            const items = content
              .split("\n")
              .filter((item: string) => item.trim());
            form.setFieldValue(quadrant, items);
            updateMutation.mutate(form.getFieldsValue());
          }}
        />
      }
    >
      <Form.Item noStyle>
        <Space direction="vertical" className="w-full">
          <Form.Item
            noStyle
            name={quadrant}
            valuePropName="value"
          >
            <div className="space-y-2">
              {(form.getFieldValue(quadrant) || []).map(
                (item: string, index: number) => (
                  <div key={index} className="flex gap-2">
                    <Input
                      placeholder={`Enter ${title.toLowerCase()}`}
                      value={item}
                      onChange={(e) => {
                        const current = form.getFieldValue(quadrant) || [];
                        current[index] = e.target.value;
                        form.setFieldValue(quadrant, [...current]);
                        handleFieldChange({});
                      }}
                      className="flex-1"
                    />
                    <Button
                      type="text"
                      danger
                      icon={<FiX size={16} />}
                      onClick={() => removeItem(quadrant, index)}
                    />
                  </div>
                )
              )}
              <Button
                type="dashed"
                block
                icon={<FiPlus size={14} />}
                onClick={() => addItem(quadrant)}
              >
                {t("add", "Add")}
              </Button>
            </div>
          </Form.Item>
        </Space>
      </Form.Item>
    </Card>
  );

  return (
    <VaPageLayout
      title={t("va.swot", "SWOT Analysis")}
      breadcrumbs={[
        { title: t("va.strategicFrameworks", "Strategic Frameworks") },
        { title: t("va.swot", "SWOT Analysis") },
      ]}
      completionPercentage={isCompleted ? 100 : 0}
      isCompleted={isCompleted}
      onMarkComplete={() => completeMutation.mutateAsync()}
    >
      <Form form={form} layout="vertical" className="w-full">
        <Row gutter={[16, 16]}>
          <Col xs={24} sm={12}>
            <SwotQuadrant
              title={t("va.strengths", "Strengths")}
              quadrant="strengths"
              color="bg-green-50"
            />
          </Col>
          <Col xs={24} sm={12}>
            <SwotQuadrant
              title={t("va.weaknesses", "Weaknesses")}
              quadrant="weaknesses"
              color="bg-red-50"
            />
          </Col>
          <Col xs={24} sm={12}>
            <SwotQuadrant
              title={t("va.opportunities", "Opportunities")}
              quadrant="opportunities"
              color="bg-blue-50"
            />
          </Col>
          <Col xs={24} sm={12}>
            <SwotQuadrant
              title={t("va.threats", "Threats")}
              quadrant="threats"
              color="bg-yellow-50"
            />
          </Col>
        </Row>
      </Form>
    </VaPageLayout>
  );
}
