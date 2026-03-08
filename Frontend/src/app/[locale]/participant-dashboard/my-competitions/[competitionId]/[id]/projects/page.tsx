"use client";
import { Button, ConfigProvider, Spin, Tabs, Tooltip } from "antd";
import axiosInstance from "@/axios";
import { useLocale, useTranslations } from "next-intl";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { CalendarOutlined } from "@ant-design/icons";
import { MdArrowForward } from "react-icons/md";
import { MdArrowBack } from "react-icons/md";
import { Link } from "@/i18n/routing";
import Empty from "@/components/Empty";
import { useEffect, useState } from "react";
import { Competition, MyCompetition, Stage, Team } from "@/lib/interfaces";

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

  // get submitted projects
  const { data: projects, isLoading: isProjectsLoading } = useQuery({
    queryKey: ["projects", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/projects`, {
        params: {
          application_id: id,
        },
      });
      return response.data.data;
    },
  });

  // get my team
  const { data: team, isLoading: isTeamLoading } = useQuery<Team>({
    queryKey: ["my-team", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/my-team`, {
        params: {
          application_id: id,
        },
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

  const filteredProjects = selectedStage
    ? projects?.filter((project: any) =>
        selectedStage?.forms?.some((form: any) => form.id === project.form_id)
      )
    : [];
  const projectStageForms = selectedStage?.forms?.filter(
    (form: any) =>
      !projects?.some((project: any) => project.form_id === form.id)
  );
  const projectStages = myApplication?.competition?.stages?.filter(
    (stage: Stage) => stage.slug?.startsWith("project")
  );
  const canSubmitProject = team ? team?.is_participant_leader : true;

  // get first project stage
  useEffect(() => {
    if (!myApplication?.competition?.stages?.length) return;

    const firstProjectStage = myApplication.competition.stages.find((stage) =>
      stage.slug?.startsWith("project")
    );

    if (!firstProjectStage) return;

    setSelectedStage(firstProjectStage);
  }, [myApplication]);

  if (isProjectsLoading || isApplicationLoading) {
    return <Spin />;
  }

  if (!myApplication) {
    return <Empty description={t("no-result-found")} />;
  }

  console.log(myApplication.competition.stages);

  return (
    <div className="w-full space-y-4">
      <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
        <Tabs
          className="[&_.ant-tabs-nav::before]:!border-[#CCCFD6]"
          activeKey={String(selectedStage?.id)}
          items={projectStages?.map((stage) => ({
            key: String(stage.id),
            label: stage.title,
          }))}
          onChange={(key) => {
            const stage = myApplication?.competition.stages.find(
              (s) => String(s.id) === key
            );
            setSelectedStage(stage);
          }}
        />
      </ConfigProvider>

      {projectStages?.map(
        (stage) =>
          stage.starts_at &&
          stage.ends_at &&
          stage.id === selectedStage?.id && (
            <div key={stage.id} className="pb-4">
              <p className="font-medium text-lg">
                {t("from")}{" "}
                {new Date(stage.starts_at).toLocaleDateString("en-GB")}{" "}
                {t("to")} {new Date(stage.ends_at).toLocaleDateString("en-GB")}
              </p>
            </div>
          )
      )}

      {!filteredProjects?.length && !selectedStage?.forms?.length && (
        <Empty description={t("no-result-found")} />
      )}

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
                <CalendarOutlined className="" />
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
                className={`px-6 py-2  rounded-full border ${
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
                {locale === "en" ? (
                  <MdArrowForward size={20} />
                ) : (
                  <MdArrowBack size={20} />
                )}
              </button>
            </Link>
          </div>
        </div>
      ))}
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
