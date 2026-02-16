"use client";

import { Button, Card, Form, Radio, RadioChangeEvent } from "antd";
import { useRouter } from "next/navigation";
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
      const formattedData: any = {
        project_id: projectId,
        path_slug: "cinematic-innovation-track",
        innovation: (Number(data.innovation1) + Number(data.innovation2)) / 2,
        design: (Number(data.design1) + Number(data.design2)) / 2,
        impact: (Number(data.impact1) + Number(data.impact2)) / 2,
        applicability:
          (Number(data.applicability1) + Number(data.applicability2)) / 2,
        team:
          (Number(data.team1) + Number(data.team2) + Number(data.team3)) / 3,
        presentation_skills:
          (Number(data.presentation1) +
            Number(data.presentation2) +
            Number(data.presentation3) +
            Number(data.presentation4)) /
          4,
        solution_alignment: Number(data.solution_alignment),
        // questions
        "innovation_questions[innovation_quality]": Number(data.innovation1),
        "innovation_questions[innovation_level_of_innovation]": Number(
          data.innovation2
        ),
        "design_questions[design_scientific_methodology]": Number(data.design1),
        "design_questions[design_scientific_data]": Number(data.design2),
        "impact_questions[impact_quality]": Number(data.impact1),
        "impact_questions[impact_quantity]": Number(data.impact2),
        "applicability_questions[applicability_possibility]": Number(data.applicability1),
        "applicability_questions[applicability_flexibility]":  Number(data.applicability2),
        "team_questions[team_availability]": Number(data.team1),
        "team_questions[team_collaboration]": Number(data.team2),
        "team_questions[team_professionalism]": Number(data.team3),
        "presentation_skills_questions[presentation_skills_explanation]": Number(data.presentation1),
        "presentation_skills_questions[presentation_skills_sequence]": Number(data.presentation2),
        "presentation_skills_questions[presentation_skills_quality]": Number(data.presentation3),
        "presentation_skills_questions[presentation_skills_communication]": Number(data.presentation4),
        "solution_alignment_questions[solution_alignment_innovative]": Number(data.solution_alignment),
      };

      const formData = new FormData();

      Object.keys(formattedData).forEach((key) => {
        if (formattedData[key] != null) {
          if (formattedData[key] instanceof Array) {
            formattedData[key].forEach((item: any, index: number) => {
              formData.append(`${key}[${index}]`, item);
            });
          } else {
            formData.append(key, formattedData[key]);
          }
        }
      });

      const response = await axiosInstance.post(
        "/judges/evaluations",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
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

  return (
    <>
      <Card className="w-full h-fit">
        <Form
          layout="vertical"
          form={form}
          onFinish={mutate}
        >
          <div className="flex flex-col gap-y-8 pb-20 mb-10">
            <h1 className="m-0 text-foreground font-medium text-xl ">
              {t("cinematic-innovation-track")}
            </h1>

            {/* Creativity and Innovation */}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("Creativity-and-Innovation")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (15٪)</span>
            </div>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t(
                    "how-creative-and-innovative-is-the-approach-to-the-proposed-solution-is-it-new-and-innovative-or-an-improvement-on-an-existing-solution"
                  )}
                </h3>
              }
              name="innovation1"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ innovation1: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t(
                    "level-of-innovation-discovery-and-pioneering-in-the-proposed-solution"
                  )}
                </h3>
              }
              name="innovation2"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ innovation2: e.target.value })
                }
              />
            </Form.Item>

            {/* Design and Scientific Validity */}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("Design-and-Scientific-Validity")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (25٪)</span>
            </div>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t(
                    "use-of-reliable-scientific-methodology-in-presenting-the-solution"
                  )}
                </h3>
              }
              name="design1"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ design1: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Adoption-of-scientific-data-and-techniques")}
                </h3>
              }
              name="design2"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ design2: e.target.value })
                }
              />
            </Form.Item>

            {/*Impact*/}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("impact")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (20٪)</span>
            </div>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t(
                    "what-is-the-impact-quality-and-quantity-this-solution-provides-to-various-challenges-and-sectors-does-it-solve-a-big-or-small-problem-will-it-inspire-or-help-many"
                  )}
                </h3>
              }
              name="impact1"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ impact1: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("economic-and-social-impact-of-the-proposed-solution")}
                </h3>
              }
              name="impact2"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ impact2: e.target.value })
                }
              />
            </Form.Item>

            {/*Applicability*/}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("applicability")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (10٪)</span>
            </div>

            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t("Possibility-of-implementing-the-idea-in-reality")}
                </h3>
              }
              name="applicability1"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ applicability1: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Flexibility-for-use-in-multiple-fields")}
                </h3>
              }
              name="applicability2"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ applicability2: e.target.value })
                }
              />
            </Form.Item>

            {/*Team*/}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("team")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (10٪)</span>
            </div>

            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t(
                    "Availability-of-an-integrated-team-with-diverse-expertise"
                  )}
                </h3>
              }
              name="team1"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) => form.setFieldsValue({ team1: e.target.value })}
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Team-ability-to-collaborate-and-achieve-goals")}
                </h3>
              }
              name="team2"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) => form.setFieldsValue({ team2: e.target.value })}
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Professionalism-and-efficiency-of-team-members")}
                </h3>
              }
              name="team3"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) => form.setFieldsValue({ team3: e.target.value })}
              />
            </Form.Item>

            {/*Presentation Skills*/}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("presentation-skills")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (10٪)</span>
            </div>

            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t("clear-and-comprehensive-explanation-of-the-idea")}
                </h3>
              }
              name="presentation1"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ presentation1: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Logical-sequence-of-ideas")}
                </h3>
              }
              name="presentation2"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ presentation2: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Quality-and-effectiveness-of-presentations")}
                </h3>
              }
              name="presentation3"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ presentation3: e.target.value })
                }
              />
            </Form.Item>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary">
                  {t("Team-ability-to-communicate-clearly-and-engagingly")}
                </h3>
              }
              name="presentation4"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ presentation4: e.target.value })
                }
              />
            </Form.Item>

            {/*Solution Alignment*/}
            <div className="flex gap-x-1">
              <h3 className="m-0 text-foreground font-medium text-base">
                {t("Solution-Alignment")}
              </h3>
              <span className="text-[#91B9B5] font-medium"> (10٪)</span>
            </div>
            <Form.Item
              label={
                <h3 className="m-0 font-normal text-sm text-secondary ">
                  {t(
                    "Alignment-of-innovative-solutions-with-the-challenges-presented-in-the-target-sectors"
                  )}
                </h3>
              }
              name="solution_alignment"
              required
              rules={[{ required: true, message: t("required-field") }]}
            >
              <EvaluateOptions
                onChange={(e) =>
                  form.setFieldsValue({ solution_alignment: e.target.value })
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
