"use client";

import { useLocale, useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/routing";
import { Button, Checkbox, Form, Input, message } from "antd";
import { FaRegUser } from "react-icons/fa";
import { MdOutlineMailOutline } from "react-icons/md";
import LabelIcon from "@/components/LabelIcon";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import PhoneInput from "react-phone-input-2";
import "react-phone-input-2/lib/style.css";
import { useState } from "react";

export default function RegisterPage() {
  const t = useTranslations();
  const [form] = Form.useForm();
  const router = useRouter();
  const locale = useLocale();
  const [successModal, setSuccessModal] = useState(false);
  const setFieldsErrors = useSetFieldsErrors(form);
  const phoneInputClass = locale === "ar" ? "phone-input-ar" : "phone-input-en";

  const [messageApi, contextHolder] = message.useMessage();

  const { mutate, isPending } = useMutation({
    mutationFn: async (values: any) => {
      const response = await axiosInstance.post(
        "/judges/auth/register",
        values
      );

      return response.data;
    },
    onSuccess: () => {
      router.push(`?success=true`);
      setSuccessModal(true);
      sessionStorage.setItem(
        "judge_registerEmail",
        form.getFieldValue("email")
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
    <>
      {contextHolder}
      <div className="card p-0">
        <div className="pb-5 pt-10 px-5 md:px-10">
          <div className="mb-8">
            <p className="text-primary text-xl text-center sm:text-start">
              {t("welcome")}
            </p>
            <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
              {t("create-account")}
            </h1>
          </div>

          <Form layout="vertical" onFinish={onSubmit} form={form} className="py-6">
            <div className={"grid grid-cols-1 md:grid-cols-2 gap-4"}>
              <h2 className="mb-4 md:col-span-2 text-base font-medium">
                {t("personal-information")}
              </h2>
              <Form.Item
                label={t("full-name")}
                name={"name"}
                rules={[
                  {
                    required: true,
                  },
                  {
                    pattern: /^[\u0621-\u064A\u0660-\u0669A-Za-z ]+$/,
                    message: t("no-symbols-and-numbers-allowed"),
                  },
                  {
                    min: 2,
                    message: t("name-min-two-characters"),
                  },
                ]}
              >
                <Input
                  placeholder={t("full-name-matches-id")}
                  prefix={<LabelIcon icon={<FaRegUser />} />}
                />
              </Form.Item>
              <Form.Item
                label={t("email")}
                name={"email"}
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
                label={t("phone-number")}
                name={"phone_number"}
                rules={[
                  {
                    required: true,
                  },
                  {
                    validator: (_, value) => {
                      if (!value) return Promise.resolve();
                      if (!value || value.length !== 12) {
                        return Promise.reject(
                          new Error(t("Phone number entered is not correct"))
                        );
                      }
                      return Promise.resolve();
                    },
                  },
                ]}
              >
                <PhoneInput
                  country={"sa"}
                  placeholder="500000000"
                  enableSearch
                  inputStyle={{
                    width: "100%",
                    direction: locale === "ar" ? "ltr" : "ltr",
                    paddingRight: locale === "ar" ? "74px" : "0px",
                    textAlign: locale === "ar" ? "right" : "left",
                  }}
                  buttonStyle={{
                    paddingRight: locale === "ar" ? "28px" : "0px",
                  }}
                  containerClass={phoneInputClass}
                />
              </Form.Item>

              <Form.Item
                label={t("experience-field")}
                name={"experience_field"}
                rules={[
                  {
                    required: true,
                  },
                  {
                    pattern: /^[\u0621-\u064A\u0660-\u0669A-Za-z ]+$/,
                    message: t("no-symbols-and-numbers-allowed"),
                  },
                  {
                    min: 2,
                    message: t("name-min-two-characters"),
                  },
                ]}
              >
                <Input
                  placeholder={t("experience-field")}
                  prefix={<LabelIcon icon={<FaRegUser />} />}
                />
              </Form.Item>

              <Form.Item
                label={t("password")}
                name={"password"}
                rules={[
                  {
                    required: true,
                  },
                  {
                    pattern:
                      /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-]).{12,}$/,
                    message: t(
                      "the-password-must-contain-at-least-12-characters-including-an-uppercase-letter-a-lowercase-letter-a-number-and-a-symbol"
                    ),
                  },
                ]}
              >
                <Input.Password placeholder={t("password")} />
              </Form.Item>

              <Form.Item
                name="password_confirmation"
                label={t("confirm-password")}
                dependencies={["password"]}
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
                <Input.Password placeholder={t("confirm-password")} />
              </Form.Item>

              <Form.Item
                name={"privacy_policy"}
                valuePropName="checked"
                rules={[
                  {
                    validator: (_, value) =>
                      value
                        ? Promise.resolve()
                        : Promise.reject(new Error(t("field-required"))),
                  },
                ]}
              >
                <Checkbox>
                  {t("i-agree-to-the")}{" "}
                  <Link
                    href={"/privacy-policy"}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary-900 underline"
                  >
                    {t("privacy-policy")}
                  </Link>
                </Checkbox>
              </Form.Item>
            </div>

            <div className="flex justify-end ">
              <Button
                type="primary"
                htmlType={"submit"}
                size="large"
                loading={isPending}
              >
                {t("sign-up")}
              </Button>
            </div>
          </Form>
        </div>
        
      </div>

      <FeedbackModal
        openModal={successModal}
        title={t("account-created-successfully")}
        subtitle={t(
          "please-activate-your-account-through-the-link-sent-to-your-email-address"
        )}
        type="success"
        btnLabel={t("login")}
        onBtnClick={() => {
          router.push("/judge/login");
        }}
      />
    </>
  );
}
