"use client";

import { useParams } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { Button, Card, Form, Input, message, Radio, Slider, Spin } from "antd";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "@/i18n/routing";
import { useEffect, useState } from "react";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import axiosInstance, { APIError } from "@/axios";
import { DynamicForm, MyCompetition } from "@/lib/interfaces";
import Empty from "@/components/Empty";
import EvaluationCommentInput from "@/components/judge-evaluate/EvolutionCommentInput";
import { useFormScrollToError } from "@/hooks/useFormScrollToError";
import { useClearFormErrors } from "@/hooks/useClearFormErrors";

export default function Rate() {
  const { projectId, formId } = useParams<{
    projectId: string;
    formId: string;
  }>();
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [messageApi, contextHolder] = message.useMessage();
  const [form] = Form.useForm();
  const setFieldsErrors = useSetFieldsErrors(form);
  const scrollToError = useFormScrollToError(form);
  const clearErrors = useClearFormErrors(form);
  const [successModal, setSuccessModal] = useState(false);
  const [totalScore, setTotalScore] = useState(0);

  // get evaluation dynamic form
  const { data: dynamicForm, isLoading: isDynamicFormLoading } =
    useQuery<DynamicForm>({
      queryKey: ["evaluationForm", formId],
      enabled: !!formId,
      queryFn: async () => {
        try {
          const response = await axiosInstance.get("/forms/evaluations/form", {
            params: {
              form_id: formId,
            },
          });

          return response?.data;
        } catch (error) {
          console.log(error);
          return null;
        }
      },
    });

  // live calculate total
  function calculateTotal(values: any) {
    if (!dynamicForm?.evaluation_config?.evaluation_criteria) return 0;

    let total = 0;

    dynamicForm.evaluation_config.evaluation_criteria.forEach((criteria) => {
      if ((parseFloat(criteria.weight) || 0) === 0) return;
      const criteriaSlug = criteria.slug;
      const hasSub =
        Array.isArray(criteria.subcriteria) && criteria.subcriteria.length > 0;

      let criteriaScore = 0;

      if (hasSub) {
        const subScores: number[] = [];
        const subWeightedScores: number[] = [];

        criteria.subcriteria.forEach((sub) => {
          const subSlug = `${criteriaSlug}_${sub.slug}`;
          const rawValue = Number(values[subSlug]) || 0;
          const defaultRange = getDefaultRange(sub.scoring_method);
          const subMin = Number(sub.min_score ?? defaultRange.min);
          const subMax = Number(sub.max_score ?? defaultRange.max);
          const subWeight = (parseFloat(sub.weight) || 0) / 100;

          if (subMax === subMin) return;

          let normalized = 0;
          if (sub.scoring_method === "yes_no") {
            normalized = rawValue ? 100 : 0;
          } else if (criteria.aggregation_method === "average") {
            normalized = ((rawValue - subMin) / (subMax - subMin)) * 100;
          } else {
            normalized = (rawValue / subMax) * 100;
          }

          //clamp normalized to [0,100]
          normalized = Math.min(100, Math.max(0, normalized));

          if (criteria.aggregation_method === "average") {
            subScores.push(normalized);
          } else {
            subWeightedScores.push(normalized * subWeight);
          }
        });

        if (criteria.aggregation_method === "average") {
          criteriaScore = subScores.length
            ? subScores.reduce((a, b) => a + b, 0) / subScores.length
            : 0;
        } else {
          criteriaScore = subWeightedScores.reduce((a, b) => a + b, 0);
        }
      } else {
        const rawValue = Number(values[criteriaSlug]) || 0;
        const defaultRange = getDefaultRange(criteria.scoring_method);
        const min = Number(criteria.min_score ?? defaultRange.min);
        const max = Number(criteria.max_score ?? defaultRange.max);

        if (criteria.scoring_method === "yes_no") {
          criteriaScore = rawValue ? 100 : 0;
        } else {
          criteriaScore = ((rawValue - min) / (max - min)) * 100;
          //clamp normalized to [0,100]
          criteriaScore = Math.min(100, Math.max(0, criteriaScore));
        }
      }

      // weight criteria
      const weight = (parseFloat(criteria.weight) || 0) / 100;
      total += criteriaScore * weight;
    });

    return parseFloat(total.toFixed(2));
  }

  // post the evaluation form
  const { mutate, isPending } = useMutation({
    mutationFn: async (values: any) => {
      const payload: any = {
        form_id: formId,
        project_id: projectId,
        stage_id: dynamicForm?.competition?.current_stage_slug?.startsWith(
          "evaluation"
        )
          ? dynamicForm?.competition?.current_stage_id
          : null,
        answers: {},
      };

      const criteriaList =
        dynamicForm?.evaluation_config?.evaluation_criteria || [];

      criteriaList.forEach((criteria) => {
        const criteriaSlug = criteria.slug;
        const mainWeight = (parseFloat(criteria.weight) || 0) / 100;
        const hasSub =
          Array.isArray(criteria.subcriteria) &&
          criteria.subcriteria.length > 0;

        let criteriaScore = 0;
        const subScores: number[] = [];
        const subWeightedScores: number[] = [];
        const questions: Record<string, any> = {};

        if (hasSub) {
          criteria.subcriteria.forEach((sub) => {
            const subSlug = `${criteriaSlug}_${sub.slug}`;
            const rawValue = Number(values[subSlug]) || 0;
            const defaultSubRange = getDefaultRange(sub.scoring_method);
            const subMin = Number(sub.min_score ?? defaultSubRange.min);
            const subMax = Number(sub.max_score ?? defaultSubRange.max);
            const subWeight = (parseFloat(sub.weight) || 0) / 100;

            // Skip invalid range
            if (subMax === subMin) return;

            // Normalize to 0–100
            let normalizedScore = 0;
            if (sub.scoring_method === "yes_no") {
              normalizedScore = rawValue ? 100 : 0;
            } else if (criteria.aggregation_method === "average") {
              normalizedScore = ((rawValue - subMin) / (subMax - subMin)) * 100;
            } else {
              normalizedScore = (rawValue / subMax) * 100;
            }
            const roundedNormalized = parseFloat(normalizedScore.toFixed(2));
            if (criteria.aggregation_method === "average") {
              subScores.push(roundedNormalized);
            } else {
              subWeightedScores.push(roundedNormalized * subWeight);
            }

            questions[subSlug] = rawValue;

            const commentKey = `${subSlug}_comment`;
            if (values[commentKey]) {
              questions[commentKey] = values[commentKey];
            }
          });

          let aggregated = 0;
          if (criteria.aggregation_method === "average") {
            aggregated = subScores.length
              ? subScores.reduce((acc, val) => acc + val, 0) / subScores.length
              : 0;
          } else {
            // default: weighted sum
            aggregated = subWeightedScores.reduce((acc, val) => acc + val, 0);
          }

          criteriaScore = parseFloat(aggregated.toFixed(2));

          payload.answers[criteriaSlug] = criteriaScore;
          payload.answers[`${criteriaSlug}_questions`] = questions;
        } else {
          // Simple criteria: normalize raw score
          const rawValue = Number(values[criteriaSlug]) || 0;
          const defaultRange = getDefaultRange(criteria.scoring_method);
          const minScore = Number(criteria.min_score ?? defaultRange.min);
          const maxScore = Number(criteria.max_score ?? defaultRange.max);

          let normalized = 0;
          if (criteria.scoring_method === "yes_no") {
            normalized = rawValue ? 100 : 0;
          } else {
            normalized = ((rawValue - minScore) / (maxScore - minScore)) * 100;
          }
          criteriaScore = parseFloat(normalized.toFixed(2));
          payload.answers[criteriaSlug] = criteriaScore;
        }

        const commentKey = `${criteriaSlug}_comment`;
        if (values[commentKey]) {
          payload.answers[commentKey] = values[commentKey];
        }
      });

      if (values.final_comment) {
        payload.answers.final_comment = values.final_comment;
      }

      const response = await axiosInstance.post("/judges/evaluations", payload);
      return response.data;
    },

    onSuccess: () => {
      setSuccessModal(true);
      setTimeout(() => {
        document
          .querySelector("main")
          ?.scrollTo({ top: 0, behavior: "smooth" });
      }, 0);
    },

    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
      setFieldsErrors(error);
      scrollToError();
    },

    onMutate: () => {
      clearErrors();
    },
  });

  // render scoring options
  function renderScoringInput(criteria: any, parent?: any) {
    const parentSlug = parent?.slug || "";
    const slug = criteria.slug;
    switch (criteria.scoring_method) {
      case "yes_no":
        const yesPoints = 100;
        return (
          <Radio.Group className="checkbox-group !grid lg:grid-cols-2 !gap-4">
            <Radio value={yesPoints}>{t("yes")}</Radio>
            <Radio value={0}>{t("no")}</Radio>
          </Radio.Group>
        );
      case "percentage":
        const minPercentage = 0;
        const MaxPercentage = 100;
        return (
          <Input
            type="number"
            min={minPercentage}
            max={MaxPercentage}
            addonAfter="%"
            placeholder={`${minPercentage}-${MaxPercentage} %`}
            className="w-full"
          />
        );
      case "custom_range":
      case "multiple_choice":
        const minRange = parseInt(criteria.min_score) || 0;
        const MaxRange = parseInt(criteria.max_score) || 100;
        return (
          <Slider
            min={minRange}
            max={MaxRange}
            tooltip={{ formatter: (value) => `${value}` }}
            className="w-full"
            reverse={locale === "ar" ? true : false}
          />
        );

      case "numeric_scale":
      default:
        const numericMax = 5;
        return (
          <Radio.Group className="!flex !gap-4 !flex-wrap !items-center">
            {Array.from({ length: numericMax }, (_, i) => i + 1).map((val) => (
              <Radio.Button
                key={`${parentSlug}_${slug}_${val}`}
                value={val}
                rootClassName="evaluate"
                title={`${val}/${numericMax}`}
              >
                {val}
              </Radio.Button>
            ))}
          </Radio.Group>
        );
    }
  }

  // add validation rules
  function getValidationRules(criteria: any) {
    const rules = [];
    if (criteria.scoring_method === "percentage") {
      const min = parseInt(criteria.min_score) || 0;
      const max = parseInt(criteria.max_score) || 100;
      rules.push({
        min,
        max,
        message: t("range-value", { min, max }),
        transform: (v: any) => (v === "" || v == null ? v : Number(v)),
      });
    }

    return rules;
  }

  // get default range based on sorting method
  function getDefaultRange(method?: string) {
    switch (method) {
      case "numeric_scale":
        return { min: 1, max: 5 };
      case "percentage":
        return { min: 0, max: 100 };
      default:
        return { min: 0, max: 1 }; // fallback default
    }
  }

  if (isDynamicFormLoading) {
    return <Spin className="w-full flex justify-center" />;
  }

  if (!dynamicForm?.id || !formId) {
    return <Empty description={t("no-evolution-form-found")} />;
  }

  return (
    <div className="mb-[80px]">
      {contextHolder}
      <div className="flex justify-between items-center gap-x-6 gap-y-4 max-xl:flex-wrap pb-6">
        <h1 className="m-0 text-foreground font-bold text-2xl ">
          {t("rate-project")}
        </h1>
        <div className="total_rate">
          <span className="text-[#626262] font-meduim">{t("total-rate")}:</span>{" "}
          <span className="font-bold">{totalScore.toFixed(2)}%</span>
        </div>
      </div>
      <Card className="w-full h-fit">
        <Form
          layout="vertical"
          form={form}
          onFinish={mutate}
          onFinishFailed={scrollToError}
          onValuesChange={(changed, allValues) => {
            const score = calculateTotal(allValues);
            setTotalScore(score);
          }}
        >
          <div className="flex flex-col gap-y-8">
            {dynamicForm?.evaluation_config?.evaluation_criteria?.map(
              (criteria) => (
                <div
                  key={criteria.slug}
                  className="criteria-item flex flex-col gap-y-6"
                >
                  <div className="criteria-title font-bold">
                    <h2 className="text-foreground inline me-1">
                      {criteria.label}
                    </h2>
                    <span
                      className={`text-secondary inline-block ${
                        parseInt(criteria.weight || "0") == 0
                          ? "align-middle text-sm py-1 px-2 rounded-lg font-medium border bg-[#F6F7F9] !text-[#626262] border-[#DEE1E6]"
                          : ""
                      }`}
                    >
                      {parseInt(criteria.weight || "0") == 0
                        ? t("not-included-evaluation")
                        : `(${criteria.weight}%)`}
                    </span>
                    {criteria?.subcriteria.length === 0 ? (
                      <div className="ant-form-item-label inline-block">
                        <label className="ant-form-item-required mx-[2px]"></label>
                      </div>
                    ) : null}
                  </div>
                  {criteria?.subcriteria?.length ? (
                    <>
                      <div className="subcriteria-list flex flex-col gap-y-6">
                        {criteria?.subcriteria.map((qa) => (
                          <div
                            className="subcriteria-item flex flex-col gap-y-3"
                            key={`${criteria.slug}_${qa.slug}`}
                          >
                            <Form.Item
                              className="!mb-0"
                              name={`${criteria.slug}_${qa.slug}`}
                              label={
                                <>
                                  <span className="me-1 inline">
                                    {qa.label}
                                  </span>
                                  {parseInt(criteria.weight || "0") != 0 &&
                                  qa.weight ? (
                                    <span className="text-secondary inline-block">
                                      {` (${qa.weight}%) `}
                                    </span>
                                  ) : null}
                                </>
                              }
                              labelCol={{ className: "[&_label]:!block" }}
                              rules={[
                                { required: true },
                                ...getValidationRules(qa),
                              ]}
                            >
                              {renderScoringInput(qa, criteria)}
                            </Form.Item>
                            {qa.enable_comments && (
                              <EvaluationCommentInput
                                name={`${criteria.slug}_${qa.slug}_comment`}
                                label={t("evaluation-comment.sub-criteria")}
                                maxChars={Number(qa.comment_max_chars)}
                                rows={1}
                                className="lg:max-w-[505px]"
                              />
                            )}
                          </div>
                        ))}
                      </div>
                    </>
                  ) : (
                    <div className="subcriteria-item flex flex-col gap-y-3">
                      <Form.Item
                        className="!mb-0"
                        name={`${criteria.slug}`}
                        rules={[
                          { required: true },
                          ...getValidationRules(criteria),
                        ]}
                      >
                        {renderScoringInput(criteria)}
                      </Form.Item>
                    </div>
                  )}
                  {criteria.enable_comments_criteria && (
                    <div className="criteria-comment">
                      <EvaluationCommentInput
                        name={`${criteria.slug}_comment`}
                        label={t("evaluation-comment.main-criteria")}
                        maxChars={Number(criteria.comment_max_chars)}
                        rows={2}
                      />
                    </div>
                  )}
                </div>
              )
            )}
            {dynamicForm?.evaluation_config?.enable_overall_comments && (
              <EvaluationCommentInput
                name={`final_comment`}
                label={t("evaluation-comment.project")}
                maxChars={500}
                rows={2}
              />
            )}
          </div>

          <div className="floating-bar fixed w-full bottom-0 left-0 bg-card py-6 px-6 sm:px-10 xl:px-14 flex justify-end z-10">
            <div className="flex gap-x-6">
              <Button
                type="default"
                onClick={() =>
                  router.push(`/judge/judge-dashboard/projects/${projectId}`)
                }
                disabled={isPending}
              >
                {t("cancel")}
              </Button>
              <Button htmlType="submit" type="primary" loading={isPending}>
                {t("submit-evaluation")}
              </Button>
            </div>
          </div>
        </Form>
      </Card>
      <FeedbackModal
        openModal={successModal}
        title={t("evaluation-submitted-successfully")}
        btnLabel={t("back-to-projects")}
        type="success"
        onBtnClick={() => {
          queryClient.invalidateQueries({
            queryKey: ["judge-project", projectId],
            refetchType: "all",
          });
          queryClient.invalidateQueries({
            queryKey: ["judge-project-evaluations", projectId],
            refetchType: "all",
          });
          queryClient.invalidateQueries({
            queryKey: ["projects"],
            refetchType: "all",
          });
          setSuccessModal(false);
          router.push(`/judge/judge-dashboard`);
        }}
      />
    </div>
  );
}
