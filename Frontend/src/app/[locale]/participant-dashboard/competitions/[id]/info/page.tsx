"use client";

import { Card, Spin } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import { Link } from "@/i18n/routing";
import { Competition, MyCompetition } from "@/lib/interfaces";
import Empty from "@/components/Empty";
import dayjs from "dayjs";

export default function CompetitionInfoPage() {
  const t = useTranslations();
  const { id } = useParams();

  const { data: competition } = useQuery<Competition>({
    queryKey: ["competition", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/competitions/${id}`);
      return response.data.data;
    },
  });

  const { data: myCompetitions, isLoading } = useQuery<MyCompetition[]>({
    queryKey: ["my-competitions"],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/competition-applications`
      );
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!myCompetitions) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <div className="flex flex-col gap-y-8" style={{ padding: "0" }}>
      <div
        className="w-full text-white p-6"
        style={{
          backgroundImage: `url(${competition?.banner})`,
          backgroundSize: "cover",
          backgroundPosition: "center",
        }}
      >
        <div className="flex flex-col gap-y-14">
          <h1 className="text-sm font-normal">{t("competitions")}</h1>
          <h2 className="text-3xl font-bold">{competition?.title}</h2>
          <div className="flex justify-between items-center mt-4">
            {myCompetitions?.some(
              (myCompetition) => myCompetition.competition.id === Number(id)
            ) ? (
              <div className="flex justify-between items-center flex-wrap gap-4">
                <Link href={`/participant-dashboard/competitions/${id}`}>
                  <button className="bg-card text-foreground font-bold text-sm px-6 py-2 rounded">
                    {t("view-application")}
                  </button>
                </Link>
                {new Date(competition?.registration_closed_date || "") <
                  new Date() && (
                  <h1 className="bg-transparent border border-white rounded-xl text-white font-medium text-sm px-4 py-2">
                    {`${t("registration-closed-0")}: ${new Date(
                      competition?.registration_closed_date || ""
                    ).toLocaleDateString()}`}
                  </h1>
                )}
              </div>
            ) : (
              <>
                {!competition?.is_closed &&
                competition?.registration_closed_date ? (
                  <>
                    <Link
                      href={`/participant-dashboard/competitions/${id}/register`}
                    >
                      <button className="bg-card text-foreground font-bold text-sm px-6 py-2 rounded">
                        {t("register-now")}
                      </button>
                    </Link>
                    <p className="bg-transparent border border-white rounded-xl text-white font-medium text-sm px-4 py-2">
                      {`${t("registration-open")}: ${dayjs(
                        competition.registration_closed_date
                      ).format("D-MM-YYYY")}`}
                    </p>
                  </>
                ) : (
                  <p className="bg-transparent border border-white rounded-xl text-white font-medium text-sm px-4 py-2">
                    {`${t("registration-closed-0")}: ${
                      competition?.registration_closed_date
                        ? dayjs(competition.registration_closed_date).format(
                            "D-MM-YYYY"
                          )
                        : t("undefined-date")
                    }`}
                  </p>
                )}
              </>
            )}
          </div>
        </div>
      </div>
      <Card>
        <div className="flex flex-col gap-y-5">
          <h1 className="text-lg font-bold text-[#5B656A]">
            {t("about-the-competition")}
          </h1>
          <div
            className="text-[#667085] text-sm font-normal leading-6"
            dangerouslySetInnerHTML={{ __html: competition?.about || "" }}
          />
        </div>
      </Card>
      <Card>
        <div className="flex flex-col gap-y-5">
          <h1 className="text-lg font-bold text-[#5B656A]">
            {t("terms-and-conditions")}
          </h1>
          <div
            className="text-[#667085] text-sm font-normal leading-6"
            dangerouslySetInnerHTML={{
              __html: competition?.terms_and_conditions || "",
            }}
          />
        </div>
      </Card>
    </div>
  );
}
