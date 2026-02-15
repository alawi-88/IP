"use client";

import { useUserStore } from "@/store/user";
import { useTranslations } from "next-intl";
import dayjs from "dayjs";
import { Button, Form, Input, message, Modal } from "antd";
import { useForm } from "antd/es/form/Form";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useClearFormErrors } from "@/hooks/useClearFormErrors";
import { ApiError } from "next/dist/server/api-utils";
import { use, useEffect, useState } from "react";
import { FaRegClock } from "react-icons/fa";

export default function ProfilePage() {
  const t = useTranslations();
  const user = useUserStore((state) => state.participant);
  const [isOpen, setIsOpen] = useState(false);
  const [step, setStep] = useState<number>(1);
  const [email, setEmail] = useState("");
  const [form] = useForm();
  const setFieldsErrors = useSetFieldsErrors(form);
  const clearErrors = useClearFormErrors(form);
  const [messageApi, contextHolder] = message.useMessage();
  const queryClient = useQueryClient();
  const [countDown, setCountDown] = useState(0);

  // add recovery email
  const { mutate: postRecoveryEmail, isPending: isPendingAdd } = useMutation({
    mutationFn: async (values: any) => {
      const response = await axiosInstance.post(
        `/participants/profile/recovery-email/request-otp`,
        { recovery_email: values.recovery_email }
      );
      return response.data;
    },
    onSuccess: (data, variables) => {
      form.resetFields();
      setStep(2);
      setEmail(variables.recovery_email);
      setCountDown(60);
      if (variables.resend) {
        messageApi.success(data.message);
      }
    },
    onError: (error: APIError) => {
      if (error.response.data.message && !error.response.data.errors) {
        messageApi.error(error.response.data.message);
      }
      setFieldsErrors(error);
    },
    onMutate: () => {
      clearErrors();
    },
  });

  // verify added recovery email
  const { mutate: verifyRecoveryEmail, isPending: isPendingVerify } =
    useMutation({
      mutationFn: async (values: any) => {
        const response = await axiosInstance.post(
          `/participants/profile/recovery-email/verify-otp?otp_code=${values.otp_code}`
        );
        return response.data;
      },
      onSuccess: (data) => {
        queryClient.invalidateQueries({
          queryKey: ["profile"],
        });
        setIsOpen(false);
        setStep(1);
        form.resetFields();
        setCountDown(0);
        messageApi.success(t("recovery-email.success"));
      },
      onError: (error: APIError) => {
        if (error.response.data.message && !error.response.data.errors) {
          messageApi.error(error.response.data.message);
        }
        setFieldsErrors(error);
      },
      onMutate: () => {
        clearErrors();
      },
    });

  // format count down
  const formatCountdown = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds < 10 ? "0" : ""}${remainingSeconds}`;
  };

  useEffect(() => {
    let timer: NodeJS.Timeout;

    if (step === 2 && countDown > 0) {
      timer = setTimeout(() => {
        setCountDown((prev) => prev - 1);
      }, 1000);
    }

    return () => clearTimeout(timer);
  }, [countDown, step]);

  if (user == null) {
    return null;
  }

  return (
    <section className="flex flex-col gap-y-6">
      {contextHolder}
      <h1 className="text-2xl text-foreground font-bold">{t("profile")}</h1>
      <div className="dashboard-card">
        <div className="flex flex-wrap gap-y-4 gap-x-6 [&_label]:text-nowrap [&_div]:min-w-52">
          <div className="flex flex-col gap-y-2">
            <label className="font-normal text-sm text-secondary">
              {t("serial-number")}
            </label>
            <p className="font-medium text-base text-foreground">
              {user.serial_number}
            </p>
          </div>
          {user.login_by === "nafath" && (
            <div className="flex flex-col gap-y-2">
              <label className="font-normal text-sm text-secondary">
                {t("IdentityNumber")}
              </label>
              <p className="font-medium text-base text-foreground">
                {user.nafath_data?.IdentityNumber}
              </p>
            </div>
          )}
          <div className="flex flex-col gap-y-2">
            <label className="font-normal text-sm text-secondary">
              {t("registration-date")}
            </label>
            <p className="font-medium text-base text-foreground">
              {dayjs(user.created_at).format("DD/MM/YYYY HH:mm:ss")}
            </p>
          </div>
        </div>
        <div className="flex flex-col gap-y-4">
          <h2 className="text-lg font-bold text-primary col-span-5">
            {t("personal-information")}
          </h2>

          <div className="grid md:grid-cols-3 sm:grid-cols-2 flex-wrap gap-y-4 gap-x-6 [&_label]:text-nowrap [&_div]:min-w-52">
            <div className="flex flex-col gap-y-2">
              <label className="font-normal text-sm text-secondary">
                {t("full-name")}
              </label>
              <p className="font-medium text-base text-foreground">
                {user.name}
              </p>
            </div>
            <div className="flex flex-col gap-y-2">
              <label className="font-normal text-sm text-secondary">
                {t("email")}
              </label>
              <p className="font-medium text-base text-foreground break-all">
                {user.email}
              </p>
            </div>
            {user.login_by === "credentials" && (
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("recovery-email.label")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.recovery_email && (
                    <span className="break-all pe-1">
                      {user.recovery_email} -
                    </span>
                  )}
                  <Button
                    className="p-0 min-w-auto h-auto"
                    type="link"
                    onClick={() => setIsOpen(true)}
                  >
                    {user.recovery_email ? t("edit") : t("add")}
                  </Button>
                </p>
              </div>
            )}
            <div className="flex flex-col gap-y-2">
              <label className="font-normal text-sm text-secondary">
                {t("phone-number")}
              </label>
              <p className="font-medium text-base text-foreground">
                {user.phone || "-"}
              </p>
            </div>
            {user.login_by === "credentials" && (
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("gender")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.gender || "-"}
                </p>
              </div>
            )}
            <div className="flex flex-col gap-y-2">
              <label className="font-normal text-sm text-secondary">
                {t("date-of-birth")}
              </label>
              <p className="font-medium text-base text-foreground">
                {dayjs(user.date_of_birth).format("DD/MM/YYYY")}
              </p>
            </div>
            <div className="flex flex-col gap-y-2">
              <label className="font-normal text-sm text-secondary">
                {t("nationality")}
              </label>
              <p className="font-medium text-base text-foreground">
                {user.nationality || "-"}
              </p>
            </div>
            {user.login_by === "credentials" && (
              <>
                <div className="flex flex-col gap-y-2">
                  <label className="font-normal text-sm text-secondary">
                    {t("country")}
                  </label>
                  <p className="font-medium text-base text-foreground">
                    {user.country || "-"}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <label className="font-normal text-sm text-secondary">
                    {t("residence-of-city")}
                  </label>
                  <p className="font-medium text-base text-foreground">
                    {user.residence_city || "-"}
                  </p>
                </div>
              </>
            )}
          </div>
        </div>

        {user.login_by === "credentials" && (
          <div className="flex flex-col gap-y-4">
            <h2 className="text-lg font-bold text-primary col-span-5">
              {t("experiences")}
            </h2>

            <div className="grid md:grid-cols-3 sm:grid-cols-2 flex-wrap gap-y-4 gap-x-6 [&_label]:text-nowrap [&_div]:min-w-52">
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("educational-background")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.educational_background || "-"}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("your-current-role")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.current_role || "-"}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("place-of-work-study")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.place_of_work_study || "-"}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("years-of-experience")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.years_of_experience || "-"}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("experience-or-skills")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.experience_or_skills || "-"}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("most-notable-achievements-or-indicators-of-success")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.key_achievements || "-"}
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
      <Modal
        title={
          user.recovery_email
            ? t("recovery-email.edit")
            : t("recovery-email.add")
        }
        footer={null}
        open={isOpen}
        onCancel={() => {
          setIsOpen(false);
          setStep(1);
          setCountDown(0);
          form.resetFields();
        }}
        centered
      >
        <div className="pt-6 pb-4">
          <Form
            layout="vertical"
            form={form}
            onFinish={step === 1 ? postRecoveryEmail : verifyRecoveryEmail}
          >
            <div className="flex flex-col gap-y-2 py-0">
              {step === 1 && (
                <>
                  <Form.Item
                    label={t("email")}
                    name="recovery_email"
                    rules={[
                      { required: true },
                      {
                        type: "email",
                        message: t("please-enter-a-valid-email"),
                      },
                    ]}
                  >
                    <Input placeholder={t("enter-email")} />
                  </Form.Item>
                </>
              )}

              {step === 2 && (
                <>
                  <div className="text-center mb-6">
                    <h1 className="text-xl text-[#5B656A] font-bold">
                      {t("opt-code")}
                    </h1>
                    <p className="text-[#112838]">
                      {t("opt-code-sended")} {email}
                    </p>
                    <p className="text-[#98A2B3] flex items-center justify-center gap-x-1">
                      <FaRegClock /> {formatCountdown(countDown)}
                    </p>
                  </div>
                  <Form.Item
                    name="otp_code"
                    className="!w-fit !mx-auto"
                    rules={[{ required: true }]}
                  >
                    <Input.OTP length={6} dir="ltr" />
                  </Form.Item>
                </>
              )}

              <div className="flex flex-col items-center text-center">
                <Button
                  type="primary"
                  size="large"
                  htmlType="submit"
                  className="w-full"
                  loading={step === 1 ? isPendingAdd : isPendingVerify}
                >
                  {t("confirm")}
                </Button>

                {step === 2 && (
                  <p className="text-primary-green-900 font-medium flex items-center flex-wrap gap-y-2">
                    {t("didnt-receive-the-code")} {""}
                    <Button
                      type="link"
                      className="!text-primary !underline !p-0"
                      onClick={() => {
                        // postRecoveryEmail({
                        //   recovery_email: email,
                        //   resend:true,
                        // });
                        setStep(1);
                        form.setFieldValue("recovery_email", email);
                      }}
                      loading={isPendingAdd}
                      disabled={isPendingVerify || countDown > 0}
                    >
                      <div>{t("resend-code")}</div>
                    </Button>
                  </p>
                )}
              </div>
            </div>
          </Form>
        </div>
      </Modal>
    </section>
  );
}
