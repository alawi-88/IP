"use client";

import axiosInstance, { APIError } from "@/axios";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useRouter } from "@/i18n/routing";
import { useUserStore } from "@/store/user";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Form, message, Button, Spin, Radio, Steps, Select, Modal } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams, useSearchParams } from "next/navigation";
import React, { useEffect, useMemo, useState } from "react";
import { useFormScrollToError } from "@/hooks/useFormScrollToError";
import { useClearFormErrors } from "@/hooks/useClearFormErrors";
import { useRenderFieldType } from "@/hooks/useRenderField";
import { useGetValidationRules } from "@/hooks/useGetValidationRules";
import { DynamicForm, Field } from "@/lib/interfaces";
import Empty from "@/components/Empty";
import dayjs from "dayjs";
import { FaRegUser } from "react-icons/fa";
import { MdOutlineEmail } from "react-icons/md";
import { InboxOutlined } from "@ant-design/icons";
import { flushSync } from "react-dom";
import { programsTypes } from "@/lib/constants";
import {
  IoIosCheckmarkCircleOutline,
  IoIosCloseCircleOutline,
} from "react-icons/io";
import { PiStarFourFill } from "react-icons/pi";

export default function Register() {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = Form.useForm();
  const router = useRouter();
  const { id } = useParams();
  const queryClient = useQueryClient();
  const [messageApi, contextHolder] = message.useMessage();
  const user = useUserStore((state) => state.participant);
  const [successModal, setSuccessModal] = useState(false);
  const setFieldsErrors = useSetFieldsErrors(form);
  const scrollToError = useFormScrollToError(form);
  const clearErrors = useClearFormErrors(form);
  const { renderFieldType } = useRenderFieldType();
  const { getValidationRules } = useGetValidationRules();
  const [teamFields, setTeamFields] = useState<Field[]>([]);
  const [registerAs, setRegisterAs] = useState("");
  const [isAgeValid, setIsAgeValid] = useState(true);
  const [currentStep, setCurrentStep] = useState(0);
  const [subTrackOptions, setSubTrackOptions] = useState<
    { label: string; value: number }[]
  >([]);
  const [stepFelidsValues, setStepFelidsValues] = useState({});
  const [conditionalFelids, setConditionalFelids] = useState<{
    slugs: Set<string>;
    fields: Set<string>;
    values: Record<string, any>;
  }>({
    slugs: new Set(),
    fields: new Set(),
    values: {},
  });
  const [lastDraftValues, setLastDraftValues] = useState<Record<string, any>>(
    {}
  );
  const [isDraft, setIsDraft] = useState(false);
  const [isStepSkipRequired, setIsStepSkipRequired] = useState(false);
  const [confirmDraft, setConfirmDraft] = useState(false);
  const [isFormMounted, setIsFormMounted] = useState(false);
  const [formReady, setFormReady] = useState(false);
  const [aiSuggestions, setAiSuggestions] = useState<
    Record<
      string,
      {
        suggestedValue: any;
        originalValue: any;
      }
    >
  >({});
  const [isAiEnhancing, setIsAiEnhancing] = useState(false);

  // get dynamic form fields
  const { data: dynamicForm, isLoading: isDynamicFormLoading } =
    useQuery<DynamicForm>({
      queryKey: ["registrationForm", id],
      enabled: !!id,
      queryFn: async () => {
        try {
          const response = await axiosInstance.get("/forms/registration", {
            params: {
              program_id: id,
            },
          });

          return response?.data;
        } catch (error) {
          console.log(error);
          return null;
        }
      },
    });

  // get dynamic form config
  const { data: formConfig, isLoading: isFormConfigLoading } = useQuery({
    queryKey: ["registerConfig", id],
    enabled: !!id,
    queryFn: async () => {
      try {
        const response = await axiosInstance.get("/forms/registration-config", {
          params: {
            program_id: id,
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
    if (!form || !fields?.length) return;

    const valuesToSet = Object.fromEntries(
      fields
        .map((field) => [field.slug, handleFieldVal(field)])
        .filter(([_, value]) => {
          if (value == null) return false;
          if (typeof value === "string" && value.trim() === "") return false;
          if (Array.isArray(value) && value.length === 0) return false;
          if (typeof value === "object" && Object.keys(value).length === 0)
            return false;
          return true;
        })
    );

    if (Object.keys(valuesToSet).length > 0) {
      form.setFieldsValue(valuesToSet);
      setLastDraftValues((prev) => ({
        ...prev,
        ...valuesToSet,
      }));
    }
  }

  // post the register form
  const { mutate, isPending } = useMutation({
    mutationFn: async (values: any) => {
      const formData = new FormData();

      values = {
        ...stepFelidsValues,
        ...values,
      };

      let payload = {
        ...values,
      };

      if (isDraft) {
        // if (
        //   lastDraftValues &&
        //   JSON.stringify(values) === JSON.stringify(lastDraftValues)
        // ) {
        //   messageApi.warning(t("draft.no-change"));
        //   throw new Error("No data changed");
        // }
        const isEmptyLike = (val: any): boolean => {
          if (val === null || val === undefined) return true;
          if (typeof val === "string" && val.trim() === "") return true;
          if (Array.isArray(val) && val.length === 0) return true;
          if (
            typeof val === "object" &&
            !Array.isArray(val) &&
            Object.keys(val).length === 0
          )
            return true;
          return false;
        };
        if (Object.keys(lastDraftValues).length > 0) {
          const changedPayload: Record<string, any> = {};
          Object.keys(payload).forEach((key) => {
            const prevVal = lastDraftValues[key];
            const newVal = payload[key];
            const isEqual =
              JSON.stringify(prevVal) === JSON.stringify(newVal) ||
              (isEmptyLike(prevVal) && isEmptyLike(newVal)) ||
              (typeof prevVal === "number" &&
                typeof newVal === "string" &&
                prevVal == Number(newVal)) ||
              (typeof prevVal === "string" &&
                typeof newVal === "number" &&
                Number(prevVal) == newVal);

            if (!isEqual) {
              changedPayload[key] = newVal;
            }
          });
          if (Object.keys(changedPayload).length === 0) {
            messageApi.warning(t("draft.no-change"));
            // setIsDraft(false);
            throw new Error("No data changed");
          }
          // payload = {
          //   ...changedPayload,
          // };
        } else {
          const hasValues = Object.values(values).some(
            (val) => !isEmptyLike(val)
          );
          if (!hasValues) {
            messageApi.warning(t("draft.no-data"));
            // setIsDraft(false);
            throw new Error("No data entered");
          }
        }

        if (!confirmDraft) {
          setConfirmDraft(true);
          // setIsDraft(false);
          throw new Error("Wait user confirm");
        }
      }

      // Add required meta fields
      formData.append("program_id", String(id));
      formData.append("form_id", String(dynamicForm?.id));
      formData.append("type", isDraft ? "draft" : "submission");

      payload = {
        participant_name: user?.name,
        participant_email: user?.email,
        has_team:
          formConfig?.registration_type === "team" ||
          values?.register_as === "team"
            ? 1
            : 0,
        ...payload,
      };
      // Apply the payload to the form data
      Object.keys(payload).forEach((key) => {
        const value = payload[key];
        const keyPrefix = `answers[${key}]`;

        if (value != null) {
          if (
            Array.isArray(value) &&
            (value[0]?.originFileObj || value[0]?.url)
          ) {
            const file = value[0];
            if (file?.originFileObj) {
              // New file, append binary
              formData.append(keyPrefix, file.originFileObj);
            } else if (file?.url) {
              // Existing file from draft, send the URL string
              formData.append(keyPrefix, file.url);
            }
          } else if (
            typeof value === "object" &&
            typeof value.format === "function"
          ) {
            const isTimeOnly =
              value.hour() !== 0 ||
              value.minute() !== 0 ||
              value.second() !== 0;
            const format = isTimeOnly ? "HH:mm" : "YYYY-MM-DD";
            formData.append(keyPrefix, value.format(format));
          } else {
            formData.append(keyPrefix, value);
          }
        }
      });

      const response = await axiosInstance.post(
        "/participants/program-applications",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      return response.data;
    },
    onSuccess: (_, variables) => {
      if (isDraft) {
        queryClient.invalidateQueries({
          queryKey: ["registrationForm", id],
        });
        setLastDraftValues((prev) => ({
          ...prev,
          ...variables,
        }));
        if (currentStep === (dynamicForm && dynamicForm.steps.length - 1)) {
          setStepFelidsValues((prev) => ({
            ...prev,
            ...form.getFieldsValue(),
          }));
        }
        messageApi.success(t("draft.saved"));
        setIsDraft(false);
        setConfirmDraft(false);
        router.push(
          `/participant-dashboard${
            dynamicForm?.program?.type &&
            dynamicForm.program.type.slug !== programsTypes[0]
              ? `?program_type=${dynamicForm.program.type.slug}`
              : ""
          }`
        );
      } else {
        setSuccessModal(true);
        setCurrentStep(0);
        setTimeout(() => {
          document
            .querySelector("main")
            ?.scrollTo({ top: 0, behavior: "smooth" });
        }, 0);
      }
      setAiSuggestions({});
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }

      if (
        dynamicForm &&
        dynamicForm?.steps?.length > 0 &&
        error?.response?.data?.errors?.team_serial?.length
      ) {
        setCurrentStep(0);
        setTimeout(() => {
          setFieldsErrors(error);
          scrollToError();
        }, 10);
      }

      // go to current error step
      // if (dynamicForm?.steps?.length > 0) {
      //   const errKeys = Object.keys(error.response.data.errors || {});
      //   for (const errKey of errKeys) {
      //     const foundIndex = dynamicForm?.steps?.findIndex((step:any) =>
      //       step.fields.some((field:Field) => field.slug === errKey)
      //     );
      //     if (foundIndex !== -1) {
      //       setCurrentStep(foundIndex);
      //       console.log("First Matching Error Key" , errKey);
      //       break;
      //     }
      //   }
      // }
      setConfirmDraft(false);
      setFieldsErrors(error);
      scrollToError();
    },
    onMutate: () => {
      clearErrors();
    },
  });

  // delete draft
  const { mutate: deleteDraft, isPending: isDeleteDraftPending } = useMutation({
    mutationFn: async (_) => {
      const formData = new FormData();
      formData.append("application_id", String(dynamicForm?.application_id));
      const response = await axiosInstance.post(
        "/participants/program-applications/reset-draft",
        formData
      );
      return response.data;
    },
    onSuccess: (response) => {
      messageApi.success(response.message);
      setLastDraftValues({});
      setAiSuggestions({});
      queryClient.invalidateQueries({
        queryKey: ["registrationForm", id],
      });
      form.resetFields();
      setRegisterAs("");
      form.setFieldsValue({
        register_as: undefined,
        track: undefined,
        sub_track: undefined,
      });
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  // handle final submit
  async function handleFinalSubmit(values: any) {
    try {
      const allValues = {
        ...stepFelidsValues,
        ...values,
      };
      const allSteps = dynamicForm?.steps || [];
      let firstErrorStep = -1;
      if (allSteps.length && !isDraft) {
        for (let i = 0; i < allSteps.length; i++) {
          const step = allSteps[i];

          for (const field of step.fields) {
            if (field.required) {
              if (field.slug in allValues) {
                const value = allValues[field.slug];
                const isEmpty =
                  value == null ||
                  (typeof value === "string" && value.trim() === "") ||
                  (Array.isArray(value) && value.length === 0);

                if (isEmpty) {
                  firstErrorStep = i;
                  break;
                }
              }
            }
          }
          if (firstErrorStep !== -1) break;
        }
        if (firstErrorStep !== -1) {
          setCurrentStep(firstErrorStep);
          const stepFields = dynamicForm?.steps[firstErrorStep]?.fields || [];
          const fieldNames = stepFields.map((f: Field) => f.slug);
          if (firstErrorStep === 0) {
            if (formConfig?.registration_type === "both") {
              fieldNames.push("register_as");
            }
            if (formConfig?.registration_type === "team") {
              fieldNames.push("team_name", "team_logo", "team_serial");
            }
            if (dynamicForm?.program?.tracks?.length) {
              fieldNames.push("track");
            }
          }
          setTimeout(async () => {
            try {
              await form.validateFields(fieldNames);
            } catch (err) {
              scrollToError();
              messageApi.warning(t("jump-alert"));
            }
          }, 500);

          return;
        }
      }
      mutate(allValues);
    } catch (err) {
      scrollToError();
    }
  }

  // save form as draft
  function saveASDraft() {
    if (isPending) return;
    setIsDraft(true);
    form.submit();
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

    return sortedFields.map((field) => {
      if (["section_header", "paragraph"].includes(field.type)) {
        return (
          <Form.Item
            key={field.id}
            name={field.slug}
            className={field.fieldClass}
            // initialValue={field.label}
            rootClassName="ant-min-h-auto"
          >
            {renderFieldType(field)}
          </Form.Item>
        );
      }
      return (
        <div key={field.id} className="flex flex-col">
          <Form.Item
            name={field.slug}
            className={`${field.fieldClass} ${
              aiSuggestions[field.slug] ? "ai-item-enhanced" : ""
            }`}
            label={field.label}
            rules={getValidationRules(field, isDraft)}
            required={field.required}
            {...(field.type === "file" && {
              valuePropName: "fileList",
              getValueFromEvent: (e) => e?.fileList || [],
            })}
            extra={field.hint}
          >
            {renderFieldType(field, null, false, aiSuggestions[field.slug])}
          </Form.Item>
          {aiSuggestions[field.slug] && (
            <div className="flex flex-wrap items-center justify-end gap-x-3 gap-y-2 mt-0">
              <Button
                type="primary"
                disabled={isPending || isDeleteDraftPending}
                onClick={() => handleSuggestionAction(field.slug, "accept")}
              >
                <IoIosCheckmarkCircleOutline size={24} />
                {t("accept")}
              </Button>
              <Button
                type="default"
                disabled={isPending || isDeleteDraftPending}
                onClick={() => handleSuggestionAction(field.slug, "reject")}
              >
                <IoIosCloseCircleOutline size={24} />
                {t("reject")}
              </Button>
            </div>
          )}
        </div>
      );
    });
  }

  // render dynamic grouping Fields
  function renderDynamicGroupingFields(fields: Field[]) {
    if (!Array.isArray(fields)) return null;

    const sortedFields = fields
      .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
      .filter(shouldShowField);

    const sections: { header?: Field; fields: Field[] }[] = [];
    let currentSection: { header?: Field; fields: Field[] } = { fields: [] };

    for (const field of sortedFields) {
      if (field.type === "section_header") {
        // Start a new section
        if (currentSection.header || currentSection.fields.length) {
          sections.push(currentSection);
        }
        currentSection = { header: field, fields: [] };
      }
      currentSection.fields.push(field);
    }

    if (currentSection.header || currentSection.fields.length) {
      sections.push(currentSection);
    }

    console.log(sections);

    if (!sections.length) {
      if (currentStep > 0) {
        return (
          <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
            <Empty description={t("no-fields-found")} />
          </div>
        );
      }
      return;
    }

    return sections.map((section, index) => (
      <div
        key={index}
        className="dashboard-card dynamic-fields-card bg-[#F9FAFB]"
      >
        <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
          {section.fields.map((field) => {
            if (["section_header", "paragraph"].includes(field.type)) {
              return (
                <Form.Item
                  key={field.id}
                  name={field.slug}
                  className={field?.fieldClass}
                  initialValue={field.label}
                  rootClassName="ant-min-h-auto"
                >
                  {renderFieldType(field)}
                </Form.Item>
              );
            }
            return (
              <Form.Item
                key={field.id}
                name={field.slug}
                className={field.fieldClass}
                label={field.label}
                rules={getValidationRules(field)}
                required={field.required}
                {...(field.type === "file" && {
                  valuePropName: "fileList",
                  getValueFromEvent: (e) => e?.fileList || [],
                })}
                extra={field.hint}
              >
                {renderFieldType(field)}
              </Form.Item>
            );
          })}
        </div>
      </div>
    ));
  }

  // handle next step
  async function handleNextStep() {
    try {
      const currentFields = dynamicForm?.steps[currentStep]?.fields || [];

      const fieldNames = currentFields.map((field: Field) => field.slug);

      if (currentStep === 0) {
        if (formConfig?.registration_type === "both") {
          fieldNames.push("register_as");
        }
        if (formConfig?.registration_type === "team" || registerAs === "team") {
          fieldNames.push("team_name", "team_logo", "team_serial");
        }
        if (dynamicForm?.program?.tracks?.length) {
          fieldNames.push("track");
        }
      }

      await form.validateFields(fieldNames);

      setStepFelidsValues((prev) => ({
        ...prev,
        ...form.getFieldsValue(),
      }));

      setCurrentStep((prev) => prev + 1);

      setTimeout(() => {
        document
          .querySelector("main")
          ?.scrollTo({ top: 0, behavior: "smooth" });
      }, 0);
    } catch (errorInfo) {
      scrollToError();
    }
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

  // handle conditional felids change
  function handleConditionalFelidsChange(changed: any) {
    const changedSlug = Object.keys(changed)[0];
    const changedValue = Object.values(changed)[0];
    const allFields = dynamicForm?.steps?.length
      ? dynamicForm?.steps[currentStep]?.fields
      : dynamicForm?.fields;

    if (!conditionalFelids.slugs.has(changedSlug)) return;

    setTimeout(() => {
      // update stored conditional field values
      setConditionalFelids((prev) => ({
        ...prev,
        values: { ...prev.values, [changedSlug]: changedValue },
      }));

      // build dependency chain iteratively to  handle nested multiple levels deep dependencies
      const result = new Set<string>();
      const queue = [changedSlug];
      while (queue.length) {
        const current = queue.shift();
        allFields.forEach((f: any) => {
          if (
            f.conditional_logic_rules?.some(
              (r: any) => r.field_id === current
            ) &&
            !result.has(f.slug)
          ) {
            result.add(f.slug);
            queue.push(f.slug);
          }
        });
      }
      result.forEach((slug) => {
        const field = allFields.find((f: any) => f.slug === slug);
        if (!field) return;
        const isVisible = shouldShowField(field);
        if (!isVisible) {
          form.resetFields([field.slug]);
        }
      });
    }, 0);
  }

  // get Ai fields
  function getEnhanceAbleFields() {
    const fields: Field[] = [];
    if (dynamicForm?.fields?.length) {
      fields.push(...dynamicForm.fields);
    }
    if (dynamicForm?.steps?.length) {
      fields.push(...(dynamicForm.steps[currentStep]?.fields || []));
    }
    if (teamFields?.length) {
      fields.push(...teamFields);
    }
    const seen = new Set<string>();
    return fields.filter((f) => {
      const shouldInclude =
        f.ai_enhancement_enabled &&
        shouldShowField(f) &&
        !seen.has(f.slug) &&
        f.slug;
      seen.add(f.slug);
      return shouldInclude;
    });
  }

  // handle Ai enhancement
  async function handleAiEnhancement() {
    if (isAiEnhancing) return;
    const enhanceAble = getEnhanceAbleFields();
    if (!enhanceAble.length) {
      messageApi.warning(t("ai.noEnhanceAbleFields"));
      return;
    }
    if (Object.keys(aiSuggestions).length) {
      messageApi.warning(t("ai.notCompleteAction"));
      return;
    }
    setIsDraft(false);
    try {
      await form.validateFields(enhanceAble.map((f) => f.slug));
    } catch {
      scrollToError();
      return;
    }

    const payloadFields = enhanceAble
      .map((field) => {
        const value = form.getFieldValue(field.slug);

        const isEmpty =
          value === undefined ||
          value === null ||
          (typeof value === "string" && value.trim() === "") ||
          (Array.isArray(value) && value.length === 0);

        if (isEmpty) return null;

        return {
          fieldId: String(field.id),
          slug: field.slug,
          label: field.label,
          type: field.type,
          instructions: field.ai_enhancement_instructions,
          context:
            (field.ai_enhancement_context_field &&
              form.getFieldValue(field.ai_enhancement_context_field)) ||
            null,
          value,
        };
      })
      .filter(Boolean);

    if (!payloadFields.length) {
      messageApi.warning(t("ai.noFilledFields"));
      return;
    }

    try {
      setIsAiEnhancing(true);
      const response = await axiosInstance.post("/forms/enhance", {
        formId: String(dynamicForm?.id),
        fields: payloadFields,
      });

      let responseFields = [];

      if (dynamicForm?.fields?.length) {
        responseFields = response?.data?.fields;
      }
      if (dynamicForm?.steps?.length) {
        responseFields = response?.data?.steps[currentStep]?.fields;
      }

      const valuesToSet: Record<string, any> = {};
      const suggestions: Record<string, any> = {};

      responseFields?.forEach((field: any) => {
        const suggested = field?.suggestedValue;
        const originalVal = field?.value ?? form.getFieldValue(field?.slug);
        if (suggested !== null) {
          suggestions[field.slug] = {
            suggestedValue: suggested,
            originalValue: originalVal,
          };
          valuesToSet[field.slug] = suggested;
        }
      });

      if (Object.keys(valuesToSet).length === 0) {
        messageApi.info(t("ai.noSuggestions"));
        return;
      }

      form.setFieldsValue(valuesToSet);
      setAiSuggestions((prev) => ({ ...prev, ...suggestions }));
      try {
        await form.validateFields(Object.keys(valuesToSet));
      } catch {}

      setTimeout(() => {
        const firstAiEnhancedFiled = document.querySelector(
          ".ai-item-enhanced [id]"
        )?.id;
        if (firstAiEnhancedFiled) {
          form.scrollToField(firstAiEnhancedFiled, {
            behavior: "smooth",
            block: "center",
          });
        }
      }, 10);
    } catch (error: any) {
      const errMsg = error?.response?.data?.message;
      messageApi.error(errMsg);
    } finally {
      setIsAiEnhancing(false);
    }
  }

  // handle suggestion result
  function handleSuggestionAction(slug: string, action: string) {
    const suggestion = aiSuggestions[slug];
    if (!suggestion) return;

    if (action === "reject") {
      form.setFieldValue(slug, suggestion.originalValue ?? undefined);
    }
    setAiSuggestions((prev) => {
      const updated = { ...prev };
      delete updated[slug];
      return updated;
    });
  }

  // get sub_tracks
  function getSubTracks(selectedTrackId: any) {
    const selectedTrack = dynamicForm?.program?.tracks?.find(
      (track) => track.id === selectedTrackId
    );
    const subTrack =
      selectedTrack?.sub_tracks?.map((sub) => ({
        label: sub.name,
        value: sub.id,
      })) || [];
    setSubTrackOptions(subTrack);
  }

  // add team felids if enabled
  useEffect(() => {
    if (dynamicForm && formConfig?.team_fields_enabled) {
      const fields: Field[] = [
        {
          id: "static_team_name",
          slug: "team_name",
          label: formConfig?.labels?.team_name || t("team-name"),
          type: "text",
          required: true,
          placeholder: undefined,
          hint: undefined,
          validation_rules: [],
          fieldClass: "static_field",
          value: dynamicForm?.team?.team_name,
        },
        {
          id: "static_team_logo",
          slug: "team_logo",
          label: formConfig?.labels?.team_logo || t("team-logo"),
          type: "file",
          required: false,
          placeholder: undefined,
          hint: undefined,
          validation_rules: [
            {
              rule: "mimes",
              allowed_mimes_string: ".jpg,.jpeg,.png,.webp",
              max_file_size: "1",
            },
          ],
          fieldClass: "static_field",
          value: dynamicForm?.team?.team_logo,
        },
        {
          id: "static_team_serial",
          slug: "team_serial",
          label: formConfig?.labels?.team_serial || t("team-serial"),
          type: "team",
          required: true,
          placeholder: t("enter-the-serial-number-from-the-members-profile"),
          hint: formConfig?.labels?.help_team_serial || undefined,
          validation_rules: [
            {
              rule: "max_team_members",
              value: Number(formConfig?.max_team_members),
            },
            {
              rule: "min_team_members",
              value: Number(formConfig?.min_team_members),
            },
          ],
          fieldClass: "static_field",
          value: dynamicForm?.team?.team_serial,
        },
      ];

      setTeamFields(fields);
      if (dynamicForm.submit_type === "draft") {
        setFormValues(fields);
      }
    }

    if ((formConfig?.min_age || formConfig?.max_age) && user) {
      const userAge = dayjs().diff(dayjs(user?.date_of_birth), "year");

      const isMinValid = formConfig?.min_age
        ? userAge >= formConfig.min_age
        : true;

      const isMaxValid = formConfig?.max_age
        ? userAge <= formConfig.max_age
        : true;

      setIsAgeValid(isMinValid && isMaxValid);
    }
  }, [dynamicForm, formConfig, user]);

  // add conditional felids
  useEffect(() => {
    if (!dynamicForm?.id) return;

    const slugs = new Set<string>();
    const fields = new Set<string>();
    const targetFelids = dynamicForm?.steps?.length
      ? dynamicForm?.steps[currentStep]?.fields
      : dynamicForm?.fields;

    targetFelids.forEach((field: Field) => {
      if (field?.conditional_logic_rules?.length) {
        fields.add(field.slug);
        field.conditional_logic_rules?.forEach((rule) => {
          slugs.add(rule.field_id);
        });
      }
    });

    setConditionalFelids((prev) => ({
      ...prev,
      slugs,
      fields,
    }));
  }, [dynamicForm, currentStep]);

  // set form felids values
  useEffect(() => {
    if (!dynamicForm || isFormConfigLoading) return;
    if (dynamicForm?.submit_type === "draft") {
      const stepFields = dynamicForm?.steps?.[currentStep]?.fields || [];

      const generalFields = dynamicForm?.fields || [];

      if (stepFields.length > 0) setFormValues(stepFields);

      if (generalFields.length > 0) setFormValues(generalFields);

      // handle selected tracks
      const selectedTrack = dynamicForm.program?.tracks.find(
        (track) => track.is_selected
      );
      if (selectedTrack) {
        getSubTracks(selectedTrack?.id);
        setLastDraftValues((prev) => ({
          ...prev,
          track: selectedTrack?.id,
          sub_track: selectedTrack?.sub_tracks.find(
            (sub_track) => sub_track.is_selected
          )?.id,
        }));
      }

      // handle selected register_as
      if (dynamicForm?.team?.register_as) {
        setLastDraftValues((prev) => ({
          ...prev,
          register_as: dynamicForm?.team?.register_as,
        }));
        if (dynamicForm.team.register_as === "team") {
          setRegisterAs("team");
        }
      }

      // Allow render only after values are set
      setTimeout(() => {
        setFormReady(true);
      }, 0);
    }
  }, [dynamicForm, currentStep, isFormConfigLoading]);

  useEffect(() => {
    setIsFormMounted(true);
  }, []);

  if (isDynamicFormLoading || isFormConfigLoading || !isFormMounted) {
    return <Spin />;
  }

  if (!dynamicForm?.id) {
    return <Empty description={t("no-form-found")} />;
  }

  // if (dynamicForm?.program?.is_closed) {
  //   return <Empty description={t("no-result-found")} />;
  // }

  return (
    <>
      {contextHolder}
      <div className="dashboard-card">
        <h1 className="text-2xl text-primary-900 font-bold">
          <span className="me-2">{t("join-the-program")}</span>
          {dynamicForm?.submit_type === "draft" && (
            <span className="inline-block align-middle text-sm py-1 px-2 rounded-lg font-medium border bg-[#F6F7F9] text-[#626262] border-[#DEE1E6]">
              {t("draft.key")}
            </span>
          )}
        </h1>

        {!isAgeValid ? (
          <Empty description={t("invalid-age")} />
        ) : (
          <>
            {dynamicForm?.steps?.length > 1 && (
              <Steps
                className="!mb-8"
                current={currentStep}
                labelPlacement="vertical"
                size="small"
                responsive
                items={dynamicForm?.steps?.map((step: any) => ({
                  title: typeof step?.name === "object" ? (step?.name?.[locale] || step?.name?.en || step?.name?.ar) : step?.name,
                }))}
              />
            )}

            <Form
              form={form}
              onFinish={handleFinalSubmit}
              onFinishFailed={() => {
                scrollToError();
                // setIsDraft(false);
              }}
              layout="vertical"
              onValuesChange={handleConditionalFelidsChange}
            >
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
                            <p className="font-bold">
                              {user ? user.name : "-"}
                            </p>
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
                                    required: isDraft ? false : true,
                                  },
                                ]}
                                initialValue={dynamicForm?.team?.register_as}
                              >
                                <Radio.Group
                                  className="checkbox-group"
                                  onChange={(e) => {
                                    setRegisterAs(e.target.value);
                                  }}
                                >
                                  <Radio value="individual">
                                    {formConfig?.labels?.option_individual ||
                                      t("register-as-individual")}
                                  </Radio>
                                  <Radio value="team">
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

                    {dynamicForm?.program &&
                      dynamicForm?.program?.tracks?.length > 0 && (
                        <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                          <div className="section_header">
                            {t("track-info")}
                          </div>
                          <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                            <Form.Item
                              label={t("track")}
                              name={"track"}
                              rules={[
                                {
                                  required: isDraft ? false : true,
                                },
                              ]}
                              required={true}
                              initialValue={
                                (dynamicForm?.submit_type === "draft" &&
                                  dynamicForm.program.tracks.find(
                                    (track) => track.is_selected
                                  )?.id) ||
                                undefined
                              }
                            >
                              <Select
                                placeholder={t("choose")}
                                notFoundContent={null}
                                showSearch={true}
                                allowClear={true}
                                options={dynamicForm.program.tracks.map(
                                  (track) => ({
                                    label: track.name,
                                    value: track.id,
                                  })
                                )}
                                filterOption={(input, option) =>
                                  (option?.label ?? "")
                                    .toString()
                                    .toLowerCase()
                                    .includes(input.toLowerCase())
                                }
                                onChange={(selectedTrackId) => {
                                  const selectedTrack =
                                    dynamicForm.program?.tracks?.find(
                                      (track) => track.id === selectedTrackId
                                    );
                                  const subTrack =
                                    selectedTrack?.sub_tracks?.map((sub) => ({
                                      label: sub.name,
                                      value: sub.id,
                                    })) || [];
                                  setSubTrackOptions(subTrack);
                                  form.setFieldsValue({ sub_track: undefined });
                                }}
                              />
                            </Form.Item>
                            <Form.Item
                              label={t("sub-track")}
                              name={"sub_track"}
                              initialValue={
                                (dynamicForm?.submit_type === "draft" &&
                                  dynamicForm.program.tracks
                                    .find((track) => track.is_selected)
                                    ?.sub_tracks?.find(
                                      (subTrack) => subTrack.is_selected
                                    )?.id) ||
                                undefined
                              }
                            >
                              <Select
                                placeholder={t("choose")}
                                notFoundContent={null}
                                showSearch={true}
                                allowClear={true}
                                options={subTrackOptions}
                                filterOption={(input, option) =>
                                  (option?.label ?? "")
                                    .toString()
                                    .toLowerCase()
                                    .includes(input.toLowerCase())
                                }
                                disabled={!subTrackOptions.length}
                              />
                            </Form.Item>
                          </div>
                        </div>
                      )}
                  </>
                )}

                {dynamicForm?.fields?.length > 0 && (
                  <>
                    <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                      <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                        {renderDynamicFields(dynamicForm?.fields)}
                      </div>
                    </div>
                    <div className="flex justify-between items-center gap-4 flex-wrap">
                      <div className="flex gap-4 flex-wrap">
                        <Button
                          type="default"
                          htmlType="button"
                          size="large"
                          disabled={isPending || isDeleteDraftPending}
                          onClick={() => router.back()}
                        >
                          {t("cancel")}
                        </Button>
                        {dynamicForm.ai_enhancement?.enabled && (
                          <Button
                            variant="filled"
                            color="default"
                            htmlType="button"
                            size="large"
                            loading={isAiEnhancing}
                            disabled={isPending || isDeleteDraftPending}
                            onClick={handleAiEnhancement}
                          >
                            <PiStarFourFill
                              className="text-primary"
                              size={24}
                            />
                            {t("ai.ai-enhance")}
                          </Button>
                        )}
                      </div>

                      <div className="flex gap-4 flex-wrap">
                        <Button
                          type="default"
                          size="large"
                          // loading={isPending && isDraft}
                          onClick={saveASDraft}
                        >
                          {t("draft.save-as")}
                        </Button>

                        {dynamicForm.submit_type === "draft" && (
                          <Button
                            variant="filled"
                            color="danger"
                            size="large"
                            onClick={() => deleteDraft()}
                            loading={isDeleteDraftPending}
                          >
                            {t("draft.delete-as")}
                          </Button>
                        )}

                        <Button
                          type="primary"
                          htmlType={"submit"}
                          size="large"
                          loading={isPending && !isDraft}
                          onClick={() => {
                            setIsDraft(false);
                          }}
                        >
                          {t("join-the-program")}
                        </Button>
                      </div>
                    </div>
                  </>
                )}

                {dynamicForm?.steps?.length > 0 &&
                  dynamicForm?.steps[currentStep]?.fields?.length > 0 && (
                    <>
                      <div className="dashboard-card dynamic-fields-card bg-[#F9FAFB]">
                        <div className="grid lg:grid-cols-1 lg:gap-x-6 gap-y-2">
                          {renderDynamicFields(
                            dynamicForm?.steps[currentStep]?.fields
                          )}
                        </div>
                      </div>
                      <div className="flex justify-between items-center gap-4 flex-wrap">
                        <div className="flex gap-4 flex-wrap">
                          {dynamicForm?.steps?.length > 1 &&
                          currentStep !== 0 ? (
                            <Button
                              type="default"
                              htmlType="button"
                              size="large"
                              disabled={
                                currentStep === 0 ||
                                isPending ||
                                isDeleteDraftPending
                              }
                              onClick={handlePrevStep}
                            >
                              {t("previous")}
                            </Button>
                          ) : (
                            <Button
                              type="default"
                              htmlType="button"
                              size="large"
                              disabled={isPending || isDeleteDraftPending}
                              onClick={() => router.back()}
                            >
                              {t("cancel")}
                            </Button>
                          )}
                          {dynamicForm.ai_enhancement?.enabled && (
                            <Button
                              variant="filled"
                              color="default"
                              htmlType="button"
                              size="large"
                              loading={isAiEnhancing}
                              disabled={isPending || isDeleteDraftPending}
                              onClick={handleAiEnhancement}
                            >
                              <PiStarFourFill
                                className="text-primary"
                                size={24}
                              />
                              {t("ai.ai-enhance")}
                            </Button>
                          )}
                        </div>

                        <div className="flex gap-4 flex-wrap">
                          <Button
                            type="default"
                            size="large"
                            // loading={isPending && isDraft}
                            onClick={saveASDraft}
                          >
                            {t("draft.save-as")}
                          </Button>

                          {dynamicForm.submit_type === "draft" && (
                            <Button
                              variant="filled"
                              color="danger"
                              size="large"
                              onClick={() => deleteDraft()}
                              loading={isDeleteDraftPending}
                            >
                              {t("draft.delete-as")}
                            </Button>
                          )}

                          {currentStep < dynamicForm.steps.length - 1 && (
                            <Button
                              type="primary"
                              htmlType="button"
                              size="large"
                              onClick={() => {
                                flushSync(() => setIsDraft(false));
                                handleNextStep();
                              }}
                            >
                              {t("next")}
                            </Button>
                          )}
                          {currentStep === dynamicForm.steps.length - 1 && (
                            <Button
                              type="primary"
                              htmlType="submit"
                              size="large"
                              loading={isPending && !isDraft}
                              onClick={() => {
                                setIsDraft(false);
                              }}
                            >
                              {t("join-the-program")}
                            </Button>
                          )}
                        </div>
                      </div>
                    </>
                  )}
              </div>
            </Form>

            <FeedbackModal
              openModal={successModal}
              title={t("you-have-successfully-registered-for-the-program")}
              subtitle={t("you-will-be-notified-of-your-joining-status-soon")}
              btnLabel={t("go-to-programs")}
              type="success"
              onBtnClick={() => {
                queryClient.invalidateQueries({
                  queryKey: ["programs"],
                });
                queryClient.invalidateQueries({
                  queryKey: ["my-programs"],
                });
                router.push(
                  `/participant-dashboard${
                    dynamicForm?.program?.type &&
                    dynamicForm.program.type.slug !== programsTypes[0]
                      ? `?program_type=${dynamicForm.program.type.slug}`
                      : ""
                  }`
                );
              }}
            />

            <Modal
              footer={null}
              open={confirmDraft}
              className="confirm-draft-modal successModal"
              centered
            >
              <div className="relative w-full h-full sm:max-h-[95vh] overflow-y-auto">
                <div className="relative text-center py-10 px-6 gap-y-8 sm:gap-y-10 bg-card flex flex-col items-center">
                  <h3 className="font-semibold text-xl">
                    {t("draft.confirm")}
                  </h3>

                  <div className="modal-actions">
                    <div className="flex items-center justify-center gap-x-6 gap-y-4 flex-col sm:flex-row [&_button]:min-w-[200px]">
                      <Button
                        className="min-w-auto"
                        onClick={() => setConfirmDraft(false)}
                      >
                        {t("no")}
                      </Button>
                      <Button
                        className="min-w-auto"
                        type="primary"
                        onClick={saveASDraft}
                        loading={isPending && isDraft}
                      >
                        {t("yes")}
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </Modal>
          </>
        )}
      </div>
    </>
  );
}
