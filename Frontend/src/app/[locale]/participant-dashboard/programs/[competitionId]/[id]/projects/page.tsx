"use client";
import { Button, ConfigProvider, Progress, Spin, Tabs, Tag, Tooltip } from "antd";
import axiosInstance from "@/axios";
import { useLocale, useTranslations } from "next-intl";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import {
  CalendarOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
  RightOutlined,
  LeftOutlined,
  FileTextOutlined,
  InfoCircleOutlined,
} from "@ant-design/icons";
import { MdArrowForward, MdArrowBack } from "react-icons/md";
import { Link } from "@/i18n/routing";
import Empty from "@/components/Empty";
import { useEffect, useMemo, useState } from "react";
import { Competition, MyCompetition, Stage, Team } from "@/lib/interfaces";
import dayjs from "dayjs";

/** Helper: determine stage status relative to current_stage_id */
function getStageStatus(
  stage: Stage,
  allStages: Stage[],
  currentStageId: number | null | undefined
): "completed" | "active" | "upcoming" | "unknown" {
  if (!currentStageId) return "unknown";
  if (stage.id === currentStageId) return "active";

  const currentIdx = allStages.findIndex((s) => s.id === currentStageId);
  const stageIdx = allStages.findIndex((s) => s.id === stage.id);
  if (currentIdx === -1 || stageIdx === -1) return "unknown";

  return stageIdx < currentIdx ? "completed" : "upcoming";
}

export default function ProjectPage() {
  const t = useTranslations();
  const { id, competitionId } = useParams<{
    id: string;
    competitionId: string;
  }>();
  const router = useRouter();
  const locale = useLocale();
  const searchParams = useSearchParams();
  const [selectedStage, setSelectedStage] = useState<Stage>();
  const isRtl = locale === "ar";
  const ArrowIcon = isRtl ? LeftOutlined : RightOutlined;

  // get submitted projects
  const { data: projects, isLoading: isProjectsLoading } = useQuery({
    queryKey: ["projects", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/projects`, {
        params: { application_id: id },
      });
      return response.data.data;
    },
  });

  // get my team
  const { data: team } = useQuery<Team>({
    queryKey: ["my-team", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/my-team`, {
        params: { application_id: id },
      });
      return response.data.data;
    },
    retry: 1,
  });

  // get my application
  const { data: myApplication, isLoading: isApplicationLoading } =
    useQuery<MyCompetition>({
      queryKey: ["my-application", id],
      queryFn: async () => {
        const response = await axiosInstance.get(
          `/participants/competition-applications/${id}`
        );
        return response.data.data;
      },
    });

  const allStages = myApplication?.competition?.stages || [];
  const currentStageId = myApplication?.competition?.current_stage_id;
  const currentStage = myApplication?.competition?.current_stage;

  const projectStages = useMemo(
    () => allStages.filter((stage: Stage) => stage.slug?.startsWith("project")),
    [allStages]
  );

  const filteredProjects = selectedStage
    ? projects?.filter((project: any) =>
        selectedStage?.forms?.some((form: any) => form.id === project.form_id)
      )
    : [];

  const projectStageForms = selectedStage?.forms?.filter(
    (form: any) =>
      !projects?.some((project: any) => project.form_id === form.id)
  );

  const canSubmitProject = team ? team?.is_participant_leader : true;

  // Compute submission progress for selected stage
  const submissionProgress = useMemo(() => {
    if (!selectedStage?.forms?.length) return { total: 0, submitted: 0 };
    const total = selectedStage.forms.length;
    const submitted = selectedStage.forms.filter((form: any) =>
      projects?.some(
        (project: any) =>
          project.form_id === form.id && project.submit_type !== "draft"
      )
    ).length;
    return { total, submitted };
  }, [selectedStage, projects]);

  // Upcoming stages (all stages after current)
  const upcomingStages = useMemo(() => {
    if (!currentStageId || !allStages.length) return [];
    const currentIdx = allStages.findIndex((s) => s.id === currentStageId);
    if (currentIdx === -1) return [];
    return allStages.slice(currentIdx + 1);
  }, [allStages, currentStageId]);

  // get first project stage
  useEffect(() => {
    if (!myApplication?.competition?.stages?.length) return;

    // Default to the current active project stage, or the first project stage
    const activeProjectStage = myApplication.competition.stages.find(
      (stage) =>
        stage.slug?.startsWith("project") &&
        stage.id === myApplication.competition.current_stage_id
    );
    const firstProjectStage = myApplication.competition.stages.find((stage) =>
      stage.slug?.startsWith("project")
    );

    setSelectedStage(activeProjectStage || firstProjectStage);
  }, [myApplication]);

  if (isProjectsLoading || isApplicationLoading) {
    return <Spin />;
  }

  if (!myApplication) {
    return <Empty description={t("no-result-found")} />;
  }

  const selectedStageStatus = selectedStage
    ? getStageStatus(selectedStage, allStages, currentStageId)
    : "unknown";

  return (
    <div className="w-full space-y-4">
      {/* ─── Stage Overview Card ──────────────────────────────── */}
      {currentStage && (
        <div className="rounded-xl border border-[#E5E7EB] bg-gradient-to-br from-[#F9FAFB] to-white p-5 space-y-4">
          {/* Current Stage Header */}
          <div className="flex items-start justify-between flex-wrap gap-3">
            <div className="space-y-1">
              <p className="text-xs font-medium text-[#6B7280] uppercase tracking-wide">
                {t("current-stage") || "Current Stage"}
              </p>
              <h3 className="text-lg font-bold text-foreground">
                {currentStage.title}
              </h3>
              {currentStage.description && (
                <p className="text-sm text-[#6B7280] max-w-xl">
                  {currentStage.description}
                </p>
              )}
            </div>
            <div className="flex items-center gap-2 flex-wrap">
              {currentStage.starts_at && currentStage.ends_at && (
                <Tag
                  icon={<CalendarOutlined />}
                  className="!m-0 !rounded-full !border-[#E5E7EB] !bg-white !px-3 !py-1 !text-sm"
                >
                  {dayjs(currentStage.starts_at).format("DD MMM")} -{" "}
                  {dayjs(currentStage.ends_at).format("DD MMM YYYY")}
                </Tag>
              )}
              {currentStage.ends_at && (
                <Tag
                  icon={<ClockCircleOutlined />}
                  color={
                    dayjs(currentStage.ends_at).isBefore(dayjs())
                      ? "red"
                      : dayjs(currentStage.ends_at).diff(dayjs(), "day") <= 3
                      ? "orange"
                      : "green"
                  }
                  className="!m-0 !rounded-full !px-3 !py-1 !text-sm"
                >
                  {dayjs(currentStage.ends_at).isBefore(dayjs())
                    ? t("ended") || "Ended"
                    : `${dayjs(currentStage.ends_at).diff(dayjs(), "day")} ${
                        t("days-remaining") || "days remaining"
                      }`}
                </Tag>
              )}
            </div>
          </div>

          {/* Expected Submissions */}
          {currentStage.slug?.startsWith("project") &&
            currentStage.forms?.length > 0 && (
              <div className="rounded-lg border border-[#E5E7EB] bg-white p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <FileTextOutlined className="text-primary" />
                    <span className="text-sm font-semibold text-foreground">
                      {t("expected-submissions") || "Expected Submissions"}
                    </span>
                  </div>
                  <span className="text-xs font-medium text-[#6B7280]">
                    {submissionProgress.submitted}/{submissionProgress.total}{" "}
                    {t("completed") || "completed"}
                  </span>
                </div>
                <Progress
                  percent={
                    submissionProgress.total > 0
                      ? Math.round(
                          (submissionProgress.submitted /
                            submissionProgress.total) *
                            100
                        )
                      : 0
                  }
                  strokeColor="var(--primary-color, #25935F)"
                  size="small"
                  showInfo={false}
                />
                <div className="space-y-2">
                  {currentStage.forms.map((form: any) => {
                    const submitted = projects?.find(
                      (p: any) =>
                        p.form_id === form.id && p.submit_type !== "draft"
                    );
                    const draft = projects?.find(
                      (p: any) =>
                        p.form_id === form.id && p.submit_type === "draft"
                    );
                    return (
                      <div
                        key={form.id}
                        className="flex items-center justify-between text-sm"
                      >
                        <div className="flex items-center gap-2">
                          {submitted ? (
                            <CheckCircleOutlined className="text-[#08BCB8]" />
                          ) : draft ? (
                            <ClockCircleOutlined className="text-[#FF822C]" />
                          ) : (
                            <InfoCircleOutlined className="text-[#9CA3AF]" />
                          )}
                          <span
                            className={
                              submitted
                                ? "text-[#6B7280] line-through"
                                : "text-foreground"
                            }
                          >
                            {form.name}
                          </span>
                        </div>
                        <span className="text-xs">
                          {submitted ? (
                            <Tag color="success" className="!m-0 !text-xs">
                              {t("submitted") || "Submitted"}
                            </Tag>
                          ) : draft ? (
                            <Tag color="warning" className="!m-0 !text-xs">
                              {t("draft.key") || "Draft"}
                            </Tag>
                          ) : (
                            <Tag className="!m-0 !text-xs">
                              {t("not-submitted") || "Not Submitted"}
                            </Tag>
                          )}
                        </span>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

          {/* Upcoming Stages */}
          {upcomingStages.length > 0 && (
            <div className="space-y-2">
              <p className="text-xs font-medium text-[#6B7280] uppercase tracking-wide">
                {t("upcoming-stages") || "Upcoming Stages"}
              </p>
              <div className="flex gap-3 flex-wrap">
                {upcomingStages.slice(0, 4).map((stage, idx) => (
                  <div
                    key={stage.id}
                    className="flex items-center gap-2 rounded-lg border border-dashed border-[#D1D5DB] bg-white px-3 py-2 text-sm"
                  >
                    <span className="flex items-center justify-center w-5 h-5 rounded-full bg-[#F3F4F6] text-xs font-bold text-[#6B7280]">
                      {idx + 1}
                    </span>
                    <span className="text-foreground font-medium">
                      {stage.title}
                    </span>
                    {stage.starts_at && (
                      <span className="text-[#9CA3AF] text-xs">
                        {dayjs(stage.starts_at).format("DD MMM")}
                      </span>
                    )}
                  </div>
                ))}
                {upcomingStages.length > 4 && (
                  <span className="text-xs text-[#9CA3AF] self-center">
                    +{upcomingStages.length - 4} {t("more") || "more"}
                  </span>
                )}
              </div>
            </div>
          )}
        </div>
      )}

      {/* ─── Stage Tabs ──────────────────────────────────────── */}
      <ConfigProvider direction={isRtl ? "rtl" : "ltr"}>
        <Tabs
          className="[&_.ant-tabs-nav::before]:!border-[#CCCFD6]"
          activeKey={String(selectedStage?.id)}
          items={projectStages?.map((stage) => {
            const status = getStageStatus(stage, allStages, currentStageId);
            return {
              key: String(stage.id),
              label: (
                <span className="flex items-center gap-1.5">
                  {status === "completed" && (
                    <CheckCircleOutlined className="text-[#08BCB8] text-xs" />
                  )}
                  {status === "active" && (
                    <span className="relative flex h-2 w-2">
                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75" />
                      <span className="relative inline-flex rounded-full h-2 w-2 bg-primary" />
                    </span>
                  )}
                  {stage.title}
                </span>
              ),
            };
          })}
          onChange={(key) => {
            const stage = allStages.find((s) => String(s.id) === key);
            setSelectedStage(stage);
          }}
        />
      </ConfigProvider>

      {/* Stage date range & status badge */}
      {selectedStage?.starts_at && selectedStage?.ends_at && (
        <div className="flex items-center gap-3 pb-2 flex-wrap">
          <p className="font-medium text-lg">
            {t("from")}{" "}
            {new Date(selectedStage.starts_at).toLocaleDateString("en-GB")}{" "}
            {t("to")}{" "}
            {new Date(selectedStage.ends_at).toLocaleDateString("en-GB")}
          </p>
          {selectedStageStatus === "completed" && (
            <Tag color="default" className="!m-0 !rounded-full">
              <CheckCircleOutlined /> {t("completed") || "Completed"}
            </Tag>
          )}
          {selectedStageStatus === "active" && (
            <Tag color="processing" className="!m-0 !rounded-full">
              <ClockCircleOutlined /> {t("in-progress") || "In Progress"}
            </Tag>
          )}
          {selectedStageStatus === "upcoming" && (
            <Tag className="!m-0 !rounded-full">
              {t("upcoming") || "Upcoming"}
            </Tag>
          )}
        </div>
      )}

      {/* Description for selected stage */}
      {selectedStage?.description && (
        <p className="text-sm text-[#6B7280] pb-2">{selectedStage.description}</p>
      )}

      {!filteredProjects?.length && !selectedStage?.forms?.length && (
        <Empty description={t("no-result-found")} />
      )}

      {/* ─── Submitted Projects ─────────────────────────────── */}
      {filteredProjects?.map((project: any) => (
        <div
          key={project.id}
          className="flex flex-col gap-y-8 lg:flex-row items-center justify-between p-4 bg-card rounded-lg shadow-sm"
        >
          <div className="flex flex-col gap-y-3">
            <h2 className="text-lg text-foreground font-bold">
              <span className="me-2">
                {project.submit_type === "draft" &&
                !project?.metadata?.project_name
                  ? t("draft.untitled")
                  : project?.metadata?.project_name}
              </span>
              {project.has_comment && (
                <span className="inline-block text-sm py-1 px-2 rounded-lg font-medium border bg-[#E1F7F6] text-[#08BCB8] border-[#CEF2F1]">
                  {t("new-comment")}
                </span>
              )}
            </h2>
            <div className="flex items-center gap-x-2">
              <span>
                <CalendarOutlined />
              </span>
              <span className="flex items-center gap-x-2">
                {t("delivery-date")}:{" "}
                {new Date(project.created_at).toLocaleDateString("en-GB")}
              </span>
            </div>
          </div>

          <div className="flex items-center max-sm:flex-wrap gap-4">
            {project.submit_type === "draft" ? (
              <div className="px-6 py-2 rounded-full border bg-[#F6F7F9] text-[#626262] border-[#DEE1E6] font-medium text-sm text-center">
                {t("draft.key")}
              </div>
            ) : (
              <div
                className={`px-6 py-2 rounded-full border ${
                  project.status === "pending"
                    ? "bg-[#FFF0E6] border-[#FFE6D5] text-[#FF822C]"
                    : project.status === "qualified"
                    ? "bg-[#E1F7F6] border-[#CEF2F1] text-[#08BCB8]"
                    : project.status === "winner"
                    ? "bg-[#F0EFFF] border-[#E2E0FA] text-[#6D62E5]"
                    : "bg-[#FDE8EC] border-[#FCD8DF] text-[#F13C61]"
                }`}
              >
                <p className="font-medium text-sm text-center">
                  {project.status === "pending"
                    ? t("pending")
                    : project.status === "qualified"
                    ? t("approved")
                    : project.status === "winner"
                    ? t("winner")
                    : t("rejected")}
                </p>
              </div>
            )}
            <Link
              href={
                selectedStage?.id ===
                  myApplication?.competition?.current_stage_id &&
                project.submit_type === "draft"
                  ? `projects/submit?projectId=${project?.id}&formId=${project?.form_id}`
                  : `projects/${project.id}?formId=${project?.form_id}`
              }
            >
              <button className="flex gap-x-3 items-center rounded-lg border text-foreground border-[#E1E1E1] px-4 py-2">
                {t("show-details")}
                {isRtl ? (
                  <MdArrowBack size={20} />
                ) : (
                  <MdArrowForward size={20} />
                )}
              </button>
            </Link>
          </div>
        </div>
      ))}

      {/* ─── Available Forms to Submit ──────────────────────── */}
      {projectStageForms?.map((form: any) => (
        <div
          key={form.id}
          className="flex flex-col gap-y-8 lg:flex-row items-center justify-between p-4 bg-card rounded-lg shadow-sm"
        >
          <div className="flex flex-col gap-y-3">
            <h2 className="text-lg text-foreground font-bold">
              {form.name || t("submit-current-stage-project")}
            </h2>
          </div>

          <div className="flex items-center max-sm:flex-wrap gap-4">
            <Link
              href={`${
                myApplication?.competition?.current_stage_id !==
                  selectedStage?.id ||
                !myApplication?.competition?.current_stage?.ends_at ||
                !canSubmitProject
                  ? ``
                  : `projects/submit?formId=${form.id}`
              }`}
            >
              <Button
                type="primary"
                className="!px-4"
                disabled={
                  myApplication?.competition?.current_stage_id !==
                    selectedStage?.id ||
                  !myApplication?.competition?.current_stage?.ends_at ||
                  !canSubmitProject
                }
              >
                {t("submit-project")}
              </Button>
            </Link>
          </div>
        </div>
      ))}
    </div>
  );
}
