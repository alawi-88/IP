import { Button, message, Modal } from "antd";
import React, { useEffect, useState } from "react";
import SuccessSvg from "../feedback-modal/_svgs/success";
import { useTranslations } from "next-intl";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import { ApiError } from "next/dist/server/api-utils";
import { useRouter } from "@/i18n/routing";

type Props = {
  isOpen?: boolean;
  disclaimerText: string | undefined;
  disclaimerRequired?: boolean;
  projectId: string;
  stage?: any;
  onAgree?: () => void;
  onDisagree?: () => void;
  onClose: () => void;
};

function DisclaimerModal({
  isOpen,
  disclaimerText,
  disclaimerRequired,
  projectId,
  stage,
  onAgree,
  onDisagree,
  onClose,
}: Props) {
  const t = useTranslations();
  const [decision, setDecision] = useState<"initial" | "agree" | "disagree">(
    "initial"
  );
  const [cancelWaring, setCancelWaring] = useState("");
  const router = useRouter();
  const queryClient = useQueryClient();
  const [messageApi, contextHolder] = message.useMessage();

  // post the disclaimer
  const { mutate, isPending, reset } = useMutation({
    mutationFn: async (values: any) => {
      const response = await axiosInstance.post(
        `/judge/accept-disclaimer`,
        {
          ...values,
          stage_id: stage?.id,
          form_id: stage?.forms[0]?.id,
          project_id: projectId,
        }
      );

      return response.data;
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["judge-project", projectId],
        refetchType: "all",
      });
      if (decision === "disagree") {
        handleOnDisagree();
      } else {
        handleOnAgree();
      }
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
    onMutate: () => {
      setCancelWaring("");
    },
  });

  // handle cancel
  function handleCancel() {
    messageApi.destroy();
    setCancelWaring(t("cancel-disclaimer-waring"));
  }

  // handle onAgree
  function handleOnAgree() {
    onClose();
    if (onAgree) {
      onAgree();
    }
  }
  // handle onDisagree
  function handleOnDisagree() {
    onClose();
    if (onDisagree) {
      onDisagree();
    } else {
      router.push("/judge/judge-dashboard");
    }
  }

  useEffect(() => {
    messageApi.destroy();
    setCancelWaring("");
    reset();
  }, [decision]);

  return (
    <>
      {contextHolder}
      <Modal
        footer={null}
        open={isOpen}
        onCancel={handleCancel}
        className="disclaimer-modal successModal"
        centered
      >
        <div className="relative w-full h-full sm:max-h-[95vh] overflow-y-auto">
          <div className="relative text-center py-10 px-6 gap-y-8 sm:gap-y-10 bg-card flex flex-col items-center">
            <div className="flex relative z-10 justify-center">
              <SuccessSvg />
            </div>

            <h3 className="font-semibold text-xl">
              {decision === "disagree"
                ? t("disagree-disclaimer")
                : disclaimerText}
            </h3>

            <div className="modal-actions">
              <div className="flex items-center justify-center gap-x-6 gap-y-4 flex-col sm:flex-row [&_button]:min-w-[200px]">
                {decision === "disagree" ? (
                  <>
                    <Button
                      onClick={() => setDecision("initial")}
                      className="min-w-auto"
                    >
                      {t("back-to-disclaimer")}
                    </Button>
                    <Button
                      className="min-w-auto"
                      type="primary"
                      onClick={() => {
                        mutate({ accepted: false });
                      }}
                      loading={isPending}
                    >
                      {t("confirm-disagree")}
                    </Button>
                  </>
                ) : (
                  <>
                    <Button
                      onClick={() => {
                        disclaimerRequired
                          ? setDecision("disagree")
                          : onClose();
                      }}
                      className="min-w-auto"
                    >
                      {t("disagree")}
                    </Button>
                    <Button
                      className="min-w-auto"
                      type="primary"
                      onClick={() => {
                        mutate({ accepted: true });
                      }}
                      loading={isPending}
                    >
                      {t("agree")}
                    </Button>
                  </>
                  // <>
                  //   {disclaimerRequired ? (
                  //     <>
                  //       <Button
                  //         onClick={() => setDecision("disagree")}
                  //         className="min-w-auto"
                  //       >
                  //         {t("disagree")}
                  //       </Button>
                  //       <Button
                  //         className="min-w-auto"
                  //         type="primary"
                  //         onClick={() => {
                  //           mutate({ accepted: true });
                  //         }}
                  //         loading={isPending}
                  //       >
                  //         {t("agree")}
                  //       </Button>
                  //     </>
                  //   ) : (
                  //     <Button
                  //       className="min-w-auto"
                  //       type="primary"
                  //       onClick={() => mutate({ accepted: true })}
                  //       loading={isPending}
                  //     >
                  //       {t("continue")}
                  //     </Button>
                  //   )}
                  // </>
                )}
              </div>
              {cancelWaring && (
                <p className="mt-6 font-medium text-[#F13C61]">
                  {cancelWaring}
                </p>
              )}
            </div>
          </div>
        </div>
      </Modal>
    </>
  );
}

export default DisclaimerModal;
