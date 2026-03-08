"use client";

import axiosInstance from "@/axios";
import FilterResultsModal from "@/components/FilterResultsModal";
import { Link, useRouter } from "@/i18n/routing";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { Button, ConfigProvider, Form, Segmented, Select, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { useEffect, useState } from "react";
import { FiArrowLeft, FiArrowRight } from "react-icons/fi";
import Empty from "@/components/Empty";
import dayjs from "dayjs";
import { ProgramList, ProgramApplicationList, Program, ProgramApplication } from "@/lib/interfaces";
import { programsTypes } from "@/lib/constants";
import { useSearchParams } from "next/navigation";

export default function ParticipantDashboard() {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const [form] = Form.useForm();
  const [query, setQuery] = useState<{
    status?: string;
    program_type?: string;
  }>({
    status: undefined,
    program_type: undefined,
  });
  const searchParams = useSearchParams();
  const paramProgramType = searchParams.get("program_type");
  const currentProgramType =
    paramProgramType && programsTypes.includes(paramProgramType)
      ? paramProgramType
      : programsTypes[0];

  const {
    data: programs,
    isLoading,
    isRefetching,
  } = useQuery<ProgramList | undefined>({
    queryKey: ["programs", query, locale, currentProgramType],
    queryFn: async () => {
      const params = { ...query };
      const response = await axiosInstance.get(
        `/programs?program_type=${currentProgramType}`,
        {
          params,
        }
      );
      return response.data;
    },
    // placeholderData: keepPreviousData,
    refetchOnWindowFocus: false,
  });

  const { data: myPrograms } = useQuery<ProgramApplication[]>({
    queryKey: ["my-programs"],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/program-applications`
      );
      return response.data.data;
    },
  });

  const onSubmit = (values: any) => {
    setQuery((prev) => ({
      ...prev,
      status: values.status,
    }));
  };

  // reset status when program_type changes for separate pages logic
  useEffect(() => {
    setQuery((prev) => ({
      ...prev,
      status: undefined,
    }));
    if (query.status) {
      form.resetFields();
    }
  }, [currentProgramType]);

  // // get first valid program type
  // useEffect(() => {
  //   if (!query.program_type && programs) {
  //     const firstAvailableType = programs.programs_types?.find(
  //       (item: any) => item.count > 0
  //     );
  //     if (firstAvailableType) {
  //       setQuery((prev) => ({
  //         ...prev,
  //         program_type: firstAvailableType.slug || firstAvailableType.title,
  //       }));
  //     }
  //   }
  // }, [programs]);

  return (
    <>
      <section className="flex flex-col gap-y-6 ">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl text-primary-900 font-bold">
            {/* {t("programs")} */}
            {t(`programs-types.${currentProgramType}`)}
          </h1>
          <FilterResultsModal filterResults={onSubmit} form={form}>
            <Form.Item label={t("registration-status")} name={"status"}>
              <Select
                placeholder={t("choose")}
                optionFilterProp="children"
                options={[
                  {
                    label: t("registration-open"),
                    value: "open",
                  },
                  {
                    label: t("registration-closed"),
                    value: "closed",
                  },
                ]}
                allowClear
              />
            </Form.Item>
          </FilterResultsModal>
        </div>

        {isLoading && <Spin />}
        {/* {programs?.programs_types?.some((item: any) => item.count > 0) && (
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
        )} */}
        {!programs?.data?.length && !isLoading && !isRefetching ? (
          <Empty description={t("sorry-no-programs-available")} />
        ) : (
          programs?.data.length && (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
              {programs?.data?.map((program) => (
                <div
                  onClick={() =>
                    router.push(
                      `/participant-dashboard/programs/${program.id}/info`
                    )
                  }
                  key={program.id}
                  className="rounded-xl shadow-md overflow-hidden p-6 max-w-[33.125rem] h-[26.25rem] relative flex flex-col justify-between cursor-pointer"
                >
                  <Image
                    src={program.banner}
                    alt="Card Image"
                    className="w-full h-full object-cover absolute inset-0 "
                    width={300}
                    height={300}
                  />

                  <div className="z-10 w-full h-full flex flex-col justify-between">
                    <div className="flex justify-between gap-x-4 gap-y-2 items-center flex-wrap">
                      <p className="text-sm font-medium bg-success text-[#4B736F] p-2 rounded-[40px] w-48 flex items-center justify-center">
                        {!program.registration_closed_date ||
                        program.is_closed
                          ? t("registration-closed")
                          : t("registration-open")}
                      </p>
                      {myPrograms?.find(
                        (myProgram) =>
                          myProgram.program.id === program.id
                      )?.has_comment ? (
                        <span className="inline-block text-sm py-1 px-2 rounded-lg font-medium border bg-[#E1F7F6] text-[#08BCB8] border-[#CEF2F1]">
                          {t("new-comment")}
                        </span>
                      ) : null}
                    </div>

                    <div className="flex flex-col gap-y-3 p-6 bg-card rounded-lg w-full">
                      <h2 className="font-medium text-primary-900 text-2xl">
                        {program.title}
                      </h2>
                      <div className="flex justify-between gap-3 xl:items-center xl:flex-row flex-col">
                        <p className="text-[#98A2B3] text-sm font-medium">
                          {t("registration-closed-0")}:{" "}
                          {program.registration_closed_date
                            ? dayjs(
                                program.registration_closed_date
                              ).format("D-MM-YYYY")
                            : t("undefined-date")}
                        </p>

                        {!program.registration_closed_date ||
                        program.is_closed ||
                        myPrograms?.some(
                          (myProgram) =>
                            myProgram.program.id === program.id
                        ) ? (
                          <Link
                            href={`/participant-dashboard/programs/${program.id}/info`}
                            onClick={(e) => e.stopPropagation()}
                          >
                            <Button type="primary" size="small">
                              {t("more-details")}
                            </Button>
                          </Link>
                        ) : (
                          <Link
                            href={`/participant-dashboard/programs/${program.id}/register`}
                            onClick={(e) => e.stopPropagation()}
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
                              {t("register-now")}
                            </Button>
                          </Link>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )
        )}

        {/* {isLoading ? (
          <Spin />
        ) : !programs?.data?.length ? (
          <Empty description={t("sorry-no-programs-available")} />
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {programs?.data?.map((program) => (
              <div
                key={program.id}
                className="rounded-xl shadow-md overflow-hidden p-6 max-w-[33.125rem] h-[26.25rem] relative flex flex-col justify-between"
              >
                <Image
                  src={program.banner}
                  alt="Card Image"
                  className="w-full h-full object-cover absolute inset-0 "
                  width={300}
                  height={300}
                />

                <div className="z-10 w-full h-full flex flex-col justify-between">
                  <p className="text-sm font-medium bg-success text-[#4B736F] p-2 rounded-[40px] w-48 flex items-center justify-center">
                    {!program.registration_closed_date ||
                    dayjs(program.registration_closed_date).isBefore(
                      dayjs()
                    )
                      ? t("registration-closed")
                      : t("registration-open")}
                  </p>

                  <div className="flex flex-col gap-y-3 p-6 bg-card rounded-lg w-full">
                    <h2 className="font-medium text-primary-900 text-2xl">
                      {program.title}
                    </h2>
                    <div className="flex items-center justify-between">
                      <p className="text-[#98A2B3] text-sm font-medium">
                        {t("registration-closed-0")}:{" "}
                        {program.registration_closed_date
                          ? dayjs(program.registration_closed_date).format(
                              "D-MM-YYYY"
                            )
                          : t("undefined-date")}
                      </p>

                      {!program.registration_closed_date ||
                      dayjs(program.registration_closed_date).isBefore(
                        dayjs()
                      ) ||
                      myPrograms?.some(
                        (myProgram) =>
                          myProgram.program.id === program.id
                      ) ? (
                        <Link
                          href={`/participant-dashboard/programs/${program.id}/info`}
                        >
                          <Button type="primary" size="small">
                            {t("more-details")}
                          </Button>
                        </Link>
                      ) : (
                        <Link
                          href={`/participant-dashboard/programs/${program.id}/register`}
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
                            {t("register-now")}
                          </Button>
                        </Link>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )} */}
      </section>
    </>
  );
}