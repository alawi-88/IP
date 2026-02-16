"use client";

import { Button, Modal } from "antd";
import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import axiosInstance from "@/axios";
import { useTranslations } from "next-intl";

export default function MarkTeamFullModal({
  teamId,
  disabled,
  refetch,
  applicationId,
}: {
  teamId: string;
  disabled?: boolean;
  refetch: () => void;
  applicationId: string;
}) {
  const [openModal, setOpenModal] = useState(false);
  const t = useTranslations();
  const { mutate, isPending } = useMutation({
    mutationFn: async () => {
      const response = await axiosInstance.put(
        `/participants/teams/${teamId}/mark-as-completed`,
        {},
        {
          params: {
            application_id: applicationId,
          },
        }
      );
      return response.data;
    },

    onSuccess: () => {
      refetch();
      setOpenModal(false);
    },
  });

  const onConfirm = () => {
    mutate();
  };

  return (
    <>
      <Button
        onClick={() => setOpenModal(true)}
        type="primary"
        className="!px-4"
        disabled={disabled}
      >
        {t("team-completed")}
      </Button>
      <Modal
        footer={null}
        onCancel={() => setOpenModal(false)}
        className="w-full max-w-[540px]"
        open={openModal}
      >
        <div className="flex flex-col gap-y-10 py-6 px-4 items-center">
          <h1 className="text-[#5B656A] text-2xl text-center font-bold m-0">
            {t("mark-team-completed")}
          </h1>
          <div className="flex gap-4 flex-wrap items-center">
            <Button
              type="primary"
              onClick={onConfirm}
              className="!px-4"
              loading={isPending}
            >
              {t("yes-confirm")}
            </Button>

            <Button
              type="default"
              onClick={() => setOpenModal(false)}
              className="!px-4"
            >
              {t("no-cancel")}
            </Button>
          </div>
        </div>
      </Modal>
    </>
  );
}
