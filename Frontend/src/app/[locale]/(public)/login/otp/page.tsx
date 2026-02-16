"use client";

import { Button, Form, Input, message } from "antd";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { FaRegClock } from "react-icons/fa";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/routing";
import { useUserStore } from "@/store/user";
import { saveAccessToken } from "@/hooks/useTokenSaver";
import { Participant } from "@/lib/interfaces";

type APIResponse = {
  message: string;
  token: string;
  participant: Participant;
};

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

  const setParticipant = useUserStore((state) => state.loginParticipant);

  const router = useRouter();

  const { mutate: resendOTP, isPending: resendOTPisPending } = useMutation({
    mutationFn: async (data: any) => {
      const response = await axiosInstance.post(
        `/participants/resend-otp`,
        data
      );

      return response.data;
    },
    onSuccess: () => {
      setResendCount((prev) => prev + 1);
    },
    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
    },
  });

  const { mutate, isPending } = useMutation({
    mutationFn: async (data: unknown) => {
      const response = await axiosInstance.post<APIResponse>(
        "/participants/auth/login",
        data
      );

      return response.data;
    },
    onSuccess: (data: APIResponse) => {
      sessionStorage.removeItem("participant_otp_email");
      sessionStorage.removeItem("participant_otp_password");
      sessionStorage.removeItem("participant_rememberMe");
      setParticipant({ ...data.participant });
      saveAccessToken(data.token);
      router.push("/participant-dashboard");
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

  const email = sessionStorage.getItem("participant_otp_email") || "";
  const password = sessionStorage.getItem("participant_otp_password") || "";
  const rememberMe = sessionStorage.getItem("participant_rememberMe") || 0;
  const maskedEmail = email ? maskEmail(email) : "";

  const onResendOTP = async () => {
    if (resendCount >= 3 || countDown > 0 || !email) {
      message.error("");
      return;
    }

    resendOTP({ email });
  };

  const onFinish = async (values: { otp: string }) => {
    mutate({
      email,
      password,
      remember_me: Number(rememberMe),
      otp: values.otp,
    });
  };

  return (
    <>
      {contextHolder}
      <div className="card">
        <div className="text-center mb-8">
          <h1 className="text-4xl text-[#5B656A] font-bold">
            {t("temporary-code")}
          </h1>
          <p className="text-xl text-[#112838]">
            {t("enter-the-temporary-code-sent-to-your-email")} {""}{" "}
            {maskedEmail}
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
              loading={isPending}
            >
              {t("confirm")}
            </Button>
          </div>

          <div className="flex flex-col gap-y-3 items-center mx-auto mt-4">
            {" "}
            <p className="text-[#98A2B3] flex items-center gap-x-1">
              <FaRegClock /> {formatCountdown(countDown)}
            </p>
            <p className="text-primary-green-900 font-medium flex items-center flex-wrap gap-y-2">
              {t("didnt-receive-the-code")} {""}
              <Button
                type="link"
                className="!text-primary !underline !p-0"
                onClick={onResendOTP}
                disabled={resendCount >= 3 || countDown > 0}
                loading={resendOTPisPending}
              >
                <div>{t("resend-code")}</div>
              </Button>
            </p>
          </div>
        </Form>
      </div>
    </>
  );
}
