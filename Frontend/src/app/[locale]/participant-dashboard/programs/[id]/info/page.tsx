"use client";

import { Card, Spin } from "antd";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import { Link } from "@/i18n/routing";
import { Program, ProgramApplication } from "@/lib/interfaces";
import Empty from "@/components/Empty";
import dayjs from "dayjs";

export default function CompetitionInfoPage() {
  const t = useTranslations();
  const { id } = useParams();

  const { data: program } = useQuery<Program>({
    queryKey: ["program", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/programs/${id}`);
      return response.data.data;
    },
  });

  const { data: myPrograms, isLoading } = useQuery<ProgramApplication[]>({
    queryKey: ["my-programs"],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/program-applications`
      );
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!myPrograms) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <div className="flex flex-col gap-y-8" style={{ padding: "0" }}>
      <div
        className="w-full text-white p-6"
        style={{
          backgroundImage: `url(${program?.banner})`,
          backgroundSize: "cover",
          backgroundPosition: "center",
        }}
      >
        <div className="flex flex-col gap-y-14">
          <h1 className="text-sm font-normal">{t("programs")}</h1>
          <h2 className="text-3xl font-bold">{program?.title}</h2>
          <div className="flex justify-between items-center mt-4">
            {myPrograms?.some(
              (myProgram) => myProgram.program.id === Number(id)
            ) ? (
              <div className="flex justify-between items-center flex-wrap gap-4">
                <Link href={`/participant-dashboard/programs/${id}`}>
                  <button className="bg-card text-foreground font-bold text-sm px-6 py-2 rounded">
                    {t("view-application")}
                  </button>
                </Link>
                {new Date(program?.registration_closed_date || "") <
                  new Date() && (
                  <h1 className="bg-transparent border border-white rounded-xl text-white font-medium text-sm px-4 py-2">
                    {`${t("registration-closed-0")}: ${new Date(
                      program?.registration_closed_date || ""
                    ).toLocaleDateString()}`}
                  </h1>
                )}
              </div>
            ) : (
              <>
                {!program?.is_closed &&
                program?.registration_closed_date ? (
                  <>
                    <Link
                      href={`/participant-dashboard/programs/${id}/register`}
                    >
                      <button className="bg-card text-foreground font-bold text-sm px-6 py-2 rounded">
                        {t("register-now")}
                      </button>
                    </Link>
                    <p className="bg-transparent border border-white rounded-xl text-white font-medium text-sm px-4 py-2">
                      {`${t("registration-open")}: ${dayjs(
                        program.registration_closed_date
                      ).format("D-MM-YYYY")}`}
                    </p>
                  </>
                ) : (
                  <p className="bg-transparent border border-white rounded-xl text-white font-medium text-sm px-4 py-2">
                    {`${t("registration-closed-0")}: ${
                      program?.registration_closed_date
                        ? dayjs(program.registration_closed_date).format(
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
            {t("about-the-program")}
          </h1>
          <div
            className="text-[#667085] text-sm font-normal leading-6"
            dangerouslySetInnerHTML={{ __html: program?.about || "" }}
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
              __html: program?.terms_and_conditions || "",
            }}
          />
        </div>
      </Card>
    </div>
  );
}
