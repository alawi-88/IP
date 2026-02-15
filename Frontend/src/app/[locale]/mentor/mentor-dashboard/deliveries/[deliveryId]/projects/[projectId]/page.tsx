"use client";

import axiosInstance from "@/axios";
import Comments, { CommentItem } from "@/components/Comments";
import Empty from "@/components/Empty";
import { Link } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import { Breadcrumb, Spin } from "antd";
import dayjs from "dayjs";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import React from "react";

function DeliveryProductPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { deliveryId, projectId } = useParams();

  // get delivery details
  const { data: project, isLoading } = useQuery({
    queryKey: ["mentor-delivery-project", locale, deliveryId, projectId],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(
          `/mentors/teams/projects/${projectId}`
        );
        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  // handle field value display format
  function handleFieldVal(field: any) {
    if (!field?.value) return undefined;
    switch (field.type) {
      case "date":
        return dayjs(field.value, "YYYY-MM-DD").format("DD/MM/YYYY");

      case "time":
        const [hour, minute] = field.value.split(":").map(Number);
        const date = new Date();
        date.setHours(hour);
        date.setMinutes(minute);
        return new Intl.DateTimeFormat(locale, {
          hour: "numeric",
          minute: "2-digit",
          hour12: true,
        }).format(date);

      case "file":
      case "url":
        return (
          <a
            className="!text-primary break-all"
            href={field.value}
            target="_blank"
            rel="noopener noreferrer"
          >
            {(typeof field.value === "string" &&
              field.value?.split("files/")[1]) ||
              field.value}
          </a>
        );

      default:
        return field.value.trim();
    }
  }

  // render fields
  function renderFields(fields: any) {
    return fields
      .filter(
        (field: any) => !["section_header", "paragraph"].includes(field.type)
      )
      .map((field: any) => (
        <div key={field.key} className="flex flex-col gap-y-2 col-span-full">
          <h2 className="text-base font-bold m-0">{field.label}</h2>
          <p className="text-sm font-medium m-0 text-[#626262]">
            {handleFieldVal(field) || "-"}
          </p>
        </div>
      ));
  }

  return (
    <div className="flex flex-col gap-y-4">
      {isLoading ? (
        <Spin className="flex justify-center w-full" />
      ) : project ? (
        <>
          <Breadcrumb
            items={[
              {
                title: (
                  <Link href={`/mentor/mentor-dashboard/deliveries`}>
                    {t("deliveries")}
                  </Link>
                ),
              },
              {
                title: (
                  <Link
                    href={`/mentor/mentor-dashboard/deliveries/${
                      project?.team?.id || project?.participant?.id
                    }`}
                  >
                    {project?.team?.name || project?.participant?.name}
                  </Link>
                ),
              },
              {
                title: project.project_name,
              },
            ]}
          />
          <div className="space-y-4">
            <h1 className="text-2xl font-bold">{t("project-details")}</h1>
            <div className="dashboard-card">
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="flex flex-col gap-y-2">
                  <h2 className="text-base font-bold m-0">
                    {t("project-name")}
                  </h2>
                  <p className="text-sm font-medium m-0 text-[#626262]">
                    {project.project_name}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <h2 className="text-base font-bold m-0">{t("track")}</h2>
                  <p className="text-sm font-medium m-0 text-[#626262]">
                    {project?.track?.name || "-"}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <h2 className="text-base font-bold m-0">{t("sub-track")}</h2>
                  <p className="text-sm font-medium m-0 text-[#626262]">
                    {project?.sub_track?.name || "-"}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <h2 className="text-base font-bold m-0">
                    {t("project-submission-date")}
                  </h2>
                  <p className="text-sm font-medium m-0 text-[#626262]">
                    {dayjs(project.created_at).format("DD/MM/YYYY")}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <h2 className="text-base font-bold m-0">{t("status")}</h2>
                  <p className="text-sm font-medium m-0 text-[#626262]">
                    {t(project.status)}
                  </p>
                </div>
                {project.form_submissions?.length > 0 &&
                  renderFields(project.form_submissions)}
              </div>
            </div>
          </div>
          {project?.comments?.length > 0 && (
            <div className="space-y-4">
              <h3 className="text-2xl font-bold">{t("comments")}</h3>
              <div className="dashboard-card">
                <div className="comments-list space-y-8">
                  {project?.comments?.map((comment: any) => (
                    <CommentItem key={comment.id} comment={comment} t={t} />
                  ))}
                </div>
              </div>
            </div>
          )}
        </>
      ) : (
        <Empty description={t("no-result-found")} />
      )}
    </div>
  );
}

export default DeliveryProductPage;
