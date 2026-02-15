"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import FilterResultsModal from "@/components/FilterResultsModal";
import { Link } from "@/i18n/routing";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Button, Form, Select, Spin } from "antd";
import { useForm } from "antd/es/form/Form";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useState } from "react";
import { createPortal } from "react-dom";
import { BsArrowLeft, BsArrowRight } from "react-icons/bs";

interface Event {
  id: number;
  title: string;
  brief: string;
  badge: string;
  date: string;
  time: string;
  location: string;
  speaker_photo: string;
  speaker_name: string;
  speaker_experience: string;
  speaker_brief: string;
  event_link: string;
}

interface PaginationMeta {
  path: string;
  per_page: number;
  next_cursor: null;
  prev_cursor: null;
}

export default function EventsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = useForm();
  const { id } = useParams<{ id: string }>();

  const [query, setQuery] = useState<{
    badge?: string;
  }>();

  const { data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery<{
      data: Event[];
      meta: PaginationMeta;
    }>({
      queryKey: ["events", id, locale, query],
      queryFn: async ({ pageParam = undefined }) => {
        const response = await axiosInstance.get(`/participants/events`, {
          params: {
            ...query,
            cursor: pageParam,
            application_id: id,
          },
        });
        return response.data;
      },
      initialPageParam: undefined,
      getNextPageParam: (lastPage) => lastPage?.meta?.next_cursor ?? undefined,
    });

  const events = data?.pages.flatMap((page) => page.data) ?? [];

  const onSubmit = (values: any) => {
    setQuery({ badge: values.badge });
  };

  if (isLoading) {
    return <Spin />;
  }

  return (
    <>
      {createPortal(
        <FilterResultsModal form={form} filterResults={onSubmit}>
          <Form.Item label={t("event-status")} name={"badge"}>
            <Select
              placeholder={t("choose")}
              optionFilterProp="children"
              options={[
                {
                  label: t("upcoming"),
                  value: "upcoming",
                },
                {
                  label: t("completed"),
                  value: "completed",
                },
              ]}
              allowClear
            />
          </Form.Item>
        </FilterResultsModal>,
        document.getElementById("filter-section") as HTMLElement
      )}

      {!events?.length ? (
        <Empty description={t("no-events-yet")} />
      ) : (
        <div className="space-y-4">
          {events.map((event) => {
            const day = new Date(event.date).getDate();
            const month = new Date(event.date).toLocaleString(locale, {
              month: "long",
            });
            return (
              <div
                key={event.id}
                className="bg-card rounded-lg p-4 flex flex-col md:flex-row items-center justify-between gap-6 md:max-h-32"
              >
                <div className="flex items-center gap-x-5">
                  <div className="flex flex-col items-center justify-center w-24 h-16 text-sm text-primary bg-[#F2F4F7] rounded-lg">
                    <p>{day}</p>
                    <p className="font-bold">{month}</p>
                  </div>
                  <div className="flex flex-col gap-y-2 overflow-hidden">
                    <h3 className="text-xl font-bold text-[#5B656A]">
                      {event.title}
                    </h3>
                    <div className="overflow-hidden max-h-[72px]">
                      {" "}
                      {/* 18px * 4 lines = 72px */}
                      <p className="text-[#667085] text-sm m-0 text-justify leading-[18px] line-clamp-4">
                        {event.brief}
                      </p>
                    </div>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div
                    className={`px-6 py-2 rounded-full text-sm font-medium ${
                      event.badge !== "completed"
                        ? "bg-[#E1F7F6] text-[#08BCB8]"
                        : "bg-[#FDE8EC] text-[#F13C61]"
                    }`}
                  >
                    {event.badge === "completed"
                      ? t("completed")
                      : t("upcoming")}
                  </div>
                  <Link href={`events/${event.id}`}>
                    <Button
                      type="default"
                      icon={
                        locale === "en" ? <BsArrowRight /> : <BsArrowLeft />
                      }
                      iconPosition="end"
                    >
                      {t("view-details")}
                    </Button>
                  </Link>
                </div>
              </div>
            );
          })}

          {hasNextPage && (
            <div className="flex justify-center">
              <Button
                onClick={() => fetchNextPage()}
                loading={isFetchingNextPage}
              >
                {t("load-more")}
              </Button>
            </div>
          )}
        </div>
      )}
    </>
  );
}
