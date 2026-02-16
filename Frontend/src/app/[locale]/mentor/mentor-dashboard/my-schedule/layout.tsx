"use client";
import { usePathname, useRouter } from "@/i18n/routing";
import { ConfigProvider, Segmented } from "antd";
import { useLocale, useTranslations } from "next-intl";
import React, { useEffect } from "react";

export default function MyScheduleLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const pathname = usePathname();
  const currentSegment = pathname.split("/").pop();

  useEffect(() => {
    if (currentSegment === "my-schedule") {
      router.push(`${pathname}/times`);
    }
  }, [currentSegment]);

  return (
    <>
      <div className="flex flex-col gap-y-4 mb-6">
        <h1 className="text-2xl text-foreground font-bold">
          {t("my-schedule")}
        </h1>
        {/* <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
          <Segmented
            value={currentSegment}
            onChange={(value) => {
              router.push(`/mentor/mentor-dashboard/my-schedule/${value}`);
            }}
            className="!bg-card !rounded-xl !p-2 !w-fit"
            options={[
              {
                value: "times",
                label: t("my-times"),
              },
              {
                value: "settings",
                label: t("schedule-settings.title"),
              },
            ]}
          />
        </ConfigProvider> */}
      </div>
      {children}
    </>
  );
}
