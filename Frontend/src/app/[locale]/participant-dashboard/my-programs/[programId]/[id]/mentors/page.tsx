"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import FilterResultsModal from "@/components/FilterResultsModal";
import { Link } from "@/i18n/routing";
import { Mentor } from "@/lib/interfaces";
import { useInfiniteQuery, useQuery } from "@tanstack/react-query";
import { Button, DatePicker, Divider, Form, Select, Spin, Input } from "antd";
import { useForm } from "antd/es/form/Form";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import { useState } from "react";
import { createPortal } from "react-dom";
import { FaRegUser } from "react-icons/fa";

interface PaginationMeta {
  path: string;
  per_page: number;
  next_cursor: null;
  prev_cursor: null;
}

export default function MentorsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = useForm();
  const { programId, id } = useParams<{
    programId: string;
    id: string;
  }>();
  const [query, setQuery] = useState({});

  const {
    data,
    isLoading,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    refetch,
  } = useInfiniteQuery<{
    data: Mentor[];
    meta: PaginationMeta;
  }>({
    queryKey: ["mentors", id, locale, query],
    queryFn: async ({ pageParam = undefined }) => {
      const response = await axiosInstance.get(`/participants/mentors`, {
        params: {
          application_id: id,
          cursor: pageParam,
          ...query,
        },
      });
      return response.data;
    },
    initialPageParam: undefined,
    getNextPageParam: (lastPage) => lastPage?.meta?.next_cursor ?? undefined,
  });

  const mentors = data?.pages.flatMap((page) => page.data) ?? [];

  const onSubmit = (values: any) => {
    const formattedValues = {
      ...values,
      available_on: values.available_on
        ? values.available_on.format("YYYY-MM-DD")
        : undefined,
    };

    if (formattedValues.available_on) {
      formattedValues.has_availability = true;
    }

    const filteredValues = Object.fromEntries(
      Object.entries(formattedValues).filter(
        ([, value]) => value !== "" && value !== undefined && value !== null
      )
    );

    setQuery(filteredValues);
  };

  return (
    <>
      <div className="flex items-center justify-between flex-wrap gap-x-2 gap-y-3">
        <div className="tabs-link flex flex-wrap gap-x-2 gap-y-3">
          <Link
            href={`/participant-dashboard/my-programs/${programId}/${id}/mentors`}
          >
            <Button type="primary">{t("mentors")}</Button>
          </Link>
          <Link
            href={`/participant-dashboard/my-programs/${programId}/${id}/mentors-sessions`}
          >
            <Button type="default">{t("mentor.sessions")}</Button>
          </Link>
        </div>
        <FilterResultsModal form={form} filterResults={onSubmit}>
          <Form.Item label={t("search")} name="search">
            <Input
              type="text"
              allowClear
              placeholder={t("mentor.search-by-name-or-skill")}
            />
          </Form.Item>

          <Form.Item label={t("mentor.availability")} name="available_on">
            <DatePicker
              className="w-full"
              format="YYYY-MM-DD"
              popupClassName="modal-filters-datepicker-dropdown"
              getPopupContainer={(triggerNode) =>
                triggerNode.closest(".ant-modal") as HTMLElement
              }
            />
          </Form.Item>
        </FilterResultsModal>
      </div>

      {isLoading ? (
        <Spin />
      ) : !mentors?.length ? (
        <Empty description={t("mentor.not-found-mentors")} />
      ) : (
        <div className="grid grid-cols-1 sm:[grid-auto-rows:1fr] sm:grid-cols-2 xl:grid-cols-3 gap-4">
          {mentors.map((mentor) => (
            <div
              key={mentor.id}
              className="px-5 py-4 bg-card rounded-xl flex flex-col gap-y-4 sm:min-h-56"
            >
              <div className="flex gap-x-2">
                {mentor.image ? (
                  <Image
                    src={mentor.image}
                    className="w-12 h-12 rounded-full object-cover flex-shrink-0"
                    width={48}
                    height={48}
                    alt={mentor.name}
                  />
                ) : (
                  <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center  flex-shrink-0">
                    <FaRegUser className="text-white b" />
                  </div>
                )}
                <div className="flex flex-col gap-y-2">
                  <h3 className="font-bold text-sm m-0">{mentor.name}</h3>
                  <p className="text-[#667084] text-sm">{mentor.profession}</p>
                </div>
              </div>

              <Divider className="!m-0 bg-[#DEE1E6]" />

              <p className="text-[#667084] text-sm m-0 line-clamp-4 flex-grow">
                {mentor.brief || mentor.experience || ""}
              </p>

              <div className="mt-auto">
                <Link
                  href={`/participant-dashboard/my-programs/${programId}/${id}/mentors/${mentor.id}`}
                >
                  <Button type="primary">{t("book-session")}</Button>
                </Link>
              </div>
            </div>
          ))}
        </div>
      )}

      {hasNextPage && (
        <div className="flex justify-center">
          <Button onClick={() => fetchNextPage()} loading={isFetchingNextPage}>
            {t("load-more")}
          </Button>
        </div>
      )}
    </>
  );
}
