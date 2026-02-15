"use client";

import { Button, message, Modal } from "antd";
import { useState } from "react";
import { TbTrashFilled } from "react-icons/tb";
import { useMutation } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";

export default function DeleteMemberModal({
  id,
  disabled,
  teamId,
  serialNumber,
  refetch,
}: {
  id: number;
  teamId: number;
  serialNumber: string;
  disabled?: boolean;
  refetch: () => void;
}) {
  const [openModal, setOpenModal] = useState(false);
  const { id: applicationId } = useParams<{ id: string }>();
  const t = useTranslations();
  const [messageApi, contextHolder] = message.useMessage();
  const { mutate, isPending } = useMutation({
    mutationFn: async (formData: FormData) => {
     
      const response = await axiosInstance.delete(
        `/participants/teams/${teamId}/members`,
        {
          data: formData,
          params: {
            application_id: applicationId,
          },
          headers: {
            "Content-Type": "application/json",
          },
        }
      );
      return response.data;
    },
    onSuccess: () => {
      refetch();
      setOpenModal(false);
    },
    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
    },
  });

  const onConfirm = async () => {
    const formData = new FormData();
    formData.append("team_id", teamId.toString());
    formData.append("serial_numbers[0]", serialNumber.toString());

    mutate(formData);
  };

  return (
    <>
      {contextHolder}
      <TbTrashFilled
        onClick={() => (disabled ? undefined : setOpenModal(true))}
        className="absolute top-2 right-2 text-[#758085] cursor-pointer text-lg hover:text-red-600"
      />
      <Modal
        footer={null}
        onCancel={() => setOpenModal(false)}
        className="w-full max-w-[540px]"
        open={openModal}
      >
        <div className="flex flex-col gap-y-10 py-6 px-4 items-center">
          <h1 className="text-[#5B656A] text-2xl text-center font-bold m-0">
            {t("are-you-sure-you-want-to-delete-member")}{" "}
          </h1>
          <div className="flex gap-4 flex-wrap items-center">
            <Button
              type="primary"
              onClick={onConfirm}
              className="!px-4"
              loading={isPending}
            >
              {t("yes")}
            </Button>

            <Button
              type="default"
              onClick={() => setOpenModal(false)}
              className="!px-4"
              disabled={isPending}
            >
              {t("no-back")}
            </Button>
          </div>
        </div>
      </Modal>
    </>
  );
}
