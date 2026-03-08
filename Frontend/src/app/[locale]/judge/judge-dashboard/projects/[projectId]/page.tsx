"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useRenderFieldType } from "@/hooks/useRenderField";
import { DynamicForm, Field, MyCompetition } from "@/lib/interfaces";
import { useUserStore } from "@/store/user";
import { useQuery } from "@tanstack/react-query";
import {
  Button,
  Card,
  Divider,
  Form,
  Input,
  Radio,
  Select,
  Spin,
  Steps,
  Upload,
} from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { FiArrowRight } from "react-icons/fi";
import { FiArrowLeft } from "react-icons/fi";
import DisclaimerModal from "@/components/judge-evaluate/DisclaimerModal";
import dayjs from "dayjs";
import { FiCalendar, FiFlag } from "react-icons/fi";
import { FaRegUser } from "react-icons/fa";

export default function ProjectDetails() {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  const { projectId } = useParams<{ projectId: string }>();
  const [currentStage, setCurrentStage] = useState<
    MyCompetition["competition"]["stages"][number] | null
  >(null);
  const [disclaimerOpen, setDisclaimerOpen] = useState(false);
  const [shouldShowDisclaimer, setShouldShowDisclaimer] = useState(false);

  // handle field value display format
  function handleFieldVal(field: Field) {
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
              field.value?.split("attachments/")[1]) ||
              field.value}
          </a>
        );

      default:
        return field.value.trim();
    }
  }

  // render fields
  function renderFields(fields: Field[]) {
    return fields
      .filter(
        (field) =>
          field.value && !["section_header", "paragraph"].includes(field.type)
      )
      .map((field) => (
        <div key={field.slug} className="flex flex-col gap-y-2">
          <h3 className="text-base font-bold m-0">{field.label}</h3>
          <p className="text-sm font-medium m-0 text-[#626262]">
            {handleFieldVal(field)}
          </p>
        </div>
      ));
  }

  //get project
  const { data: project, isLoading: isProjectLoading } =
    useQuery<MyCompetition>({
      queryKey: ["judge-project", projectId],
      queryFn: async () => {
        const response = await axiosInstance.get(
          `/judges/projects/${projectId}`
        );
        return response.data.data;
      },
    });

  //get evaluations
  const { data: evaluations, isLoading: isEvaluationsLoading } = useQuery({
    queryKey: ["judge-project-evaluations", projectId],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/judges/evaluations?project_id=${projectId}`
      );
      return response.data.data;
    },
  });

  // get evaluation dynamic form
  const { data: dynamicForm, isLoading: isDynamicFormLoading } =
    useQuery<DynamicForm>({
      queryKey: ["evaluationForm", currentStage?.forms],
      enabled: !!currentStage,
      queryFn: async () => {
        try {
          const response = await axiosInstance.get("/forms/evaluations/form", {
            params: {
              form_id: currentStage?.forms[0]?.id,
            },
          });

          return response?.data;
        } catch (error) {
          console.log(error);
          return null;
        }
      },
    });

  // on disclaimer agree
  function onDisclaimerAgree() {
    router.push(
      `/judge/judge-dashboard/projects/${projectId}/${currentStage?.forms[0]?.id}/evaluate`
    );
  }

  // check current stage is evaluation 
  useEffect(() => {
    if (!project?.id) return;
    const currentStage = project.competition.current_stage;
    if (currentStage?.slug?.startsWith("evaluation")) {
      setCurrentStage(currentStage);
    }
  }, [project]);

  // set should show disclaimer
  useEffect(() => {
    if (project && dynamicForm) {
      const shouldShow =
        currentStage?.evaluation?.isDisclaimerAccepted === null &&
        !!dynamicForm?.evaluation_config?.evaluation_agreement_text;

      setShouldShowDisclaimer(shouldShow);
    }
  }, [project, dynamicForm]);

  if (isProjectLoading || isEvaluationsLoading || isDynamicFormLoading) {
    return <Spin className="w-full flex justify-center" />;
  }

  if (!project?.id) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <>
      <div className="flex flex-col gap-y-6">
        <div className="flex justify-between items-center gap-x-6 gap-y-4 max-xl:flex-wrap">
          <h1 className="font-bold text-2xl text-foreground">
            {project?.metadata?.project_name}
          </h1>
          <div className="flex gap-4 max-lg:flex-wrap">
            {evaluations?.length ? (
              <>
                {currentStage &&
                  (!currentStage?.evaluation?.isSubmitted ||
                    (currentStage?.evaluation?.isSubmitted &&
                      !currentStage?.evaluation?.project_id?.includes(
                        Number(projectId)
                      ))) && (
                    <Button
                      type="primary"
                      icon={
                        locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />
                      }
                      iconPosition="end"
                      onClick={() =>
                        shouldShowDisclaimer
                          ? setDisclaimerOpen(true)
                          : onDisclaimerAgree()
                      }
                      disabled={
                        currentStage?.evaluation?.isDisclaimerAccepted === false
                      }
                    >
                      {t("rate-current-stage-project")}
                    </Button>
                  )}

                <Link
                  href={`/judge/judge-dashboard/projects/${projectId}/${currentStage?.forms[0]?.id}/evaluation`}
                >
                  <Button
                    className="!bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] !border-[color-mix(in_srgb,var(--primary-color)_10%,transparent)]"
                    icon={locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />}
                    iconPosition="end"
                  >
                    {t("view-evaluation")}
                  </Button>
                </Link>
              </>
            ) : (
              currentStage && (
                <Button
                  type="primary"
                  icon={locale === "ar" ? <FiArrowLeft /> : <FiArrowRight />}
                  iconPosition="end"
                  onClick={() =>
                    shouldShowDisclaimer
                      ? setDisclaimerOpen(true)
                      : onDisclaimerAgree()
                  }
                  disabled={
                    currentStage?.evaluation?.isDisclaimerAccepted === false
                  }
                >
                  {t("rate-project")}
                </Button>
              )
            )}
          </div>
        </div>
        <div className="dashboard-card">
          <div className="grid xl:gap-x-12 gap-y-8 xl:grid-cols-12">
            <div className="xl:col-span-8">
              <div className="project-info-wrapper flex flex-col gap-y-6 ">
                <h2 className="text-2xl text-primary-900 font-bold mb-2">
                  {t("project-details")}
                </h2>

                <div className="flex flex-col gap-y-3 text-[#98A2B3] text-sm font-medium">
                  <div className="flex items-center gap-2 text-[#98A1B2]  text-sm font-medium">
                    <FiCalendar className="w-4 h-4" />
                    <span>{t("project-submission-date")}:</span>
                    <span className="text-foreground">
                      {dayjs(project.created_at).format("DD/MM/YYYY")}
                    </span>
                  </div>
                  {project.competition.stages.find(
                    (stage) => stage.id === project.competition.current_stage_id
                  ) && (
                    <div className="flex items-center gap-2 text-[#98A1B2]  text-sm font-medium">
                      <FiFlag className="w-4 h-4" />
                      <span>{t("stage")}:</span>
                      <span className="text-foreground">
                        {
                          project.competition.stages.find(
                            (stage) =>
                              stage.id === project.competition.current_stage_id
                          )?.title
                        }
                      </span>
                    </div>
                  )}
                </div>

                {project?.competition?.tracks?.length > 0 && (
                  <>
                    <div className="flex flex-col gap-y-2">
                      <h3 className="text-base font-bold m-0">{t("track")}</h3>
                      <p className="text-sm font-medium m-0 text-[#626262]">
                        {
                          project.competition.tracks.find(
                            (track) => track.is_selected
                          )?.name
                        }
                      </p>
                    </div>
                    <div className="flex flex-col gap-y-2">
                      <h3 className="text-base font-bold m-0">
                        {t("sub-track")}
                      </h3>
                      <p className="text-sm font-medium m-0 text-[#626262]">
                        {project.competition.tracks
                          .find((track) => track.is_selected)
                          ?.sub_tracks?.find((subTrack) => subTrack.is_selected)
                          ?.name || "-"}
                      </p>
                    </div>
                  </>
                )}

                {project.form.fields.length > 0 &&
                  renderFields(project.form.fields)}
                {project.form.steps?.length > 0 &&
                  renderFields(
                    project.form.steps.flatMap((step) => step.fields)
                  )}
              </div>
            </div>
            {project?.team && project?.team?.members?.length > 1 && (
              <div className="xl:col-span-4">
                <div className="bg-[#F2F4F7] rounded-lg p-4 flex flex-col gap-y-4 min-h-[228px] max-h-fit overflow-y-auto">
                  <h3 className="font-bold text-sm m-0">{t("participants")}</h3>
                  <Divider className="!m-0" />
                  {project.team.members.map((member) => (
                    <div key={member.id} className="flex gap-x-2 items-center">
                      <div className="w-12 min-w-12 h-12 rounded-full bg-primary flex items-center justify-center">
                        <FaRegUser className="text-white b" />
                      </div>
                      <div className="flex flex-col gap-y-2">
                        <h3 className="font-bold text-sm m-0">
                          {member.participant.name}
                        </h3>
                        <p className="text-[#626262] text-sm m-0 break-all">
                          {member.participant.current_role}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
      {shouldShowDisclaimer && (
        <DisclaimerModal
          isOpen={disclaimerOpen}
          projectId={projectId}
          stage={currentStage}
          disclaimerText={
            dynamicForm?.evaluation_config?.evaluation_agreement_text
          }
          disclaimerRequired={
            dynamicForm?.evaluation_config?.require_agreement_acceptance
          }
          onAgree={onDisclaimerAgree}
          onClose={() => {
            setDisclaimerOpen(false);
          }}
        />
      )}
    </>
  );
}
