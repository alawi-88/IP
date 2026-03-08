"use client";
import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useQuery } from "@tanstack/react-query";
import { Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import React from "react";

export default function WinnersPage() {
  const { competitionId, id } = useParams<{
    competitionId: string;
    id: string;
  }>();
  const t = useTranslations();
  const locale = useLocale();

  // get winners
  const { data: leaderboard, isLoading } = useQuery({
    queryKey: ["leaderboard", id, locale],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(`/participants/leaderboard`, {
          params: {
            competition_id: competitionId,
          },
        });
        return response.data.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!leaderboard) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <>
      <div className="table-responsive overflow-x-auto -mt-4">
        <table className="w-full border-separate border-spacing-y-4">
          <thead>
            <tr className="bg-card text-[#808898] text-sm sm:text-base capitalize">
              <th className="p-4 whitespace-nowrap rounded-s-xl">#</th>
              <th className="p-4 whitespace-nowrap text-start">
                {t("individual_team_name")}
              </th>
              <th className="p-4 whitespace-nowrap">
                {t("registration-score")}
              </th>
              {leaderboard[0]?.stage_scores?.map((stage: any) => (
                <th key={stage.stage_id} className="p-4 whitespace-nowrap">
                  {stage.stage_title}
                </th>
              ))}
              <th className="p-4 whitespace-nowrap rounded-e-xl">
                {t("total-score")}
              </th>
            </tr>
          </thead>
          <tbody>
            {leaderboard.map((item: any, index: any) => (
              <tr
                className="bg-card text-center font-medium text-sm sm:text-base"
                key={index}
              >
                <td
                  className={`p-4 whitespace-nowrap font-bold ${
                    index === 0 ? "text-3xl text-primary" : "text-xl"
                  } rounded-s-xl`}
                >
                  {item.rank}
                </td>
                <td className="p-4 font-bold text-start">{item.name}</td>
                <td className="p-4 whitespace-nowrap">
                  {item.registration_score} {t("point")}
                </td>
                {item.stage_scores?.map((stage: any) => (
                  <td
                    key={`${item.name}_${index}_${stage.stage_id}`}
                    className="p-4 whitespace-nowrap"
                  >
                    {stage.score ?? "0"} {t("point")}
                  </td>
                ))}
                <td className="p-4 whitespace-nowrap rounded-e-xl">
                  <div className="flex items-center text-center justify-center gap-x-1">
                    <span
                      className={`font-bold ${
                        index === 0 ? "text-3xl text-primary" : "text-xl"
                      }`}
                    >
                      {item.total_score}
                    </span>{" "}
                    {t("point")}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
