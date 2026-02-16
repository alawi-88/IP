"use client";

import { useUserStore } from "@/store/user";
import { useLocale, useTranslations } from "next-intl";
import { Button, Form, Input, message, Spin, Upload } from "antd";
import { Link, useRouter } from "@/i18n/routing";
import { FaRegUser } from "react-icons/fa";
import Image from "next/image";
import PhoneInput from "react-phone-input-2";
import "react-phone-input-2/lib/style.css";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { useClearFormErrors } from "@/hooks/useClearFormErrors";
import { useFormScrollToError } from "@/hooks/useFormScrollToError";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useEffect, useState } from "react";
import { RiImageAddLine } from "react-icons/ri";

export default function EditProfilePage() {
  const t = useTranslations();
  const locale = useLocale();
  const user = useUserStore((state) => state.mentor);
  const queryClient = useQueryClient();
  const router = useRouter();
  const phoneInputClass = locale === "ar" ? "phone-input-ar" : "phone-input-en";
  const [form] = Form.useForm();
  const values = Form.useWatch([], form);
  const [isChanged, setIsChanged] = useState(false);
  const setFieldsErrors = useSetFieldsErrors(form);
  const scrollToError = useFormScrollToError(form);
  const clearErrors = useClearFormErrors(form);
  const [messageApi, contextHolder] = message.useMessage();

  const { mutate, isPending } = useMutation({
    mutationFn: async (values: any) => {
      const formData = new FormData();
      Object.keys(values).forEach((key) => {
        const value = values[key];

        if (value != null) {
          if (
            Array.isArray(value) &&
            (value[0]?.originFileObj || value[0]?.url)
          ) {
            const file = value[0];
            if (file?.originFileObj) {
              formData.append(key, file.originFileObj);
            } else if (file?.url) {
              formData.append(key, file.url);
            }
          } else {
            formData.append(key, value);
          }
        }
      });

      const response = await axiosInstance.post(`/mentors/profile`, formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    },
    onSuccess: (data, variables) => {
      if (variables?.image?.length) {
        form.setFieldValue("image", [
          { url: data?.mentor?.image, name: data?.mentor?.image, uid: "1" },
        ]);
      }
      queryClient.invalidateQueries({ queryKey: ["profile", "mentor"] });
      setIsChanged(false);
      messageApi.success(data?.message);
      setTimeout(() => {
        document
          .querySelector("main")
          ?.scrollTo({ top: 0, behavior: "smooth" });
      }, 100);
    },
    onError: (error: APIError) => {
      setFieldsErrors(error);
      scrollToError();
    },
    onMutate: () => {
      clearErrors();
    },
  });

  useEffect(() => {
    if (!user || !values) return;

    const initialValues = {
      image: user.image
        ? [{ url: user.image, name: user.image, uid: "1" }]
        : [],
      name: user.name,
      email: user.email,
      phone: user.phone,
      brief: user.brief,
      profession: user.profession,
      experience: user.experience,
      facebook: user.facebook,
      linkedin: user.linkedin,
      instagram: user.instagram,
    };

    const hasChanged = JSON.stringify(values) !== JSON.stringify(initialValues);
    setIsChanged(hasChanged);
  }, [values, user]);

  return (
    <section className="flex flex-col gap-y-6">
      {contextHolder}
      <div className="flex justify-between gap-4">
        <h1 className="text-2xl text-foreground font-bold">{t("profile")}</h1>
      </div>
      {!user ? (
        <Spin className="flex justify-center w-full" />
      ) : (
        <Form layout="vertical" form={form} onFinish={mutate}>
          <div className="dashboard-card">
            <div className="flex flex-col gap-y-4">
              <div className="user-img">
                <Form.Item
                  name={"image"}
                  rules={[
                    {
                      validator: (_, value) => {
                        if (!value || !value.length) return Promise.resolve();

                        const file = value[0];

                        if (file.url || file.thumbUrl) return Promise.resolve();

                        if (
                          !["image/png", "image/jpg", "image/jpeg"].includes(
                            file.type
                          )
                        ) {
                          return Promise.reject(
                            t(
                              "the-attached-image-must-be-in-png-jpg-jpeg-or-webp-format"
                            )
                          );
                        }

                        if (file.size > 1024 * 1024) {
                          return Promise.reject(
                            t("uploaded-image-must-not-exceed-1-mb")
                          );
                        }

                        return Promise.resolve();
                      },
                    },
                  ]}
                  valuePropName="fileList"
                  getValueFromEvent={(e) => e?.fileList || []}
                  className="!mb-0"
                  initialValue={
                    user?.image
                      ? [
                          {
                            url: user.image,
                            name: user.image,
                            uid: "1",
                          },
                        ]
                      : []
                  }
                >
                  <Upload
                    accept="image/*"
                    maxCount={1}
                    listType="picture-circle"
                    className="avatar-uploader"
                    beforeUpload={() => false}
                    showUploadList={{
                      showPreviewIcon: false,
                      showRemoveIcon: true,
                    }}
                  >
                    <div className="flex items-center justify-center text-[#98A2B3] text-2xl">
                      <RiImageAddLine />
                    </div>
                  </Upload>
                </Form.Item>
              </div>

              <h2 className="text-lg font-bold text-primary col-span-5">
                {t("personal-information")}
              </h2>

              <div className="grid lg:grid-cols-2 lg:gap-x-6 gap-y-4">
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
                  initialValue={user.name}
                  className="!mb-0"
                >
                  <Input placeholder={t("full-name-matches-id")} />
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
                  initialValue={user.email}
                  className="!mb-0"
                >
                  <Input placeholder="Email@domain.com" />
                </Form.Item>
                <Form.Item
                  label={t("phone-number")}
                  name={"phone"}
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
                  initialValue={user.phone}
                  className="!mb-0"
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
                  label={t("bio")}
                  name={"brief"}
                  rules={[
                    {
                      required: true,
                    },
                  ]}
                  initialValue={user.brief}
                  className="!mb-0"
                >
                  <Input placeholder={t("bio")} />
                </Form.Item>
              </div>
            </div>

            <div className="flex flex-col gap-y-4">
              <h2 className="text-lg font-bold text-primary col-span-5">
                {t("experiences")}
              </h2>

              <div className="grid lg:grid-cols-2 lg:gap-x-6 gap-y-4">
                <Form.Item
                  label={t("profession")}
                  name={"profession"}
                  rules={[
                    {
                      required: true,
                    },
                  ]}
                  initialValue={user.profession}
                  className="!mb-0"
                >
                  <Input placeholder={t("profession")} />
                </Form.Item>

                <Form.Item
                  label={t("the-experience")}
                  name={"experience"}
                  rules={[
                    {
                      required: true,
                    },
                  ]}
                  initialValue={user.experience}
                  className="!mb-0"
                >
                  <Input placeholder={t("the-experience")} />
                </Form.Item>
              </div>
            </div>

            <div className="flex flex-col gap-y-4">
              <h2 className="text-lg font-bold text-primary col-span-5">
                {t("social-accounts")}
              </h2>

              <div className="grid lg:grid-cols-2 lg:gap-x-6 gap-y-4">
                <Form.Item
                  label={t("socialMedia.facebook")}
                  name={"facebook"}
                  rules={[
                    { type: "url", message: t("please-enter-a-valid-URL") },
                  ]}
                  initialValue={user.facebook}
                  className="!mb-0"
                >
                  <Input placeholder={t("socialMedia.facebook")} />
                </Form.Item>

                <Form.Item
                  label={t("socialMedia.linkedIn")}
                  name={"linkedin"}
                  rules={[
                    { type: "url", message: t("please-enter-a-valid-URL") },
                  ]}
                  initialValue={user.linkedin}
                  className="!mb-0"
                >
                  <Input placeholder={t("socialMedia.linkedIn")} />
                </Form.Item>

                <Form.Item
                  label={t("socialMedia.instagram")}
                  name={"instagram"}
                  rules={[
                    { type: "url", message: t("please-enter-a-valid-URL") },
                  ]}
                  initialValue={user.instagram}
                  className="!mb-0"
                >
                  <Input placeholder={t("socialMedia.instagram")} />
                </Form.Item>
              </div>
            </div>

            <div className="flex justify-between items-center gap-4 flex-wrap">
              <Button
                type="default"
                htmlType="button"
                size="large"
                disabled={isPending}
                onClick={() => router.push("/mentor/mentor-dashboard/profile")}
              >
                {t("back")}
              </Button>
              <Button
                type="primary"
                size="large"
                htmlType="submit"
                loading={isPending}
                disabled={!isChanged}
              >
                {t("save")}
              </Button>
            </div>
          </div>
        </Form>
      )}
    </section>
  );
}
