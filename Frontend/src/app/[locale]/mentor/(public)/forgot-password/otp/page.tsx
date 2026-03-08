"use client";

import { Button, Form, Input, message } from "antd";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { FaRegClock } from "react-icons/fa";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/routing";
const maskEmail = (email: string) => {
  const [localPart, domain] = email.split("@");
  const maskedLocalPart = localPart.slice(0, 3) + "******";
  const maskedDomain = domain.replace(/[^.]/g, "*");
  return `${maskedLocalPart}@${maskedDomain}`;
};

const formatCountdown = (seconds: number) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes}:${remainingSeconds < 10 ? "0" : ""}${remainingSeconds}`;
};

export default function ForgotPasswordOTPPage() {
  const searchParams = useSearchParams();
  const t = useTranslations();

  const [form] = Form.useForm();

  const [countDown, setCountDown] = useState(0);
  const [resendCount, setResendCount] = useState(0);
  const [messageApi, contextHolder] = message.useMessage();

  const router = useRouter();

  const { mutate: resendOTP, isPending: resendOTPisPending } = useMutation({
    mutationFn: async (data: any) => {
      const response = await axiosInstance.post(`/mentors/forgot-password`, data);

      return response.data;
    },
    onSuccess: () => {
      setResendCount((prev) => prev + 1);
    },
    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
    },
  });

  const { mutate: confirmOTP, isPending: confirmOTPisPending } = useMutation({
    mutationFn: async (data: { code: string }) => {
      const response = await axiosInstance.post(
        `/mentors/check-password-reset-code`,
        data
      );

      return response.data;
    },
    onSuccess: (data: { token?: string; code_exists: boolean }) => {
      const code = form.getFieldValue("otp");
      if (data.code_exists) {
        router.push(`/mentor/forgot-password/reset`);
        sessionStorage.setItem("mentor_otp_rest_password", code);
        sessionStorage.removeItem("mentor_reset_password_email");
      } else {
        messageApi.error(t("invalid-or-expired-code"));
      }
    },
    onError: (error: APIError) => {
      messageApi.error(t("invalid-or-expired-code"));
    },
  });

  useEffect(() => {
    const interval = setInterval(() => {
      setCountDown((prev) => {
        if (prev === 0) {
          clearInterval(interval);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [countDown]);

  useEffect(() => {
    if (resendCount < 3) {
      setCountDown(60 * (resendCount + 1));
    }
  }, [resendCount]);

  const email = sessionStorage.getItem("mentor_reset_password_email") || "";
  const maskedEmail = email ? maskEmail(email) : "";

  const onResendOTP = async () => {
    if (resendCount >= 3 || countDown > 0 || !email) {
      message.error("");
      return;
    }

    resendOTP({ email });
  };

  const onFinish = async (values: { otp: string }) => {
    confirmOTP({ code: values.otp });
  };

  return (
    <>
      {contextHolder}
      <div className="card">
        <div className="text-center mb-8">
          <h1 className="text-4xl text-[#5B656A] font-bold">
            {t("temporary-code")}
          </h1>
          <p className="text-foreground text-xl">
            {t("enter-the-recovery-code-sent-to-your-email")} {""} {maskedEmail}
          </p>
        </div>

        <Form
          layout="vertical"
          form={form}
          scrollToFirstError
          onFinish={onFinish}
        >
          <div className="grid grid-cols-1 justify-center">
            <Form.Item
              name="otp"
              className="!w-fit !mx-auto"
              rules={[
                {
                  required: true,
                },
              ]}
            >
              <Input.OTP length={6} dir="ltr" />
            </Form.Item>
          </div>

          <div className="flex flex-col lg:flex-row justify-between items-center gap-4">
            <Button
              type="primary"
              htmlType="submit"
              className="!mx-auto lg:!max-w-36 !w-full"
              loading={confirmOTPisPending}
            >
              {t("confirm")}
            </Button>
          </div>

          <div className="flex flex-col gap-y-3 items-center mx-auto mt-4">
            {" "}
            <p className="text-[#98A2B3] flex items-center gap-x-1">
              <FaRegClock /> {formatCountdown(countDown)}
            </p>
            <p className="text-primary-green-900 font-medium">
              {t("didnt-receive-the-code")} {""}
              <Button
                type="link"
                className="!text-primary !underline !p-0"
                onClick={onResendOTP}
                disabled={resendCount >= 3 || countDown > 0}
                loading={resendOTPisPending}
              >
                <div>
                  {t("resend-code")}
                </div>
              </Button>
            </p>
          </div>
        </Form>
      </div>
    </>
  );
}
