"use client";

import { Button, Form, Input, message } from "antd";
import { useSearchParams } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/routing";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
export default function ResetPasswordPage() {
  const t = useTranslations();

  const [form] = Form.useForm();

  const searchParams = useSearchParams();
  const router = useRouter();
  const [messageApi, contextHolder] = message.useMessage();

  const success = searchParams.get("success") === "true";
  const code = sessionStorage.getItem("judge_otp_rest_password") || "";

  const { mutate, isPending } = useMutation({
    mutationFn: async (data: {
      password: string;
      password_confirmation: string;
    }) => {
      const response = await axiosInstance.post(
        `/judges/reset-password?code=${code}`,
        {
          ...data,
        }
      );

      return response.data;
    },
    onSuccess: () => {
      router.push(`?success=true`);
    },
    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
    },
  });

  const onFinish = async (values: {
    password: string;
    password_confirmation: string;
  }) => {
    if (!code) {
      return;
    }

    mutate(values);
  };

  return (
    <>
      {contextHolder}
      <div className="card">
        <div className="text-center mb-8">
          <h1 className="text-4xl text-[#5B656A] font-bold">
            {t("reset-password")}
          </h1>
          <p className="text-foreground text-xl">
            {t("an-email-will-be-sent-to-recover-your-password")}
          </p>
        </div>

        <Form
          layout="vertical"
          form={form}
          scrollToFirstError
          onFinish={onFinish}
        >
          <div className="grid grid-cols-1">
            <Form.Item
              label={t("new-password")}
              name="password"
              rules={[
                {
                  required: true,
                },
                {
                  validator: (_, value) => {
                    if (!value) return Promise.resolve();

                    const hasMinLength = value.length >= 12;
                    const hasPattern =
                      /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*_-]).+$/.test(
                        value
                      );

                    if (hasMinLength && hasPattern) {
                      return Promise.resolve();
                    }
                    return Promise.reject(t("password-validation-message"));
                  },
                },
              ]}
            >
              <Input.Password placeholder={t("new-password")} />
            </Form.Item>

            <Form.Item
              name="password_confirmation"
              label={t("confirm-new-password")}
              dependencies={["password"]}
              hasFeedback
              rules={[
                {
                  required: true,
                },
                ({ getFieldValue }) => ({
                  validator(_, value) {
                    if (!value || getFieldValue("password") === value) {
                      return Promise.resolve();
                    }
                    return Promise.reject(
                      new Error(t("password-does-not-match"))
                    );
                  },
                }),
              ]}
            >
              <Input.Password placeholder={t("confirm-new-password")} />
            </Form.Item>
          </div>

          <div className="flex flex-col lg:flex-row justify-between items-center gap-4">
            <Button
              type="primary"
              htmlType="submit"
              className="!mx-auto lg:!max-w-36 !w-full"
              loading={isPending}
            >
              {t("reset-password")}
            </Button>
          </div>
        </Form>
      </div>

      <FeedbackModal
        openModal={success}
        title={t("password-changed-successfully")}
        subtitle={t("you-can-now-log-in-to-your-account")}
        type="success"
        onBtnClick={() => {
          router.push("/judge/login");
        }}
      />
    </>
  );
}
