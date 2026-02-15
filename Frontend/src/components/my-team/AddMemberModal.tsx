"use client";

import { Button, Form, Input, Modal } from "antd";
import { useTranslations } from "next-intl";
import { useEffect, useState } from "react";
import { AiOutlineUserAdd } from "react-icons/ai";
import FeedbackModal from "../feedback-modal/FeedbackModal";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import useSetFieldsErrors from "@/hooks/useSetFieldsErrors";
import { useParams } from "next/navigation";

export default function AddMemberModal({
  teamId,
  refetch,
  disabled,
}: {
  teamId: string;
  refetch: () => void;
  disabled?: boolean;
}) {
  const t = useTranslations();
  const [openModal, setOpenModal] = useState(false);
  const [successModal, setSuccessModal] = useState(false);
  const [form] = Form.useForm();
  const { id: applicationId } = useParams<{ id: string }>();
  const setFieldsErrors = useSetFieldsErrors(form);

  const { mutate, isPending } = useMutation({
    mutationFn: async ({ data }: { data: FormData }) => {
      data.append("_method", "PUT");
      const response = await axiosInstance.post(
        `/participants/teams/${teamId}/members`,
        data,
        {
          params: {
            application_id: applicationId,
          },
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );
      return response.data;
    },

    onSuccess: () => {
      setSuccessModal(true);
      refetch();
    },

    onError: (error: APIError) => {
      setFieldsErrors(error);
    },
  });

  const onSubmit = (data: { serial_numbers: string }) => {
    const formData = new FormData();
    formData.append("serial_numbers[0]", data.serial_numbers);

    mutate({ data: formData });
  };

  useEffect(() => {
    if(openModal){
      form.resetFields();
    }
  }, [openModal]);

  return (
    <>
      <Button
        onClick={() => setOpenModal(true)}
        type="default"
        className="!px-4 !text-foreground !font-medium !flex !items-center"
        disabled={disabled}
      >
        {t("add-member")}
        <AiOutlineUserAdd className="text-2xl" />
      </Button>
      <FeedbackModal
        openModal={successModal}
        title={t("new-member-added-to-your-team")}
        subtitle={t("the-team-will-be-automatically-added-to-members")}
        type="success"
        onBtnClick={() => {
          setSuccessModal(false);
          setOpenModal(false);
          form.resetFields();
        }}
      />
      <Modal
        footer={null}
        onCancel={() => setOpenModal(false)}
        className="w-full max-w-[540px] !rounded-2xl"
        open={openModal && !successModal}
      >
        <div className="flex flex-col gap-y-10 py-6 px-4">
          <h1 className="text-[#5B656A] text-2xl font-bold m-0">
            {t("add-member")}
          </h1>
          <Form
            layout="vertical"
            form={form}
            onFinish={onSubmit}
          >
            <Form.Item
              label={t("members-serial-number")}
              name={"serial_numbers"}
              required
              rules={[
                {
                  required: true,
                },
              ]}
            >
              <Input placeholder={t("Enter-member-serial-number")} />
            </Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              block
              loading={isPending}
            >
              {t("add")}
            </Button>
          </Form>
        </div>
      </Modal>
    </>
  );
}
