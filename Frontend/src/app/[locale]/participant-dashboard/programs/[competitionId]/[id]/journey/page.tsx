"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { MyCompetition, Stage } from "@/lib/interfaces";
import { useQuery } from "@tanstack/react-query";
import { Spin, Tag, Tooltip } from "antd";
import {
  CalendarOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
  HourglassOutlined,
  FileTextOutlined,
  TrophyOutlined,
  RocketOutlined,
} from "@ant-design/icons";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(relativeTime);

/** Determine stage status based on current_stage_id position */
function getStageStatus(
  stage: Stage,
  allStages: Stage[],
  currentStageId: number | null | undefined
): "completed" | "active" | "upcoming" {
  if (!currentStageId) return "upcoming";
  if (stage.id === currentStageId) return "active";

  const currentIdx = allStages.findIndex((s) => s.id === currentStageId);
  const stageIdx = allStages.findIndex((s) => s.id === stage.id);
  if (currentIdx === -1 || stageIdx === -1) return "upcoming";

  return stageIdx < currentIdx ? "completed" : "upcoming";
}

/** Map stage slug prefix to an icon */
function getStageIcon(slug: string) {
  if (slug.startsWith("project")) return <FileTextOutlined />;
  if (slug.startsWith("evaluat")) return <TrophyOutlined />;
  if (slug.startsWith("register") || slug.startsWith("application"))
    return <RocketOutlined />;
  return <CalendarOutlined />;
}

const statusConfig = {
  completed: {
    color: "#08BCB8",
    bgColor: "#E1F7F6",
    borderColor: "#CEF2F1",
    icon: <CheckCircleOutlined />,
    dotBg: "#08BCB8",
  },
  active: {
    color: "var(--primary-color, #25935F)",
    bgColor: "var(--primary-color, #25935F)",
    borderColor: "var(--primary-color, #25935F)",
    icon: <ClockCircleOutlined />,
    dotBg: "var(--primary-color, #25935F)",
  },
  upcoming: {
    color: "#9CA3AF",
    bgColor: "#F3F4F6",
    borderColor: "#E5E7EB",
    icon: <HourglassOutlined />,
    dotBg: "#D1D5DB",
  },
};

export default function JourneyPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { id } = useParams<{ id: string }>();
  const isRtl = locale === "ar";

  const { data: myApplication, isLoading } = useQuery<MyCompetition>({
    queryKey: ["my-application", id],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/competition-applications/${id}`
      );
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin className="w-full flex justify-center py-12" />;
  }

  const stages = myApplication?.competition?.stages || [];
  const currentStageId = myApplication?.competition?.current_stage_id;

  if (!stages.length) {
    return <Empty description={t("no-stages-found") || "No stages found"} />;
  }

  // Calculate overall progress
  const currentIdx = stages.findIndex((s) => s.id === currentStageId);
  const progressPercent =
    currentIdx >= 0 ? Math.round(((currentIdx + 0.5) / stages.length) * 100) : 0;

  return (
    <div className="w-full space-y-6">
      {/* Header Card */}
      <div className="rounded-xl border border-[#E5E7EB] bg-gradient-to-br from-[#F9FAFB] to-white p-5 space-y-3">
        <h2 className="text-xl font-bold text-foreground">
          {t("program-journey") || "Program Journey"}
        </h2>
        <p className="text-sm text-[#6B7280]">
          {t("program-journey-description") ||
            "Track your progress through each stage of the program"}
        </p>

        {/* Progress bar */}
        <div className="space-y-1.5">
          <div className="flex items-center justify-between text-xs text-[#6B7280]">
            <span>
              {t("stage") || "Stage"} {currentIdx + 1} {t("of") || "of"}{" "}
              {stages.length}
            </span>
            <span>{progressPercent}% {t("completed") || "complete"}</span>
          </div>
          <div className="w-full h-2 bg-[#E5E7EB] rounded-full overflow-hidden">
            <div
              className="h-full rounded-full transition-all duration-700 ease-out"
              style={{
                width: `${progressPercent}%`,
                background:
                  "linear-gradient(90deg, var(--primary-color, #25935F), var(--secondary-color, #1a7a4a))",
              }}
            />
          </div>
        </div>
      </div>

      {/* Timeline */}
      <div className="relative">
        {stages.map((stage, index) => {
          const status = getStageStatus(stage, stages, currentStageId);
          const config = statusConfig[status];
          const isLast = index === stages.length - 1;

          const daysUntilStart = stage.starts_at
            ? dayjs(stage.starts_at).diff(dayjs(), "day")
            : null;
          const daysUntilEnd = stage.ends_at
            ? dayjs(stage.ends_at).diff(dayjs(), "day")
            : null;

          return (
            <div key={stage.id} className="flex gap-4 relative">
              {/* Timeline line + dot */}
              <div className="flex flex-col items-center relative" style={{ minWidth: 40 }}>
                {/* Dot */}
                <div
                  className={`relative z-10 flex items-center justify-center rounded-full transition-all duration-300 ${
                    status === "active"
                      ? "w-10 h-10 shadow-lg"
                      : "w-8 h-8"
                  }`}
                  style={{
                    background:
                      status === "active"
                        ? config.dotBg
                        : status === "completed"
                        ? config.dotBg
                        : "#F3F4F6",
                    border:
                      status === "upcoming"
                        ? "2px dashed #D1D5DB"
                        : "2px solid transparent",
                  }}
                >
                  <span
                    className="text-sm"
                    style={{
                      color:
                        status === "upcoming" ? "#9CA3AF" : "white",
                    }}
                  >
                    {status === "completed" ? (
                      <CheckCircleOutlined />
                    ) : status === "active" ? (
                      <span className="relative flex items-center justify-center">
                        <span className="animate-ping absolute inline-flex h-4 w-4 rounded-full bg-white opacity-30" />
                        {getStageIcon(stage.slug || "")}
                      </span>
                    ) : (
                      <span className="text-xs font-bold">{index + 1}</span>
                    )}
                  </span>
                </div>

                {/* Connecting line */}
                {!isLast && (
                  <div
                    className="flex-1 w-0.5 min-h-[24px]"
                    style={{
                      background:
                        status === "completed"
                          ? "#08BCB8"
                          : status === "active"
                          ? `linear-gradient(180deg, var(--primary-color, #25935F) 0%, #E5E7EB 100%)`
                          : "#E5E7EB",
                      ...(status === "upcoming"
                        ? {
                            backgroundImage:
                              "repeating-linear-gradient(180deg, #D1D5DB 0px, #D1D5DB 4px, transparent 4px, transparent 8px)",
                            backgroundColor: "transparent",
                          }
                        : {}),
                    }}
                  />
                )}
              </div>

              {/* Content Card */}
              <div
                className={`flex-1 mb-6 rounded-xl border p-4 transition-all duration-300 ${
                  status === "active"
                    ? "border-[var(--primary-color,#25935F)] bg-white shadow-md ring-1 ring-[var(--primary-color,#25935F)]/10"
                    : status === "completed"
                    ? "border-[#CEF2F1] bg-[#FAFFFE]"
                    : "border-[#E5E7EB] bg-[#FAFAFA]"
                }`}
              >
                {/* Stage Header */}
                <div className="flex items-start justify-between flex-wrap gap-2 mb-2">
                  <div className="space-y-0.5">
                    <h3
                      className={`text-base font-bold ${
                        status === "upcoming"
                          ? "text-[#9CA3AF]"
                          : "text-foreground"
                      }`}
                    >
                      {stage.title}
                    </h3>
                    {stage.description && (
                      <p
                        className={`text-sm ${
                          status === "upcoming"
                            ? "text-[#D1D5DB]"
                            : "text-[#6B7280]"
                        }`}
                      >
                        {stage.description}
                      </p>
                    )}
                  </div>

                  {/* Status Badge */}
                  <Tag
                    className="!m-0 !rounded-full !px-3 !py-0.5 !text-xs !font-medium"
                    style={{
                      background:
                        status === "active" ? config.bgColor : config.bgColor,
                      color: status === "active" ? "white" : config.color,
                      border: `1px solid ${config.borderColor}`,
                    }}
                  >
                    {config.icon}{" "}
                    {status === "completed"
                      ? t("completed") || "Completed"
                      : status === "active"
                      ? t("in-progress") || "In Progress"
                      : t("upcoming") || "Upcoming"}
                  </Tag>
                </div>

                {/* Dates */}
                {(stage.starts_at || stage.ends_at) && (
                  <div
                    className={`flex items-center gap-3 text-sm flex-wrap ${
                      status === "upcoming" ? "text-[#D1D5DB]" : "text-[#6B7280]"
                    }`}
                  >
                    <CalendarOutlined />
                    {stage.starts_at && (
                      <span>
                        {t("start") || "Start"}:{" "}
                        <strong>
                          {dayjs(stage.starts_at).format("DD MMM YYYY")}
                        </strong>
                      </span>
                    )}
                    {stage.starts_at && stage.ends_at && <span>•</span>}
                    {stage.ends_at && (
                      <span>
                        {t("end") || "End"}:{" "}
                        <strong>
                          {dayjs(stage.ends_at).format("DD MMM YYYY")}
                        </strong>
                      </span>
                    )}

                    {/* Countdown / time info */}
                    {status === "active" && daysUntilEnd !== null && (
                      <Tag
                        color={
                          daysUntilEnd <= 0
                            ? "red"
                            : daysUntilEnd <= 3
                            ? "orange"
                            : "green"
                        }
                        className="!m-0 !rounded-full !text-xs"
                      >
                        {daysUntilEnd <= 0
                          ? t("ended") || "Ended"
                          : `${daysUntilEnd} ${
                              t("days-remaining") || "days left"
                            }`}
                      </Tag>
                    )}
                    {status === "upcoming" && daysUntilStart !== null && daysUntilStart > 0 && (
                      <span className="text-xs">
                        ({t("starts-in") || "starts in"} {daysUntilStart}{" "}
                        {t("days") || "days"})
                      </span>
                    )}
                  </div>
                )}

                {/* Forms / Submissions for project stages */}
                {stage.forms?.length > 0 && (
                  <div className="mt-3 pt-3 border-t border-[#F3F4F6]">
                    <p
                      className={`text-xs font-medium mb-2 ${
                        status === "upcoming"
                          ? "text-[#D1D5DB]"
                          : "text-[#6B7280]"
                      }`}
                    >
                      <FileTextOutlined className="me-1" />
                      {t("expected-submissions") || "Expected Submissions"} (
                      {stage.forms.length})
                    </p>
                    <div className="flex flex-wrap gap-2">
                      {stage.forms.map((form: any) => {
                        const isSubmitted = stage.projects?.some(
                          (p: any) =>
                            p.isSubmitted &&
                            stage.form_id?.includes(form.id)
                        );
                        return (
                          <Tag
                            key={form.id}
                            className="!m-0 !rounded-lg !text-xs"
                            color={
                              isSubmitted
                                ? "success"
                                : status === "upcoming"
                                ? undefined
                                : "default"
                            }
                            icon={
                              isSubmitted ? (
                                <CheckCircleOutlined />
                              ) : undefined
                            }
                          >
                            {form.name}
                          </Tag>
                        );
                      })}
                    </div>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
