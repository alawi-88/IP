"use client";

import { Button, Card, Form, Radio, RadioChangeEvent } from "antd";
import { useRouter } from "@/i18n/routing";
import { useState } from "react";

import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import axiosInstance, { APIError } from "@/axios";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useTranslations } from "next-intl";

const EvaluateOptions = ({
  onChange,
}: {
  onChange: (e: RadioChangeEvent) => void;
}) => (
  <Radio.Group
    rootClassName="!flex !gap-4 !flex-wrap !items-center"
    onChange={onChange}
  >
    <Radio.Button
      rootClassName="evaluate"
      value="1"
    >
      1
    </Radio.Button>
    <Radio.Button
      rootClassName="evaluate"
      value="2"
    >
      2
    </Radio.Button>
    <Radio.Button
      rootClassName="evaluate"
      value="3"
    >
      3
    </Radio.Button>
    <Radio.Button
      rootClassName="evaluate"
      value="4"
    >
      4
    </Radio.Button>
    <Radio.Button
      rootClassName="evaluate"
      value="5"
    >
      5
    </Radio.Button>
  </Radio.Group>
);

export default function LinearEvaluationForm({
  projectId,
}: {
  projectId: string;
}) {
  const [form] = Form.useForm();
  const [successModal, setSuccessModal] = useState(false);
  const setFieldsErrors = useSetFieldsErrors(form);
  const t = useTranslations();
  const router = useRouter();
  const queryClient = useQueryClient();

  const { mutate, isPending } = useMutation({
    mutationFn: async (data: any) => {
      const formattedData = {
        project_id: projectId,
        path_slug: "business-solutions-track",
        problem_solution: data.problem_solution,
        feasibility: data.feasibility,
        competitive_advantage: data.competitive_advantage,
        target_audience: data.target_audience,
        competitors: data.competitor_analysis,
        business_model: data.business_model,
        scalability: data.scalability,
        presentation_clarity: data.presentation_clarity,
        project_work: data.project_clarity,
      };

      const response = await axiosInstance.post(
        "/judges/evaluations",
        formattedData,
        {
          headers: {
            "Content-Type": "application/json",
          },
        }
      );
      return response.data;
    },

    onSuccess: () => {
      setSuccessModal(true);
      queryClient.invalidateQueries({
        queryKey: ["projects"],
      });
      queryClient.invalidateQueries({
        queryKey: ["project", projectId],
      });
      queryClient.invalidateQueries({
        queryKey: ["evaluation", projectId],
      });
    },

    onError: (error: APIError) => {
      setFieldsErrors(error);
    },
  });

  const onSubmit = (data: any) => {
    mutate(data);
  };

  return (
    <>
      <Card className="w-full h-fit relative">
        <Form
          layout="vertical"
          form={form}
          onFinish={onSubmit}
        >
          <div className="flex flex-col gap-y-8 pb-20 mb-10">
            <h1 className="m-0 text-foreground font-medium text-xl ">
              {t("business-solution-track")}
            </h1>
            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("is-the-solution-related-to-the-problem-or-challenge")}
                  <span className="text-[#91B9B5] font-medium"> (15٪)</span>
                </h3>
              }
              name="problem_solution"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ problem_solution: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t(
                    "is-the-solution-realistic-implementable-tested-and-proven-effective"
                  )}{" "}
                  <span className="text-[#91B9B5] font-medium"> (10٪)</span>
                </h3>
              }
              name="feasibility"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ feasibility: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("is-the-competitve-adva")}{" "}
                  <span className="text-[#91B9B5] font-medium"> (10٪)</span>
                </h3>
              }
              name="competitive_advantage"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ competitive_advantage: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("is-the-target-audience-defined-and-clear")}{" "}
                  <span className="text-[#91B9B5] font-medium"> (5٪)</span>
                </h3>
              }
              name="target_audience"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ target_audience: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("were-competitors-clearly-presented")}
                  <span className="text-[#91B9B5] font-medium"> (15٪)</span>
                </h3>
              }
              name="competitor_analysis"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ competitor_analysis: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("is-the-business-model-clear-and-viable")}{" "}
                  <span className="text-[#91B9B5] font-medium"> (15٪)</span>
                </h3>
              }
              name="business_model"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ business_model: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("is-the-project-idea-scalable-and-growth-able")}{" "}
                  <span className="text-[#91B9B5] font-medium"> (10٪)</span>
                </h3>
              }
              name="scalability"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ scalability: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t("clarity-and-sequence-of-presentation")}{" "}
                  <span className="text-[#91B9B5] font-medium"> (5٪)</span>
                </h3>
              }
              name="presentation_clarity"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ presentation_clarity: e.target.value })
                }
              />
            </Form.Item>

            <Form.Item
              label={
                <h3 className="m-0 font-bold text-foreground">
                  {t(
                    "clarity-of-project-work-and-how-to-benefit-from-the-service-or-product-provided"
                  )}
                  <span className="text-[#91B9B5] font-medium"> (15٪)</span>
                </h3>
              }
              name="project_clarity"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ project_clarity: e.target.value })
                }
              />
            </Form.Item>
          </div>

          <div className="fixed w-full bottom-0 left-0 bg-[#F9FAFB] p-6 flex justify-end z-30">
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
              <Button
                htmlType="submit"
                type="primary"
                loading={isPending}
              >
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
          setSuccessModal(false);
          router.push(`/judge/judge-dashboard`);
        }}
      />
    </>
  );
}
