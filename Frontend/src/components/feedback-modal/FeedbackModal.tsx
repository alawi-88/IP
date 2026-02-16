"use client";

import { Button, Modal } from "antd";
import ErrorSvg from "./_svgs/error";
import EnvelopeIcon from "./_svgs/envelopeIcon";
import { useTranslations } from "next-intl";
import SuccessSvg from "./_svgs/success";

export default function FeedbackModal({
  onClose,
  openModal,
  subtitle,
  title,
  type,
  onBtnClick,
  btnLabel,
  children,
}: {
  openModal: boolean;
  onClose?: () => void;
  title: string;
  subtitle?: string;
  type: "success" | "error" | "message";
  onBtnClick?: () => void;
  btnLabel?: string;
  children?: React.ReactNode;
}) {
  const t = useTranslations();

  return (
    <Modal
      footer={null}
      open={openModal}
      onCancel={onClose}
      className="successModal"
      closable={false}
    >
      <div className="relative w-full h-full">
        <div
          className="bg-[#F2F4F7]"
          style={{
            position: "absolute",
            top: "-85%",
            left: "-10%",
            width: "120%",
            height: "120%",

            borderRadius: "0 0 50% 50%",
            zIndex: "9",
          }}
        ></div>

        <div className="relative text-center py-10 gap-y-4 bg-card flex flex-col items-center">
          <div className="flex relative z-10 justify-center">
            {type === "success" ? (
              <SuccessSvg />
            ) : type === "message" ? (
              <EnvelopeIcon />
            ) : (
              <ErrorSvg />
            )}
          </div>

          <div className="px-6">
            <h2 className="text-[#5B656A] font-medium text-2xl">{title}</h2>
            {subtitle && <p className=" text-[#667085]">{subtitle}</p>}
          </div>

          {children ? (
            children
          ) : (
            <Button
              type="primary"
              style={{
                paddingInline: "20px",
                marginBlock: "10px",
              }}
              onClick={onBtnClick}
            >
              {btnLabel ? btnLabel : type === "success" ? t("ok") : null}
            </Button>
          )}
        </div>
      </div>
    </Modal>
  );
}
