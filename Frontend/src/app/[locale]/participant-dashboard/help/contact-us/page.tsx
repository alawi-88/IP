"use client";

import { Button, Card, Form, Input, message, Upload } from "antd";
import { useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { MdOutlineEmail, MdOutlineFileUpload } from "react-icons/md";
import { FaRegUser } from "react-icons/fa";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import { useParams } from "next/navigation";
import { useRouter } from "@/i18n/routing";
import { useUserStore } from "@/store/user";
import { useFormScrollToError } from "@/hooks/useFormScrollToError";
import { useClearFormErrors } from "@/hooks/useClearFormErrors";
import Dragger from "antd/es/upload/Dragger";

export default function ContactUs() {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [form] = Form.useForm();
  const [successModal, setSuccessModal] = useState(false);
  const setFieldsErrors = useSetFieldsErrors(form);
  const scrollToError = useFormScrollToError(form);
  const clearErrors = useClearFormErrors(form);
  const user = useUserStore((state) => state.participant);
  const [messageApi, contextHolder] = message.useMessage();

  const { mutate, isPending } = useMutation({
    mutationFn: async (values: any) => {
      const formData = new FormData();

      // Apply the payload to the form data
      Object.keys(values).forEach((key) => {
        const value = values[key];

        if (value != null) {
          if (Array.isArray(value) && value[0]?.originFileObj) {
            value.forEach((fileObj: any, index) => {
              formData.append(`${key}[${index}]`, fileObj.originFileObj);
            });
          } else {
            formData.append(key, value);
          }
        }
      });

      const response = await axiosInstance.post(`/contact-us`, formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    },
    onSuccess: () => {
      setSuccessModal(true);
      setTimeout(() => {
        document
          .querySelector("main")
          ?.scrollTo({ top: 0, behavior: "smooth" });
      }, 0);
      form.resetFields();
    },
    onError: (error: APIError) => {
      setFieldsErrors(error);
      scrollToError();
    },
    onMutate: () => {
      clearErrors();
    },
  });

  return (
    <>
      {contextHolder}
      <h1 className="text-2xl text-primary-900 font-bold mb-6">
        {t("contact-us")}
      </h1>

      <Card title={t("stay-connected")}>
        <Form
          layout="vertical"
          form={form}
          onFinish={mutate}
          onFinishFailed={scrollToError}
        >
          <div className="flex flex-col gap-y-2 py-0">

            <div className="grid md:grid-cols-3  gap-4 mb-6">
              <div className="flex flex-col gap-y-1">
                <p className="text-gray-500 font-medium text-sm">
                  {t("full-name")}
                </p>
                <p className="font-bold text-base">{user ? user.name : "-"}</p>
              </div>
              <div className="flex flex-col gap-y-1">
                <p className="text-gray-500 font-medium text-sm">
                  {t("email")}
                </p>
                <p className="font-bold text-base break-all">{user ? user.email : "-"}</p>
              </div>
            </div>

            <Form.Item
              label={t("title1")}
              name="title"
              rules={[
                { required: true },
                {
                  pattern: /^[\u0621-\u064A\u0660-\u0669A-Za-z0-9 ]+$/,
                  message: t("no-symbols-allowed"),
                },
              ]}
            >
              <Input placeholder={t("enter-title")} />
            </Form.Item>

            <Form.Item
              label={t("message")}
              name="message"
              rules={[
                { required: true },
                { max: 500, message: t("max-message-length") },
              ]}
            >
              <Input.TextArea
                placeholder={t("enter-message")}
                rows={3}
                autoSize={{ minRows: 3, maxRows: 5 }}
              />
            </Form.Item>

            <Form.Item
              label={t("attachments")}
              name="attachments"
              valuePropName="fileList"
              getValueFromEvent={(e) => e?.fileList || []}
              rules={[
                {
                  validator: (_: any, fileList: any[]) => {
                    const maxSizeBytes = 5 * 1024 * 1024;

                    if (!fileList?.length) return Promise.resolve();

                    const invalidFile = fileList.find(
                      (file) => file.size > maxSizeBytes
                    );
                    if (invalidFile) {
                      return Promise.reject(new Error(`${t("invalid-file")}`));
                    }

                    return Promise.resolve();
                  },
                },
              ]}
            >
              <Upload
                className="round-sm"
                accept=".pdf,.jpg,.jpeg,.png,.docx"
                multiple
                listType="picture"
                beforeUpload={(file) => {
                  const isValid = file.size <= 5 * 1024 * 1024;
                  if (!isValid) {
                    messageApi.error(`${file.name}: ${t("invalid-file")}`);
                  }
                  return isValid ? false : Upload.LIST_IGNORE;
                }}
                onPreview={() => false}
                disabled={isPending}
              >
                <Button className="flex justify-center items-center gap-2">
                  <MdOutlineFileUpload />
                  {t("select-file")}
                </Button>
              </Upload>
            </Form.Item>

            <div className="flex justify-end">
              <Button
                type="primary"
                size="large"
                htmlType="submit"
                loading={isPending}
              >
                {t("submit")}
              </Button>
            </div>
          </div>
        </Form>
      </Card>

      <FeedbackModal
        openModal={successModal}
        title={t("message-sent-successfully")}
        btnLabel={t("go-to-inquiries-list")}
        type="success"
        onBtnClick={() => {
          queryClient.invalidateQueries({
            queryKey: ["inquiries"],
          });
          router.push("/participant-dashboard/help/inquiries");
        }}
      />
    </>
  );
}
