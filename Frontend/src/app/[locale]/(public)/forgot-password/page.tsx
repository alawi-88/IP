"use client";

import { Button, Form, Input, message } from "antd";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { useRouter } from "@/i18n/routing";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useTranslations } from "next-intl";
export default function ForgotPasswordPage() {
  const [form] = Form.useForm();
  const t = useTranslations();
  const router = useRouter();
  const setFieldsErrors = useSetFieldsErrors(form);
  const [messageApi, contextHolder] = message.useMessage();

  const { mutate, isPending } = useMutation({
    mutationFn: async (data: any) => {
      const response = await axiosInstance.post(
        `/participants/forgot-password`,
        data
      );

      return response.data;
    },
    onSuccess: () => {
      const email = form.getFieldValue("email");
      sessionStorage.setItem("participant_reset_password_email", email);
      router.push(
        `/forgot-password/otp`
      );
    },
    onError: (error: APIError) => {
      setFieldsErrors(error);
    },
  });

  const onSubmit = async (values: any) => {
    mutate(values);
  };

  return (
    <div className="card">
      <div className="text-center mb-8">
        <h1 className="text-4xl text-[#5B656A] font-bold">
          {t("forgot-password")}
        </h1>
        <p className="text-primary text-xl">
          {t("an-email-will-be-sent-to-recover-your-password")}
        </p>
      </div>

      <Form
        layout="vertical"
        form={form}
        onFinish={onSubmit}
      >
        <div className="grid grid-cols-1">
          <Form.Item
            label={t("email")}
            name="email"
            rules={[
              {
                required: true,
              },
              {
                type: "email",
              },
            ]}
          >
            <Input placeholder={t("enter-email")} />
          </Form.Item>
        </div>

        <div className="flex flex-col lg:flex-row justify-between items-center gap-4">
          <Button
            type="primary"
            htmlType="submit"
            className="!mx-auto lg:!max-w-36 !w-full"
            loading={isPending}
          >
            {t("send-code")}
          </Button>
        </div>
      </Form>
    </div>
  );
}
