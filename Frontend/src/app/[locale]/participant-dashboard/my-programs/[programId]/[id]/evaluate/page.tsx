"use client";

import { Button, Card, Form, Input, Radio, RadioChangeEvent } from "antd";
import { useParams, useRouter } from "next/navigation";
import { useState } from "react";
import { useTranslations } from "next-intl";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import axiosInstance from "@/axios";

const EvaluateOptions = ({
  onChange,
}: {
  onChange: (e: RadioChangeEvent) => void;
}) => (
  <Radio.Group
    rootClassName="!flex !gap-4 !items-center !flex-wrap"
    onChange={onChange}
  >
    <Radio.Button rootClassName="evaluate" value="1">
      1
    </Radio.Button>
    <Radio.Button rootClassName="evaluate" value="2">
      2
    </Radio.Button>
    <Radio.Button rootClassName="evaluate" value="3">
      3
    </Radio.Button>
    <Radio.Button rootClassName="evaluate" value="4">
      4
    </Radio.Button>
    <Radio.Button rootClassName="evaluate" value="5">
      5
    </Radio.Button>
  </Radio.Group>
);

export default function EvaluateForm() {
  const router = useRouter();
  const [form] = Form.useForm();

  const t = useTranslations();
  const queryClient = useQueryClient();
  const [successModal, setSuccessModal] = useState(false);
  const { id: applicationId } = useParams<{
    id: string;
  }>();
  const { mutate, isPending } = useMutation({
    mutationFn: async (data: any) => {
      const response = await axiosInstance.post(
        `/participants/satisfactions`,
        data,
        {
          params: {
            application_id: applicationId,
          },
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
        queryKey: ["submitted-evaluations"],
      });
    },
  });

  const onSubmit = async (data: any) => {
    const formData = {
      application_id: applicationId,
      overall_experience: data.overall_experience,
      benefit_from_training: data.benefit_from_training,
      support_and_guidance_mentors: data.support_and_guidance_mentors,
      support_organizers: data.support_organizers,
      location_surrounding_environment: data.location_surrounding_environment,
      interested_attending_similar_programs:
        data.interested_attending_similar_programs,
      how_did_you_hear_about_filmathon: data.how_did_you_hear_about_filmathon,
      suggestions_comments: data.suggestions_comments,
    };
    mutate(formData);
  };

  return (
    <>
      <h1 className="m-0 my-6 text-[#5B656A] font-medium text-2xl">
        {t("beneficiary-satisfaction-survey")}
      </h1>
      <Card className="w-full !pb-20">
        <div className="flex flex-col gap-y-8">
          <div className="flex flex-col gap-y-4">
            <p className="m-0 text-foreground leading-8 text-base font-medium">
              {t("survey-description")}
            </p>

            <div className="bg-[#F2F4F7] py-4 px-2 rounded-lg text-[#5B656A] flex flex-col md:flex-row gap-10 flex-wrap">
              <div className="flex gap-x-2 items-center">
                <p className="m-0 bg-[#FFFFFF] rounded-md py-[6px] px-[11px] text-foreground">
                  1
                </p>
                <p className="m-0 text-foreground text-sm font-normal">
                  {t("very-unsatisfied")}
                </p>
              </div>

              <div className="flex gap-x-2 items-center">
                <p className="m-0 bg-[#FFFFFF] rounded-md py-[6px] px-[11px] text-foreground">
                  2
                </p>
                <p className="m-0 text-foreground text-sm font-normal">
                  {t("unsatisfied")}
                </p>
              </div>

              <div className="flex gap-x-2 items-center">
                <p className="m-0 bg-[#FFFFFF] rounded-md py-[6px] px-[11px] text-foreground">
                  3
                </p>
                <p className="m-0 text-foreground text-sm font-normal">
                  {t("neutral")}
                </p>
              </div>

              <div className="flex gap-x-2 items-center">
                <p className="m-0 bg-[#FFFFFF] rounded-md py-[6px] px-[11px] text-foreground">
                  4
                </p>
                <p className="m-0 text-foreground text-sm font-normal">
                  {t("satisfied")}
                </p>
              </div>

              <div className="flex gap-x-2 items-center">
                <p className="m-0 bg-[#FFFFFF] rounded-md py-[6px] px-[11px] text-foreground">
                  5
                </p>
                <p className="m-0 text-foreground text-sm font-normal">
                  {t("very-satisfied")}
                </p>
              </div>
            </div>
          </div>

          <Form layout="vertical" form={form} onFinish={onSubmit}>
            <div className="flex flex-col gap-y-6">
              <Form.Item
                name="overall_experience"
                label={t(
                  "How-do-you-evaluate-your-overall-experience-in-Filmathon"
                )}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
                className="text-secondary text-sm font-normal"
              >
                <EvaluateOptions
                  onChange={(e) => {
                    form.setFieldsValue({ overall_experience: e.target.value });
                  }}
                />
              </Form.Item>

              <Form.Item
                name="benefit_from_training"
                label={t(
                  "To-what-extent-did-you-benefit-from-the-information-provided-in-the-training-sessions-and-discussion-panels"
                )}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
              >
                <EvaluateOptions
                  onChange={(e) => {
                    form.setFieldsValue({
                      benefit_from_training: e.target.value,
                    });
                  }}
                />
              </Form.Item>

              <Form.Item
                name="support_and_guidance_mentors"
                label={t(
                  "How-satisfied-are-you-with-the-support-and-guidance-provided-by-the-mentors"
                )}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
              >
                <EvaluateOptions
                  onChange={(e) => {
                    form.setFieldsValue({
                      support_and_guidance_mentors: e.target.value,
                    });
                  }}
                />
              </Form.Item>

              <Form.Item
                name="support_organizers"
                label={t(
                  "How-satisfied-are-you-with-the-support-provided-by-the-organizers"
                )}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
              >
                <EvaluateOptions
                  onChange={(e) => {
                    form.setFieldsValue({ support_organizers: e.target.value });
                  }}
                />
              </Form.Item>

              <Form.Item
                name="location_surrounding_environment"
                label={t(
                  "Was-the-location-and-surrounding-environment-suitable-for-conducting-Filmathon-activities"
                )}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
              >
                <EvaluateOptions
                  onChange={(e) => {
                    form.setFieldsValue({
                      location_surrounding_environment: e.target.value,
                    });
                  }}
                />
              </Form.Item>

              <Form.Item
                name="interested_attending_similar_programs"
                label={t(
                  "Are-you-interested-in-attending-similar-programs-in-the-future"
                )}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
              >
                <Radio.Group
                  onChange={(e) => {
                    form.setFieldsValue({
                      interested_attending_similar_programs: e.target.value,
                    });
                  }}
                  className="!flex !flex-wrap !gap-4 !items-center [&_.ant-radio-wrapper-checked]:bg-[#f9e8dd] [&_.ant-radio-wrapper-checked]:border-accent"
                >
                  <Radio
                    value="1"
                    className="!h-auto !px-10 !py-4 border rounded-2xl"
                  >
                    {t("yes-interested")}
                  </Radio>

                  <Radio
                    value="0"
                    className="!h-auto !px-6 !py-4 border rounded-2xl"
                  >
                    {t("no-interested")}
                  </Radio>
                </Radio.Group>
              </Form.Item>

              <Form.Item
                name="how_did_you_hear_about_filmathon"
                label={t("How-did-you-hear-about-Filmathon")}
                required
                rules={[
                  {
                    required: true,
                  },
                ]}
              >
                <Radio.Group
                  onChange={(e) => {
                    form.setFieldsValue({
                      how_did_you_hear_about_filmathon: e.target.value,
                    });
                  }}
                  className="selcted-checkbox-group !flex !flex-wrap !gap-4 !items-center [&_.ant-radio-wrapper-checked]:bg-[#f9e8dd] [&_.ant-radio-wrapper-checked]:border-accent"
                >
                  <Radio
                    value="email"
                    className="!h-auto !px-10 !py-4 border border-[#D0D5DD] rounded-2xl"
                  >
                    {t("email")}
                  </Radio>
                  <Radio
                    type="right"
                    value="sms"
                    className="!h-auto !px-10 !py-4 border border-[#D0D5DD] rounded-2xl"
                  >
                    {t("sms")}
                  </Radio>
                  <Radio
                    value="social_media"
                    className="!h-auto !px-10 !py-4 border border-[#D0D5DD] rounded-2xl"
                  >
                    {t("social-media")}
                  </Radio>
                  <Radio
                    value="other"
                    className="!h-auto !px-10 !py-4 border border-[#D0D5DD] rounded-2xl"
                  >
                    {t("other")}
                  </Radio>
                </Radio.Group>
              </Form.Item>

              <Form.Item
                name="suggestions_comments"
                label={t(
                  "Do-you-have-any-suggestions-or-comments-that-can-help-us-improve-the-services-provided-in-future-editions"
                )}
              >
                <Input.TextArea
                  rows={1}
                  placeholder={t("write-your-suggestion")}
                />
              </Form.Item>
            </div>

            <div className="fixed w-full bottom-0 left-0 bg-[#F9FAFB] p-6 flex justify-end z-30">
              <div className="flex gap-x-6">
                <Button
                  type="default"
                  onClick={() => router.push("my-programs")}
                >
                  {t("cancel")}
                </Button>
                <Button htmlType="submit" type="primary">
                  {t("submit-evaluation")}
                </Button>
              </div>
            </div>
          </Form>
        </div>
      </Card>

      <FeedbackModal
        openModal={successModal}
        title={t("evaluation-submitted-successfully")}
        btnLabel={t("back-to-home")}
        type="success"
        onBtnClick={() => {
          setSuccessModal(false);
          router.push("/");
        }}
      />
    </>
  );
}
