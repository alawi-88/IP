"use client";

import { useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/routing";
import { Button, Checkbox, Divider, Form, Input, message, Spin } from "antd";
import { MdOutlineMailOutline } from "react-icons/md";
import { TbLockOpen } from "react-icons/tb";
import { useState } from "react";
import LabelIcon from "@/components/LabelIcon";
import { useUserStore } from "@/store/user";
import { useMutation, useQuery } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { saveAccessToken } from "@/hooks/useTokenSaver";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import NafathLogin from "@/components/auth/NafathLogin";
import { Participant } from "@/lib/interfaces";

type APIResponse = {
  message: string;
  token: string;
  participant: Participant;
};

export default function LoginPage() {
  const t = useTranslations();
  const [form] = Form.useForm();
  const [rememberMe, setRememberMe] = useState<boolean>(false);
  const [messageApi, contextHolder] = message.useMessage();

  const setParticipant = useUserStore((state) => state.loginParticipant);
  const router = useRouter();
  const setFieldsErrors = useSetFieldsErrors(form);

  // get login methods
  const { data: loginMethods, isLoading: isLoginMethodsLoading } = useQuery({
    queryKey: ["login-methods"],
    queryFn: async () => {
      const response = await axiosInstance.get("/nafath/login-methods");
      return response.data;
    },
    retry:false,
  });

  const { mutate, isPending } = useMutation({
    mutationFn: async (values: unknown) => {
      const response = await axiosInstance.post<APIResponse>(
        "/participants/auth/login",
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
      // setParticipant({ ...data.participant });
      // saveAccessToken(data.token);
      // router.replace("/participant-dashboard");

      const email = form.getFieldValue("email");
      const password = form.getFieldValue("password");
      sessionStorage.setItem("participant_otp_email", email);
      sessionStorage.setItem("participant_otp_password", password);
      sessionStorage.setItem(
        "participant_rememberMe",
        String(rememberMe ? 1 : 0)
      );
      router.push("/login/otp");
    },
  });

  const onSubmit = async (values: any) => {
    mutate({
      ...values,
      remember_me: rememberMe ? 1 : 0,
    });
  };

  if (isLoginMethodsLoading) {
    return <Spin className="py-10" />;
  }

  return (
    <>
      <div
        className={`card p-0 transition-opacity duration-200 ${
          isLoginMethodsLoading ? "opacity-0" : "opacity-100"
        }`}
      >
        <div className="pb-10 pt-10 px-5 md:px-10">
          <div className="mb-8">
            <p className="text-primary text-xl text-center sm:text-start">
              {t("welcome")}
            </p>
            <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
              {t("login")}
            </h1>
          </div>

          {loginMethods?.nafath_available && (
            <div className="nafath-login-wrapper">
              <NafathLogin role="participant" btnText={t("nafath.login")} />
            </div>
          )}

          {loginMethods?.nafath_available &&
            loginMethods?.regular_available && (
              <Divider className="!my-6">{t("or-use")}</Divider>
            )}

          {(loginMethods?.regular_available || !loginMethods) && (
            <>
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
                    href={`/forgot-password`}
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
                    loading={isPending}
                  >
                    {t("login")}
                  </Button>
                </Form.Item>
              </Form>
              <div className="flex justify-center items-center gap-5 bg-[#EAECF0] !py-6  px-[20px] sm:px-[40px] rounded-bl-2xl rounded-br-2xl border border-solid border-[#D0D5DD]">
                <span className="text-[#586368]">
                  {t("you-dont-have-an-account")}
                </span>
                <Link href="/register">
                  <Button type="default" htmlType="button" size="large">
                    {t("signup")}
                  </Button>
                </Link>
              </div>
            </>
          )}
        </div>

        {contextHolder}
      </div>
    </>
  );
}
