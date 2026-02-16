"use client";

import React from "react";
import axiosInstance from "@/axios";
import { Link } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import { Button, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import Empty from "@/components/Empty";
import dayjs from "dayjs";
import { FiArrowLeft, FiArrowRight, FiCalendar } from "react-icons/fi";

export default function InquiriesPage() {
  const t = useTranslations();
  const locale = useLocale();

  // get inquiries list
  const { data: inquiries, isLoading } = useQuery({
    queryKey: ["inquiries"],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get("/contact-us");

        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  return (
    <>
      <h1 className="text-2xl text-primary-900 font-bold mb-6">
        {t("inquiries")}
      </h1>

      {isLoading ? (
        <Spin className="flex justify-center w-full" />
      ) : inquiries?.length ? (
        <div className="inquiries-list flex flex-col gap-y-4">
          {inquiries.map((inquiry: any) => (
            <div
              className="inquiry-item bg-card flex lg:items-center justify-between gap-x-8 gap-y-4 flex-col lg:flex-row py-4 px-6 rounded-xl"
              key={inquiry.id}
            >
              <div className="item-info">
                <p className="max-lg:flex max-lg:justify-between flex-wrap  gap-y-1 items-center mb-4">
                  <span className="font-bold me-2">{inquiry.title}</span>
                  <span
                    className={`inline-block mt-1 text-sm py-1 px-2 rounded-lg font-medium border  ${
                      inquiry.status === "resolved"
                        ? "bg-[#E1F7F6] text-[#08BCB8] border-[#CEF2F1]"
                        : inquiry.status === "pending"
                        ? "bg-[#FFF0E6] text-[#FF822C] border-[#FFE6D5]"
                        : "bg-[#6D62E5] text-[#F0EFFF] border-[#E2E0FA]"
                    }`}
                  >
                    {t(`inquiry-status.${inquiry.status}`)}
                  </span>
                </p>
                <p className="flex items-center text-sm font-medium gap-2 text-[#626262]">
                  <FiCalendar size={20} />
                  {dayjs(inquiry.created_at).format("D-MM-YYYY")}
                </p>
              </div>
              <Link
                href={`/participant-dashboard/help/inquiries/${inquiry.id}`}
              >
                <Button
                  icon={locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />}
                  iconPosition="end"
                >
                  {t("view-details")}
                </Button>
              </Link>
            </div>
          ))}
        </div>
      ) : (
        <div className="dashboard-card">
          <Empty description={t("no-inquiries-found")} />
        </div>
      )}
    </>
  );
}
