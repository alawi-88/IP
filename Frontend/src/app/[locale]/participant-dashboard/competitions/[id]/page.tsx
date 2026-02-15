"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useRenderFieldType } from "@/hooks/useRenderField";
import { Field, MyCompetition, DynamicForm } from "@/lib/interfaces";
import { useUserStore } from "@/store/user";
import { useQuery } from "@tanstack/react-query";
import { Button, Form, Input, Radio, Select, Spin, Steps, Upload } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import { FaRegUser } from "react-icons/fa";
import { MdOutlineEmail } from "react-icons/md";
import dayjs from "dayjs";
import Comments from "@/components/Comments";

export default function CompetitionPage() {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const searchParams = useSearchParams();
  const user = useUserStore((state) => state.participant);
  const { id } = useParams();
  const [form] = Form.useForm();
  const { renderFieldType } = useRenderFieldType();
  const [currentStep, setCurrentStep] = useState(0);
  const [teamFields, setTeamFields] = useState<Field[]>([]);
  const [registerAs, setRegisterAs] = useState("");
  const [isFormMounted, setIsFormMounted] = useState(false);
  const [formReady, setFormReady] = useState(false);

  //get dynamic form fields values
  const { data: forms, isLoading: isDynamicFormLoading } = useQuery<
    MyCompetition[]
  >({
    queryKey: ["submittedRegistrationForm", id],
    enabled: !!id,
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(
          "/participants/competition-applications",
          {
            params: {
              competition_id: id,
            },
          }
        );

        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });
  const dynamicForm = forms?.find((f) => f?.competition?.id === Number(id));

  // get dynamic form config
  const {
    data: formConfig,
    isLoading: isFormConfigLoading,
    isError,
  } = useQuery({
    queryKey: ["registerConfig", id],
    enabled: !!id,
    queryFn: async () => {
      try {
        const response = await axiosInstance.get("/forms/registration-config", {
          params: {
            competition_id: id,
          },
        });

        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  // handle field value display format
  function handleFieldVal(field: Field) {
    if (!field?.value) return undefined;
    switch (field.type) {
      case "checkbox":
      case "multi_select":
      case "team":
        return field.value.split(",").map((item: any) => String(item.trim()));

      case "date":
        return dayjs(field.value, "YYYY-MM-DD");

      case "time":
        return dayjs(field.value, "HH:mm");

      case "file":
        return [
          {
            uid: field.value,
            name: field.value.split("/").pop(),
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

    // .filter((field) => field.options !== "");
    // .filter((field) => {
    //   const value = field?.value;
    //   return (
    //     value !== undefined &&
    //     value !== null &&
    //     (typeof value !== "string" || value.trim() !== "") &&
    //     (!Array.isArray(value) || value.length > 0)
    //   );
    // })

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

  // add team felids if enabled
  useEffect(() => {
    if (!dynamicForm || !formConfig?.team_fields_enabled) return;

    const fields: Field[] = [
      {
        id: "static_team_name",
        slug: "team_name",
        label: formConfig?.labels?.team_name || t("team-name"),
        type: "text",
        required: true,
        placeholder: undefined,
        hint: undefined,
        fieldClass: "static_field",
        value: dynamicForm?.team_metadata?.team_name,
      },
      {
        id: "static_team_logo",
        slug: "team_logo",
        label: formConfig?.labels?.team_logo || t("team-logo"),
        type: "file",
        required: false,
        placeholder: undefined,
        hint: undefined,
        fieldClass: "static_field",
        value: dynamicForm?.team_metadata?.team_logo,
      },
      {
        id: "static_team_serial",
        slug: "team_serial",
        label: formConfig?.labels?.team_serial || t("team-serial"),
        type: "team",
        required: true,
        placeholder: t("enter-the-serial-number-from-the-members-profile"),
        hint: formConfig?.labels?.help_team_serial || undefined,
        fieldClass: "static_field",
        value: dynamicForm?.team_metadata?.team_serial,
      },
    ];

    setTeamFields(fields);

    setFormValues(fields);
  }, [dynamicForm, formConfig]);

  // set form felids values
  useEffect(() => {
    if (!dynamicForm || isFormConfigLoading) return;

    const stepFields = dynamicForm.form?.steps?.[currentStep]?.fields || [];

    const generalFields = dynamicForm.form?.fields || [];

    if (stepFields.length > 0) setFormValues(stepFields);

    if (generalFields.length > 0) setFormValues(generalFields);

    // handle selected register_as
    if (dynamicForm?.team_metadata?.register_as === "team") {
      setRegisterAs("team");
    }

    // Allow render only after values are set
    setTimeout(() => {
      setFormReady(true);
    }, 0);
  }, [dynamicForm, currentStep, isFormConfigLoading]);

  useEffect(() => {
    setIsFormMounted(true);
  }, []);

  if (isDynamicFormLoading || isFormConfigLoading || !isFormMounted) {
    return <Spin />;
  }

  if (!dynamicForm?.id) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <>
      <Steps
        current={dynamicForm?.status === "pending" ? 0 : 1}
        size="small"
        status={
          dynamicForm?.status === "rejected"
            ? "error"
            : dynamicForm?.status === "approved"
            ? "finish"
            : "process"
        }
        items={[
          {
            title: t("pending"),
          },
          {
            title:
              dynamicForm?.status === "pending"
                ? t("approved")
                : t(dynamicForm.status),
          },
        ]}
      />

      <div className="dashboard-card">
        <h1 className="text-2xl text-primary-900 font-bold">
          {t("join-the-competition")}
        </h1>

        {dynamicForm?.form?.steps?.length > 1 && (
          <Steps
            className="!mb-8"
            current={currentStep}
            labelPlacement="vertical"
            size="small"
            responsive
            items={dynamicForm?.form?.steps?.map((step: any) => ({
              title: step?.name,
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
            {dynamicForm?.status ? t(dynamicForm.status) : "-"}
          </p>
        </div>

        <Form layout="vertical" form={form}>
          <div className={`flex flex-col gap-y-6`}>
            {currentStep === 0 && (
              <>
                <div className="dashboard-card bg-[#F9FAFB] text-secondary">
                  <div className="section_header">
                    {t("personal-information")}
                  </div>
                  <div className="grid grid-cols-1 lg:grid-cols-2 lg:gap-x-6 gap-y-6">
                    <div className="flex gap-x-2 items-center">
                      <FaRegUser className="text-primary-900 flex-shrink-0" />
                      <div className="flex flex-wrap gap-x-2 items-center">
                        <label className="text-sm text-gray-500">
                          {t("full-name")}:
                        </label>
                        <p className="font-bold">{user ? user.name : "-"}</p>
                      </div>
                    </div>
                    <div className="flex gap-x-2 items-center">
                      <MdOutlineEmail className="text-primary-900 flex-shrink-0" />
                      <div className="flex flex-wrap gap-x-2 items-center">
                        <label className="text-sm text-gray-500">
                          {t("email")}:
                        </label>
                        <p className="font-bold break-all">
                          {user ? user.email : "-"}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {(formConfig?.registration_type === "both" ||
                  formConfig?.registration_type === "team") && (
                  <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                    <div className="section_header">{t("team-info")}</div>
                    <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                      {formConfig?.registration_type === "both" && (
                        <>
                          <Form.Item
                            name="register_as"
                            className="col-span-full"
                            label={
                              formConfig?.labels?.register_as ||
                              t("register-as")
                            }
                            required
                            rules={[
                              {
                                required: true,
                              },
                            ]}
                            initialValue={
                              dynamicForm?.team_metadata?.register_as
                            }
                          >
                            <Radio.Group className="checkbox-group" disabled>
                              <Radio value={"individual"}>
                                {formConfig?.labels?.option_individual ||
                                  t("register-as-individual")}
                              </Radio>
                              <Radio value={"team"}>
                                {formConfig?.labels?.option_team ||
                                  t("register-as-team")}
                              </Radio>
                            </Radio.Group>
                          </Form.Item>
                        </>
                      )}
                      {(formConfig?.registration_type === "team" ||
                        registerAs === "team") && (
                        <>{renderDynamicFields(teamFields)}</>
                      )}
                    </div>
                  </div>
                )}

                {dynamicForm?.competition?.tracks?.length > 0 && (
                  <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                    <div className="section_header">{t("track-info")}</div>
                    <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
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
                    </div>
                  </div>
                )}
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

      <Comments
        type="applications"
        typeId={`${dynamicForm.id}`}
        enableReply={dynamicForm.status === "pending"}
        hasComments={dynamicForm.has_comment}
      />
    </>
  );
}
