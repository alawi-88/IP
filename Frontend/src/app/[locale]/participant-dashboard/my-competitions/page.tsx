"use client";

import axiosInstance from "@/axios";
import { Link } from "@/i18n/routing";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { Button, ConfigProvider, Segmented, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { FiArrowLeft, FiArrowRight } from "react-icons/fi";
import Empty from "@/components/Empty";
import dayjs from "dayjs";
import { MyProgram } from "@/lib/interfaces";
import { useEffect, useState } from "react";

export default function MyCompetitions() {
  const t = useTranslations();
  const locale = useLocale() as "en" | "ar";
  const [query, setQuery] = useState<{
    status?: string;
    program_type?: string;
  }>({
    status: undefined,
    program_type: undefined,
  });

  const {
    data: programs,
    isLoading,
    isRefetching,
  } = useQuery<MyProgram>({
    queryKey: ["my-competitions", query, locale],
    queryFn: async () => {
      const params = { ...query };
      const response = await axiosInstance.get(
        `/participants/competition-applications`,
        {
          params,
        }
      );
      return response.data;
    },
    placeholderData: keepPreviousData,
    refetchOnWindowFocus: false,
  });

  // const { data: submittedApplications } = useQuery({
  //   queryKey: ["submitted-evaluations"],
  //   queryFn: async () => {
  //     const promises =
  //       programs?.data?.map(async (comp) => {
  //         const response = await axiosInstance.get(
  //           `/participants/satisfactions/is-submitted?application_id=${comp.id}`
  //         );
  //         return {
  //           applicationId: comp.id,
  //           isSubmitted: response.data.submitted,
  //         };
  //       }) || [];
  //     const results = await Promise.all(promises);
  //     return results;
  //   },
  //   enabled: !!programs?.data?.length,
  // });

  // get first valid program type
  useEffect(() => {
    if (!query.program_type && programs) {
      const firstAvailableType = programs.programs_types?.find(
        (item: any) => item.count > 0
      );
      if (firstAvailableType) {
        setQuery((prev) => ({
          ...prev,
          program_type: firstAvailableType.slug || firstAvailableType.title,
        }));
      }
    }
  }, [programs]);

  return (
    <section className="flex flex-col gap-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl text-primary-900 font-bold">
          {t("my-competitions")}
        </h1>
      </div>

      {isLoading && <Spin />}
      {programs?.programs_types?.some((item: any) => item.count > 0) && (
        <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
          <Segmented
            className="!bg-card !rounded-xl !p-2 !w-fit"
            options={[
              // { label: t("all"), value: "all" },
              ...(programs?.programs_types
                ?.filter((item: any) => item.count > 0)
                .map((item: any) => ({
                  label: item.title,
                  value: item.slug,
                })) || []),
            ]}
            value={query?.program_type || "all"}
            onChange={(programType: string) => {
              setQuery((prev) => ({
                ...prev,
                program_type: programType === "all" ? undefined : programType,
              }));
            }}
          />
        </ConfigProvider>
      )}
      {!programs?.data?.length && !isLoading && !isRefetching ? (
        <Empty
          description={
            <span className="font-bold">
              {t("sorry-you-have-not-registered-for-a-competition-yet")}
            </span>
          }
        >
          <Link href="/participant-dashboard">
            <Button type="primary">
              {t("discover-the-available-competitions")}
            </Button>
          </Link>
        </Empty>
      ) : isRefetching ? (
        <Spin />
      ) : (
        programs?.data.length && (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {programs?.data?.map(({ id, competition, status, created_at }) => (
              <div
                key={id}
                className="rounded-xl shadow-md overflow-hidden p-6 max-w-[33.125rem] h-[26.25rem] relative flex flex-col justify-between"
              >
                <Image
                  src={competition.banner}
                  alt="Card Image"
                  className="w-full h-full object-cover absolute inset-0 "
                  width={300}
                  height={300}
                />

                <div className="z-10 w-full h-full flex flex-col justify-between">
                  {/* {competition.current_stage != null ? (
                    <p className="text-sm font-medium bg-success text-[#4B736F] p-2 rounded-[40px] w-48 flex items-center justify-center">
                      {competition.current_stage}
                    </p>
                  ) : (
                    <p></p>
                  )} */}
                  <p></p>
                  <div className="flex flex-col gap-y-3 p-6 bg-card rounded-lg w-full">
                    <h2 className="font-medium text-primary-900 text-2xl">
                      {competition.title}
                    </h2>
                    <div className="flex justify-between text-[#98A2B3] text-sm font-medium gap-3 xl:items-center xl:flex-row flex-col">
                      {created_at && (
                        <p>
                          {t("registration-date")}:{" "}
                          {dayjs(created_at).format("D-MM-YYYY")}
                        </p>
                      )}

                      <p>
                        {t("application-status")}: {t(status)}
                      </p>
                    </div>

                    {status === "approved" ? (
                      <div className="flex justify-between">
                        <Link
                          href={`/participant-dashboard/my-competitions/${competition.id}/${id}`}
                        >
                          <Button
                            type="primary"
                            size="small"
                            icon={
                              locale === "ar" ? (
                                <FiArrowLeft />
                              ) : (
                                <FiArrowRight />
                              )
                            }
                            iconPosition="end"
                          >
                            {t("more-details")}
                          </Button>
                        </Link>
                      </div>
                    ) : (
                      <div className="w-auto">
                        <Button type="primary" size="small" disabled>
                          {t("more-details")}
                        </Button>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )
      )}

      {/* {isLoading === true ? (
        <Spin />
      ) : !programs?.data?.length ? (
        <Empty
          description={
            <span className="font-bold">
              {t("sorry-you-have-not-registered-for-a-competition-yet")}
            </span>
          }
        >
          <Link href="/participant-dashboard">
            <Button type="primary">
              {t("discover-the-available-competitions")}
            </Button>
          </Link>
        </Empty>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {programs?.data?.map(({ id, competition, status, created_at }) => (
            <div
              key={id}
              className="rounded-xl shadow-md overflow-hidden p-6 max-w-[33.125rem] h-[26.25rem] relative flex flex-col justify-between"
            >
              <Image
                src={competition.banner}
                alt="Card Image"
                className="w-full h-full object-cover absolute inset-0 "
                width={300}
                height={300}
              />

              <div className="z-10 w-full h-full flex flex-col justify-between">
                {competition.current_stage != null ? (
                  <p className="text-sm font-medium bg-success text-[#4B736F] p-2 rounded-[40px] w-48 flex items-center justify-center">
                    {competition.current_stage}
                  </p>
                ) : (
                  <p></p>
                )}

                <div className="flex flex-col gap-y-3 p-6 bg-card rounded-lg w-full">
                  <h2 className="font-medium text-primary-900 text-2xl">
                    {competition.title}
                  </h2>
                  <div className="flex items-center justify-between text-[#98A2B3] text-sm font-medium">
                    {created_at && (
                      <p>
                        {t("registration-date")}:{" "}
                        {dayjs(created_at).format("D-MM-YYYY")}
                      </p>
                    )}

                    <p>
                      {t("application-status")}: {t(status)}
                    </p>
                  </div>

                  {status === "approved" ? (
                    <div className="flex justify-between">
                      <Link
                        href={`/participant-dashboard/my-competitions/${competition.id}/${id}/evaluate`}
                      >
                        <Button
                          type="primary"
                          size="small"
                          icon={
                            locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />
                          }
                          iconPosition="end"
                          disabled={
                            submittedApplications?.find(
                              (app) => app.applicationId === id
                            )?.isSubmitted
                          }
                        >
                          {t("rate-experience")}
                        </Button>
                      </Link>
                      <Link
                        href={`/participant-dashboard/my-competitions/${competition.id}/${id}`}
                      >
                        <Button
                          type="primary"
                          size="small"
                          icon={
                            locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />
                          }
                          iconPosition="end"
                        >
                          {t("more-details")}
                        </Button>
                      </Link>
                    </div>
                  ) : (
                    <div className="w-auto">
                      <Button type="primary" size="small" disabled>
                        {t("more-details")}
                      </Button>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )} */}
    </section>
  );
}
