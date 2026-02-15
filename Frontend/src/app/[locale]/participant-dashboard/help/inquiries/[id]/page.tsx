"use client";

import React from "react";
import axiosInstance from "@/axios";
import { Link } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import { Button, Divider, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import Empty from "@/components/Empty";
import dayjs from "dayjs";
import { FiArrowLeft, FiArrowRight, FiCalendar } from "react-icons/fi";
import { useParams } from "next/navigation";

export default function InquiryPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { id } = useParams();

  // get inquiry details
  const { data: inquiry, isLoading } = useQuery({
    queryKey: ["inquiries", id],
    enabled: !!id,
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(`/contact-us/${id}`);

        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  function localizedDateTime(date: string) {
    if (locale === "ar") {
      return date.replace("AM", "ص").replace("PM", "م");
    }
    return date;
  }

  return (
    <>
      <div className="flex justify-between items-center gap-x-6 gap-y-4 flex-wrap mb-6">
        <h1 className="text-2xl text-primary-900 font-bold">
          {t("inquiries")}
        </h1>
        <Link
          className="flex items-center gap-2 transition-all hover:text-primary font-medium"
          href={`/participant-dashboard/help/inquiries`}
        >
          {t("back-to-inquiries")}

          {locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />}
        </Link>
      </div>
      {isLoading ? (
        <Spin className="flex justify-center w-full" />
      ) : (
        <div className="dashboard-card !py-8">
          {inquiry ? (
            <>
              <div className="inquiry-content space-y-6">
                <div className="flex gap-x-8 justify-between items-baseline flex-wrap lg:flex-nowrap">
                  <h2 className="lg:text-xl text-lg font-bold">
                    {inquiry.title}
                  </h2>
                  <div
                    className={`inline-block flex-shrink-0 mt-1 text-sm py-1 px-2 rounded-lg font-medium border  ${
                      inquiry.status === "resolved"
                        ? "bg-[#E1F7F6] text-[#08BCB8] border-[#CEF2F1]"
                        : inquiry.status === "pending"
                        ? "bg-[#FFF0E6] text-[#FF822C] border-[#FFE6D5]"
                        : "bg-[#6D62E5] text-[#F0EFFF] border-[#E2E0FA]"
                    }`}
                  >
                    {t(`inquiry-status.${inquiry.status}`)}
                  </div>
                </div>
                <p className="text-[#626262] font-medium ">{inquiry.message}</p>
                {inquiry.attachments.length > 0 && (
                  <div className="attachments-wrapper">
                    <h3 className="font-medium mb-2">{t("attachments")}</h3>
                    <ul className="flex flex-col gap-y-2">
                      {inquiry.attachments.map((url: string, index: string) => (
                        <li key={index}>
                          <a
                            className="text-primary break-all"
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            download
                          >
                            {url?.split("attachments/")[1] || url}
                          </a>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                <p className="flex items-center text-sm font-bold gap-2 text-[#626262]">
                  <FiCalendar size={20} />
                  {localizedDateTime(
                    dayjs(inquiry.created_at).format("D-MM-YYYY  h:mm A")
                  )}
                </p>
              </div>

              {inquiry.status === "resolved" && (
                <>
                  <Divider
                    style={{ borderColor: "#DEE1E6" }}
                    className="!m-0"
                  />

                  <div className="inquiry-content space-y-6">
                    <div className="flex gap-x-2 items-center">
                      <div className="flex items-center justify-center font-bold w-[32px] h-[32px] rounded-full bg-[#E1F7F6] text-[#033F3D] text-xs">
                        AD
                      </div>
                      <span className="font-bold">{t("admin")}</span>
                    </div>
                    <div
                      className="font-medium"
                      dangerouslySetInnerHTML={{ __html: inquiry.reply }}
                    />
                    <p className="flex items-center text-sm font-bold gap-2 text-[#626262]">
                      <FiCalendar size={20} />
                      {localizedDateTime(
                        dayjs(inquiry.replied_at).format("D-MM-YYYY  h:mm A")
                      )}
                    </p>
                  </div>
                </>
              )}
            </>
          ) : (
            <Empty description={t("no-result-found")} />
          )}
        </div>
      )}
    </>
  );
}
