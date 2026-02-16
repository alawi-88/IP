"use client";

import { ConfigProvider, Segmented } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useState } from "react";
import MentorSessionsList from "@/components/mentor/MentorSessionsList";

interface QueryParams {
  status?: string | undefined;
  [key: string]: any;
}

function MentorSessionsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [query, setQuery] = useState<QueryParams>({});

  return (
    <>
      <div className="flex flex-col gap-y-4 mb-6">
        <h1 className="text-2xl text-foreground font-bold">
          {t("mentor.sessions")}
        </h1>
        <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
          <Segmented
            value={query.status}
            onChange={(value) => {
              setQuery({ status: value });
            }}
            className="!bg-card !rounded-xl !p-2 !w-fit"
            options={[
              {
                label: t("all"),
                value: "all",
              },
              {
                label: t("mentor.scheduled"),
                value: "scheduled",
              },
              {
                label: t("mentor.in_progress"),
                value: "in_progress",
              },
              {
                label: t("mentor.cancelled"),
                value: "cancelled",
              },
              {
                label: t("mentor.no_show"),
                value: "no_show",
              },
              {
                label: t("mentor.completed"),
                value: "completed",
              },
            ]}
          />
        </ConfigProvider>
      </div>
      <MentorSessionsList query={query} showPagination={true} />
    </>
  );
}

export default MentorSessionsPage;
