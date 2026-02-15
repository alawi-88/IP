"use client";

import { useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/routing";
import { Button, Checkbox, Form, Input, message } from "antd";
import { MdOutlineMailOutline } from "react-icons/md";
import { TbLockOpen } from "react-icons/tb";
import { useState } from "react";
import LabelIcon from "@/components/LabelIcon";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useGoogleReCaptcha } from "react-google-recaptcha-v3";

type APIResponse = {
  message: string;
};

export default function LoginPage() {
  const t = useTranslations();
  const [form] = Form.useForm();
  const [rememberMe, setRememberMe] = useState<boolean>(false);
  const [messageApi, contextHolder] = message.useMessage();
  const { executeRecaptcha } = useGoogleReCaptcha();
  const [recaptchaTokenLoading, setRecaptchaTokenLoading] = useState(false);

  const router = useRouter();
  const setFieldsErrors = useSetFieldsErrors(form);

  const { mutate, isPending } = useMutation({
    mutationFn: async (values: unknown) => {
      const response = await axiosInstance.post<APIResponse>(
        "/mentors/auth/login",
        values
      );

      return response.data;
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }

      setFieldsErrors(error);
    },
    onSuccess: (data) => {
      const email = form.getFieldValue("email");
      const password = form.getFieldValue("password");
      sessionStorage.setItem("mentor_otp_email", email);
      sessionStorage.setItem("mentor_otp_password", password);
      sessionStorage.setItem("mentor_rememberMe", String(rememberMe ? 1 : 0));
      router.push("/mentor/login/otp");
    },
  });

  const onSubmit = async (values: any) => {
    if (!executeRecaptcha) {
      messageApi.error(t("recaptcha-not-ready"));
      return;
    }
    try {
      setRecaptchaTokenLoading(true);
      const token = await executeRecaptcha();
      setRecaptchaTokenLoading(false);
      mutate({
        ...values,
        remember_me: rememberMe ? 1 : 0,
        "g-recaptcha-response": token,
      });
    } catch (err) {
      setRecaptchaTokenLoading(false);
      messageApi.error(t("recaptcha-error"));
    }
  };
  return (
    <div className="card p-0">
      <div className="pb-5 pt-10 px-5 md:px-10">
        <div className="mb-8">
          <p className="text-primary text-xl text-center sm:text-start">
            {t("welcome")}
          </p>
          <h1 className="text-4xl text-foreground font-bold text-center sm:text-start">
            {t("login")}
          </h1>
        </div>

        <Form layout="vertical" onFinish={onSubmit} form={form}>
          <Form.Item
            label={t("email")}
            name={"email"}
            hasFeedback
            required
            rules={[
              {
                required: true,
              },
              {
                type: "email",
              },
            ]}
          >
            <Input
              placeholder="Email@domain.com"
              prefix={<LabelIcon icon={<MdOutlineMailOutline />} />}
            />
          </Form.Item>

          <Form.Item
            label={t("password")}
            name={"password"}
            required
            rules={[
              {
                required: true,
              },
            ]}
            style={{ marginBottom: "20px" }}
          >
            <Input.Password
              placeholder={t("password")}
              prefix={<LabelIcon icon={<TbLockOpen />} />}
            />
          </Form.Item>

          <div className="mb-[30px] flex justify-between items-center">
            <Checkbox
              checked={rememberMe}
              onChange={(e) => setRememberMe(e.target.checked)}
            >
              {t("remember-me")}
            </Checkbox>

            <Link
              href={`/mentor/forgot-password`}
              className="text-[#586368] font-semibold"
            >
              {t("forgot-password")}
            </Link>
          </div>

          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              className="w-full"
              loading={isPending || recaptchaTokenLoading}
            >
              {t("login")}
            </Button>
          </Form.Item>
        </Form>
      </div>
      {contextHolder}
    </div>
  );
}
