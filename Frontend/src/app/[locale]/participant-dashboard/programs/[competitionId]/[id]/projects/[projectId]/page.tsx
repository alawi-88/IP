"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useRenderFieldType } from "@/hooks/useRenderField";
import { Field, MyCompetition } from "@/lib/interfaces";
import { useUserStore } from "@/store/user";
import { useQuery } from "@tanstack/react-query";
import { Button, Form, Input, Radio, Select, Spin, Steps, Upload } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import dayjs from "dayjs";
import Comments from "@/components/Comments";

export default function ProjectDetails() {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const user = useUserStore((state) => state.participant);
  const { id, projectId } = useParams<{ id: string; projectId: string }>();
  const [form] = Form.useForm();
  const { renderFieldType } = useRenderFieldType();
  const [currentStep, setCurrentStep] = useState(0);
  const [isFormMounted, setIsFormMounted] = useState(false);
  const searchParams = useSearchParams();
  const formId = searchParams.get("formId");
  const [formReady, setFormReady] = useState(false);

  //get dynamic form fields values
  const { data: dynamicForm, isLoading: isDynamicFormLoading } =
    useQuery<MyCompetition>({
      queryKey: ["submittedProjectForm", projectId],
      queryFn: async () => {
        const response = await axiosInstance.get(
          `/participants/projects/${projectId}`,
          {
            params: {
              application_id: id,
            },
          }
        );
        return response.data.data;
      },
    });

  // handle field value display format
  function handleFieldVal(field: Field) {
    if (!field?.value) return undefined;
    switch (field.type) {
      case "checkbox":
      case "multi_select":
      case "team":
        return field.value.split(",").map((item: any) => item.trim());

      case "date":
        return dayjs(field.value, "YYYY-MM-DD");

      case "time":
        return dayjs(field.value, "HH:mm");

      case "file":
        return [
          {
            uid: field.value,
            name:
              (typeof field.value === "string" &&
                field.value.split("/").pop()) ||
              "",
            url: field.value,
            status: "done",
          },
        ];

      default:
        return field.value.trim();
    }
  }

  // set form values
  function setFormValues(fields?: Field[]) {
    if (!fields?.length) return;
    form.setFieldsValue(
      Object.fromEntries(
        fields.map((field) => [field.slug, handleFieldVal(field)])
      )
    );
  }

  // render dynamic Fields
  function renderDynamicFields(fields: Field[]) {
    if (!Array.isArray(fields)) return null;

    const sortedFields = fields
      .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
      .filter(shouldShowField);

    if (!sortedFields.length) {
      if (currentStep > 0) {
        return <Empty description={t("no-fields-found")} />;
      }
      return;
    }

    return sortedFields.map((field: Field) => {
      if (["section_header", "paragraph"].includes(field.type)) {
        return (
          <Form.Item
            key={field.id}
            name={field.slug}
            className={field.fieldClass}
            initialValue={field.label}
            rootClassName="ant-min-h-auto"
          >
            {renderFieldType(field, false, true)}
          </Form.Item>
        );
      }
      return (
        <Form.Item
          key={field.id}
          name={field.slug}
          className={field.fieldClass}
          label={field.label}
          {...(field.type === "file" && {
            valuePropName: "fileList",
            getValueFromEvent: (e) => e?.fileList || [],
          })}
          extra={field.hint}
          required={field.required ? true : false}
        >
          {renderFieldType(field, false, true)}
        </Form.Item>
      );
    });
  }

  // handle next step
  function handleNextStep() {
    setCurrentStep((prev) => prev + 1);

    setTimeout(() => {
      document.querySelector("main")?.scrollTo({ top: 0, behavior: "smooth" });
    }, 0);
  }

  // handle previous step
  function handlePrevStep() {
    setCurrentStep((prev) => prev - 1);
    setTimeout(() => {
      document.querySelector("main")?.scrollTo({ top: 0, behavior: "smooth" });
    }, 0);
  }

  // handle show or hide felid based on conditional_logic_rules
  function shouldShowField(field: Field): boolean {
    if (!field.conditional_logic_rules?.length) return true;

    return field.conditional_logic_rules.some((rule) => {
      if (!rule.field_id) return false;
      const fieldName = rule.field_id;
      const hasErrors = form?.getFieldError(fieldName)?.length > 0;
      if (hasErrors) return false;
      const targetValue = form?.getFieldValue(fieldName);
      const requiredValues = rule.values
        .map((v) => v.value)
        .filter((v) => v !== null && v !== undefined && v !== "")
        .map((v) => String(v).trim().toLowerCase());

      if (Array.isArray(targetValue)) {
        return requiredValues.some((v) =>
          // targetValue.includes(String(v).trim())
          targetValue
            .map((tv: any) => String(tv).trim().toLowerCase())
            .includes(v)
        );
      }

      return requiredValues.includes(String(targetValue).trim().toLowerCase());
    });
  }

  // set form felids values
  useEffect(() => {
    if (!dynamicForm) return;

    const stepFields = dynamicForm.form?.steps?.[currentStep]?.fields || [];

    const generalFields = dynamicForm.form?.fields || [];

    if (stepFields.length > 0) setFormValues(stepFields);

    if (generalFields.length > 0) setFormValues(generalFields);

    // Allow render only after values are set
    setTimeout(() => {
      setFormReady(true);
    }, 0);
  }, [dynamicForm, currentStep]);

  useEffect(() => {
    setIsFormMounted(true);
  }, []);

  if (isDynamicFormLoading || !isFormMounted) {
    return <Spin />;
  }

  if (!dynamicForm?.id) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <>
      <div className="dashboard-card">
        <h1 className="text-2xl text-primary-900 font-bold">
          {t("project-details")}
        </h1>

        {dynamicForm?.form?.steps?.length > 1 && (
          <Steps
            className="!mb-8"
            current={currentStep}
            labelPlacement="vertical"
            size="small"
            responsive
            items={dynamicForm?.form?.steps?.map((step: any) => ({
              title: typeof step?.name === "object" ? (step?.name?.[locale] || step?.name?.en || step?.name?.ar) : step?.name,
            }))}
          />
        )}

        <div className="flex flex-col gap-y-2 text-[#98A2B3] text-sm font-medium">
          <p>
            {t("registration-date")}:{" "}
            {dynamicForm?.created_at
              ? new Date(dynamicForm.created_at).toLocaleDateString(locale)
              : "-"}{" "}
          </p>

          <p>
            {t("application-status")}:{" "}
            {dynamicForm.submit_type === "draft"
              ? t("draft.key")
              : dynamicForm?.status
              ? t(dynamicForm.status)
              : "-"}
          </p>
        </div>

        <Form layout="vertical" form={form}>
          <div className={`flex flex-col gap-y-6`}>
            {currentStep === 0 && (
              <>
                <div className="dashboard-card bg-[#F9FAFB] text-secondary">
                  <div className="section_header">{t("basic-information")}</div>

                  <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                    <Form.Item
                      name="project_name"
                      label={t("project-name")}
                      rules={[
                        {
                          required: true,
                        },
                      ]}
                      initialValue={dynamicForm?.metadata?.project_name}
                    >
                      <Input placeholder={t("enter-project-name")} disabled />
                    </Form.Item>

                    {dynamicForm?.competition?.tracks?.length > 0 && (
                      <>
                        <Form.Item
                          label={t("track")}
                          name={"track"}
                          required
                          initialValue={
                            dynamicForm.competition.tracks.find(
                              (track) => track.is_selected
                            )?.name
                          }
                        >
                          <Select placeholder={t("choose")} disabled />
                        </Form.Item>
                        <Form.Item
                          label={t("sub-track")}
                          name={"sub_track"}
                          initialValue={
                            dynamicForm.competition.tracks
                              .find((track) => track.is_selected)
                              ?.sub_tracks?.find(
                                (subTrack) => subTrack.is_selected
                              )?.name
                          }
                        >
                          <Select placeholder={t("choose")} disabled />
                        </Form.Item>
                      </>
                    )}
                  </div>
                </div>
              </>
            )}

            {dynamicForm?.form?.fields?.length > 0 && (
              <>
                <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                  <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                    {renderDynamicFields(dynamicForm.form.fields)}
                  </div>
                </div>
                <div className="flex justify-between items-center">
                  <Button
                    type="default"
                    htmlType="button"
                    size="large"
                    onClick={() => router.back()}
                  >
                    {t("back")}
                  </Button>
                </div>
              </>
            )}

            {dynamicForm?.form?.steps?.length > 0 &&
              dynamicForm?.form?.steps[currentStep]?.fields?.length > 0 && (
                <>
                  <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                    <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                      {renderDynamicFields(
                        dynamicForm?.form?.steps[currentStep]?.fields
                      )}
                    </div>
                  </div>
                  <div className="flex justify-between items-center">
                    {dynamicForm?.form?.steps?.length > 1 &&
                    currentStep !== 0 ? (
                      <Button
                        disabled={currentStep === 0}
                        type="default"
                        htmlType="button"
                        size="large"
                        onClick={handlePrevStep}
                      >
                        {t("previous")}
                      </Button>
                    ) : (
                      <Button
                        type="default"
                        htmlType="button"
                        size="large"
                        onClick={() => router.back()}
                      >
                        {t("back")}
                      </Button>
                    )}

                    {currentStep < dynamicForm.form.steps.length - 1 && (
                      <Button
                        type="primary"
                        htmlType="button"
                        size="large"
                        onClick={handleNextStep}
                      >
                        {t("next")}
                      </Button>
                    )}
                  </div>
                </>
              )}
          </div>
        </Form>
      </div>
      {dynamicForm.submit_type != "draft" && (
        <Comments
          type="projects"
          typeId={`${projectId}`}
          enableReply={dynamicForm.status === "pending"}
          hasComments={dynamicForm.has_comment}
        />
      )}
    </>
  );
}
