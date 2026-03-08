"use client";

import {
  Card,
  Divider,
  Form,
  Input,
  Radio,
  Segmented,
  Slider,
  Spin,
  ConfigProvider,
} from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/axios";
import React, { useEffect, useState } from "react";
import Empty from "@/components/Empty";

export default function Evaluation() {
  const t = useTranslations();
  const locale = useLocale();
  const { projectId, formId } = useParams<{
    projectId: string;
    formId: string;
  }>();
  const [selectedEvaluation, setSelectedEvaluation] = useState<any | null>(
    null
  );

  // get evaluations
  const { data: evaluations, isLoading } = useQuery({
    queryKey: ["judge-project-evaluations", projectId],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/judges/evaluations?project_id=${projectId}`
      );
      return response.data.data;
    },
  });

  // set evaluation
  useEffect(() => {
    if (evaluations && evaluations.length > 0 && !selectedEvaluation) {
      setSelectedEvaluation(evaluations[0]);
    }
  }, [evaluations]);

  if (isLoading) {
    return <Spin className="w-full flex justify-center" />;
  }

  if (!evaluations || evaluations.length === 0 || !selectedEvaluation) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <div className="flex flex-col">
      {evaluations.length > 1 ? (
        <div className="mb-6">
          <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
            <Segmented
              className="!bg-card !rounded-xl !p-2 !w-fit"
              options={evaluations.map((item: any) => ({
                label: item.stage.title,
                value: item.stage.id,
              }))}
              value={selectedEvaluation.stage.id}
              onChange={(stageId: number) => {
                const found = evaluations.find(
                  (ev: any) => ev.stage.id === stageId
                );
                if (found) {
                  setSelectedEvaluation(found);
                }
              }}
            />
          </ConfigProvider>
        </div>
      ) : (
        <h1 className="font-bold mb-4">{selectedEvaluation.stage.title}</h1>
      )}

      <div className="flex justify-between items-center gap-x-6 gap-y-3 flex-wrap mb-6">
        <p className="text-sm text-[#626262] font-medium mb-0">
          {t("evaluation-date")} :{" "}
          {new Date(selectedEvaluation?.created_at).toLocaleDateString(
            "en-GB",
            {
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
            }
          )}
        </p>
        <div className="bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] text-primary border border-primary px-5 py-2 rounded-md text-base font-medium">
          {selectedEvaluation?.total}%
        </div>
      </div>

      <Card className="p-6">
        <div className="flex justify-between items-baseline gap-x-4 bg-[#F2F4F7] p-4 rounded-lg xl:grid xl:grid-cols-[3fr_2fr]">
          <div className="font-medium text-foreground">{t("criteria")}</div>
          <div className="font-medium text-foreground">
            {t("evaluation-score")}
          </div>
        </div>

        {selectedEvaluation?.stage?.evolution?.map(
          (item: any, index: number) => (
            <div className="mt-6 space-y-3" key={item.id}>
              <div className="flex justify-between items-baseline gap-x-4 xl:grid xl:grid-cols-[3fr_2fr]">
                <div className="flex flex-col gap-y-2 justify-center">
                  <p className="text-secondary font-bold">
                    <span className="me-1">{index + 1}- {item.question.title}</span>
                    {parseInt(item.question.weight) == 0 && (
                      <span
                        className={`inline-block align-middle text-xs py-1 px-2 rounded-lg font-medium border bg-[#F6F7F9] !text-[#626262] border-[#DEE1E6]`}
                      >
                        {t("not-included-evaluation")}
                      </span>
                    )}
                  </p>

                  {item.question.comment && (
                    <p className="text-[#626262] text-sm">
                      {t("evaluation-comment.comment")} :{" "}
                      {item.question.comment}
                    </p>
                  )}
                </div>
                <p className="me-0 font-medium text-sm">
                  <span className="bg-[#F2F4F7] border border-[#E1E1E1] py-2 rounded-md w-16 flex items-center justify-center">
                    {Number(item.question.value)?.toFixed(2) ||
                      item.question.value}
                  </span>
                </p>
              </div>
              {item.subquestions.length > 0 && (
                <div className="space-y-3">
                  {item.subquestions.map((qa: any, idx: number) => (
                    <div
                      className="flex justify-between items-baseline gap-x-4 xl:grid xl:grid-cols-[3fr_2fr]"
                      key={idx}
                    >
                      <div className="flex flex-col gap-y-2 justify-center">
                        <p className="text-sm font-medium">
                          {index + 1}.{idx + 1}- {qa.title[locale]}
                        </p>

                        {qa.comment && (
                          <p className="text-[#626262] text-sm">
                            {t("evaluation-comment.comment")} : {qa.comment}
                          </p>
                        )}
                      </div>
                      <p className="me-0 font-medium text-sm">
                        <span className="bg-[#F2F4F7] border border-[#E1E1E1] text-foreground py-2 rounded-md w-16 flex items-center justify-center">
                          {Number(qa.value)?.toFixed(2) || qa.value}
                        </span>
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )
        )}

        {selectedEvaluation.final_comment && (
          <>
            <Divider />
            <div className="flex flex-col gap-y-3 justify-center">
              <p className="font-bold">
                {t("evaluation-comment.final-comment")}
              </p>
              <p className="text-[#626262] text-sm">
                {selectedEvaluation.final_comment || "test test"}
              </p>
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
