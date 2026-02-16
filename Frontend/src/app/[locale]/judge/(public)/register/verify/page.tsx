"use client";

import axiosInstance, { APIError } from "@/axios";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import { Link, useRouter } from "@/i18n/routing";
import { useMutation } from "@tanstack/react-query";
import { Button, message, Spin } from "antd";
import { useTranslations } from "next-intl";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { FaRegClock } from "react-icons/fa";

export default function VerifyPage() {
  const t = useTranslations();
  const router = useRouter();
  const searchParams = useSearchParams();
  const [messageApi, contextHolder] = message.useMessage();
  const activation_code = searchParams.get("activation_code");
  const [email, setEmail] = useState(
    sessionStorage.getItem("judge_registerEmail") || ""
  );
  const [countDown, setCountDown] = useState(0);
  const [resendCount, setResendCount] = useState(0);
  const { isPending, mutate, isSuccess } = useMutation({
    mutationFn: async () => {
      const response = await axiosInstance.post(`/judges/activate-account`, {
        activation_code,
      });
      return response.data;
    },
    retry: false,
  });

  const {
    mutate: resendActivation,
    isPending: resendActivationIsPending,
    isSuccess: isResend,
  } = useMutation({
    mutationFn: async (_) => {
      const response = await axiosInstance.post(`/judges/resend-activation`, {
        email,
      });

      return response.data;
    },
    onSuccess: (data) => {
      messageApi.success(data.message);
      setResendCount((prev) => {
        const newCount = prev + 1;
        if (newCount <= 3) {
          setCountDown(60 * newCount);
        }
        return newCount;
      });
    },

    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
    },
  });

  const formatCountdown = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds < 10 ? "0" : ""}${remainingSeconds}`;
  };

  useEffect(() => {
    mutate();
  }, []);

  useEffect(() => {
    if (countDown <= 0) return;

    const interval = setInterval(() => {
      setCountDown((prev) => {
        if (prev <= 1) {
          clearInterval(interval);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [countDown]);

  if (isPending) {
    return (
      <div className="card p-0 flex justify-center items-center">
        <div className="py-10 px-5 md:px-10 flex flex-col gap-5 items-center">
          <Spin />
        </div>
      </div>
    );
  }

  return (
    <>
      {contextHolder}

      <FeedbackModal
        openModal={!isPending}
        title={
          isSuccess
            ? t("your-account-has-been-successfully-activated")
            : t("activation-link-is-invalid-or-expired")
        }
        type={isSuccess ? "success" : "error"}
        children={
          <>
            {isSuccess && (
              <Link href="/judge/login">
                <Button type="primary" className="mt-2" size="large">
                  {t("login")}
                </Button>
              </Link>
            )}
            {!isSuccess && !isPending && email && (
              <>
                {isResend && (
                  <p className="text-[#98A2B3] flex items-center gap-x-1">
                    <FaRegClock /> {formatCountdown(countDown)}
                  </p>
                )}

                <Button
                  type="primary"
                  className="mt-2"
                  size="large"
                  loading={resendActivationIsPending}
                  disabled={resendCount >= 3 || countDown > 0}
                  onClick={() => resendActivation()}
                >
                  <div>{t("resend-activation")}</div>
                </Button>
              </>
            )}
          </>
        }
      />

      {/* <div className="card p-0 flex justify-center items-center hidden">
        {contextHolder}
        <div className="py-10 px-5 md:px-10 flex flex-col gap-5 items-center">
          {isPending ? (
            <Spin />
          ) : isSuccess ? (
            <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
              {t("your-account-has-been-successfully-activated")}
            </h1>
          ) : (
            <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
              {t("activation-link-is-invalid-or-expired")}
            </h1>
          )}
          <Link href="/judge/login">
            <Button type="primary" htmlType="button" size="large">
              {t("login")}
            </Button>
          </Link>
          {!isSuccess && !isPending && email && (
            <>
              {isResend && (
                <p className="text-[#98A2B3] flex items-center gap-x-1">
                  <FaRegClock /> {formatCountdown(countDown)}
                </p>
              )}

              <Button
                type="link"
                className="!text-primary !underline !p-0"
                loading={resendActivationIsPending}
                disabled={resendCount >= 3 || countDown > 0}
                onClick={() => resendActivation()}
              >
                <div>{t("resend-activation")}</div>
              </Button>
            </>
          )}
        </div>
      </div> */}


    </>
  );
}
