"use client";
import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Button, Divider, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { FaRegUser } from "react-icons/fa";
import { MdArrowForward, MdArrowBack } from "react-icons/md";
import React from "react";
import { Link } from "@/i18n/routing";
import Image from "next/image";

function DeliveriesPage() {
  const t = useTranslations();
  const locale = useLocale();

  //get deliveries
  const { data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery<{
      data: any;
      pagination: any;
    }>({
      queryKey: ["mentor-deliveries", locale],
      queryFn: async ({ pageParam = undefined }) => {
        const response = await axiosInstance.get("/mentors/teams", {
          params: {
            page: pageParam,
          },
        });
        return response.data;
      },
      getNextPageParam: (lastPage) => {
        const { current_page, last_page } = lastPage.pagination;
        return current_page < last_page ? current_page + 1 : undefined;
      },
      initialPageParam: undefined,
    });

  const deliveries = data?.pages.flatMap((page) => page.data) ?? [];

  return (
    <>
      <div className="flex flex-col gap-y-4 mb-6">
        <h1 className="text-2xl text-foreground font-bold">
          {t("deliveries")}
        </h1>
      </div>
      {isLoading ? (
        <Spin className="flex justify-center w-full" />
      ) : deliveries && deliveries.length ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
          {deliveries.map((item) => (
            <div
              key={item.id}
              className="px-6 py-4 space-y-4 bg-card rounded-xl flex flex-col h-full"
              data-id={item.id}
            >
              <div className="flex gap-x-2 items-center">
                {item.logo ? (
                  <Image
                    src={item.logo}
                    className="w-12 h-12 rounded-full object-cover flex-shrink-0"
                    width={48}
                    height={48}
                    alt={item.name}
                  />
                ) : (
                  <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                    <FaRegUser className="text-white b" />
                  </div>
                )}
                <div className="flex flex-col gap-y-2">
                  <h2 className="font-bold text-sm m-0">{item.name}</h2>
                  <p className="text-[#667084] text-sm empty:hidden"></p>
                </div>
              </div>
              {item.track && (
                <>
                  <Divider className="border-[#DEE1E6]" />
                  <p className="font-medium m-0">{item.track?.name}</p>
                </>
              )}
              <div className="flex flex-1 items-end">
                <Link href={`deliveries/${item.id}`} className="w-full">
                  <Button variant="filled" type="default" className="w-full">
                    {t("show-details")}
                  </Button>
                </Link>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <Empty description={t("mentor.empty-assign")} />
      )}

      {hasNextPage && (
        <div className="flex justify-center mt-6">
          <Button onClick={() => fetchNextPage()} loading={isFetchingNextPage}>
            {t("load-more")}
          </Button>
        </div>
      )}
    </>
  );
}

export default DeliveriesPage;
