"use client";

import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import Empty from "@/components/Empty";
import { useTranslations } from "next-intl";
import { Form, Select, Spin } from "antd";
import { FiCalendar, FiFlag } from "react-icons/fi";
import Image from "next/image";
import dayjs from "dayjs";
import { Link } from "@/i18n/routing";
import { useState, useMemo, useEffect } from "react";
import { MdFilterListAlt } from "react-icons/md";
import { GoTrophy } from "react-icons/go";
import { Program, ProgramApplication } from "@/lib/interfaces";
import FilterResultsModal from "@/components/FilterResultsModal";

export default function ParticipantDashboard() {
  const t = useTranslations();
  const [form] = Form.useForm();
  const [filters, setFilters] = useState<{
    programId?: number;
    stageId?: number;
    evaluated?: boolean;
  }>({});

  // get projects
  const { data: projects, isLoading: isProjectsLoading } = useQuery<
    ProgramApplication[]
  >({
    queryKey: ["projects"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/judges/projects`);
      return response.data.data;
    },
  });

  // get programs
  const { data: programs, isLoading: isProgramsLoading } = useQuery<Program[]>({
    queryKey: ["programs"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/programs`);
      return response.data.data;
    },
  });

  // get evaluation  stages
  const { data: stages, isLoading: isStagesLoading } = useQuery({
    queryKey: ["evaluation-stages"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/forms/evaluations/stages`);
      return response.data;
    },
  });

  // filtered projects list
  const filteredProjects = useMemo(() => {
    if (!projects) return [];

    return projects.filter((project) => {
      const matchesProgram =
        !filters.programId ||
        project.program.id === filters.programId;

      const matchesEvaluation =
        filters.evaluated === undefined ||
        project.is_evaluated === filters.evaluated;

      const matchesStage =
        !filters.stageId ||
        project.program.stages.some(
          (stage) => stage.id === filters.stageId
        );
      // project.program.current_stage_id === filters.stageId;

      return matchesProgram && matchesEvaluation && matchesStage;
    });
  }, [projects, filters]);

  // filter results function
  const filterResults = (values: any) => {
    const newFilters = {
      programId: values.programId
        ? Number(values.programId)
        : undefined,
      stageId: values.stageId ? Number(values.stageId) : undefined,
      evaluated:
        values.evaluationStatus === "evaluated"
          ? true
          : values.evaluationStatus === "not-evaluated"
          ? false
          : undefined,
    };
    setFilters(newFilters);
  };

  // calc selected program projects
  const selectedProgramProjects = useMemo(() => {
    if (!projects || !filters.programId) return [];
    return projects.filter(
      (project) => project.program.id === filters.programId
    );
  }, [projects, filters.programId]);
  const evaluatedCount = selectedProgramProjects.filter(
    (p) => p.is_evaluated
  ).length;
  const totalCount = selectedProgramProjects.length;

  return (
    <>
      <div className="flex justify-between items-center mb-5">
        <h1 className="text-2xl text-foreground font-bold py-2">
          {t("projects")}
        </h1>

        <FilterResultsModal form={form} filterResults={filterResults}>
          <Form.Item
            label={t("programs")}
            name="programId"
            initialValue={""}
          >
            <Select
              showSearch={true}
              filterOption={(input, option) => {
                const label = option?.children?.toString()?.toLowerCase() || "";
                return label.includes(input.toLowerCase());
              }}
              notFoundContent={null}
              allowClear={true}
              placeholder={t("choose")}
            >
              <Select.Option value="">{t("all")}</Select.Option>
              {programs?.map((prog) => (
                <Select.Option key={prog.id} value={prog.id}>
                  {prog.title}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item
            label={t("evolution-stage")}
            name="stageId"
            initialValue={""}
          >
            <Select
              showSearch
              allowClear
              placeholder={t("choose")}
              filterOption={(input, option) => {
                const label = option?.children?.toString()?.toLowerCase() || "";
                return label.includes(input.toLowerCase());
              }}
            >
              <Select.Option value="">{t("all")}</Select.Option>
              {stages?.map((stage: any) => (
                <Select.Option key={stage.id} value={stage.id}>
                  {stage.title}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item
            label={t("evaluation-status")}
            name="evaluationStatus"
            initialValue={""}
          >
            <Select allowClear placeholder={t("choose")}>
              <Select.Option value="">{t("all")}</Select.Option>
              <Select.Option value="evaluated">{t("evaluated")}</Select.Option>
              <Select.Option value="not-evaluated">
                {t("not-evaluated")}
              </Select.Option>
            </Select>
          </Form.Item>
        </FilterResultsModal>
      </div>

      {isProjectsLoading ? (
        <Spin className="w-full flex justify-center" />
      ) : !filteredProjects?.length ? (
        <Empty
          description={
            filters.programId &&
            filters.evaluated === undefined &&
            filters.stageId === undefined &&
            filteredProjects.length === 0
              ? t("no-projects-found-for-program")
              : t("no-project-matches-criteria")
          }
        />
      ) : (
        <>
          {filters.programId && totalCount > 0 && (
            <div className="flex flex-wrap gap-x-20 gap-y-2 justify-between r flex-col sm:flex-row sm:items-center mb-6">
              <div className="text-base text-[#4B5563] font-bold">
                {t("project-count")}: <span>{totalCount}</span>
              </div>
              <div className="text-base text-[#4B5563] font-bold">
                {t("judge-progress", {
                  evaluated: evaluatedCount,
                  total: totalCount,
                })}
              </div>
            </div>
          )}
          <div className="grid grid-cols-1 xl:grid-cols-3 sm:grid-cols-2 gap-6">
            {filteredProjects.map((project) => (
              <Link
                href={`/judge/judge-dashboard/projects/${project.id}`}
                key={project.id}
                className="rounded-xl bg-card shadow-md overflow-hidden relative"
              >
                <Image
                  src={"/project.png"}
                  alt="Card Image"
                  className="w-full h-[200px] object-cover"
                  width={300}
                  height={200}
                />
                <div className="absolute top-4 right-4">
                  <div className="bg-[#CEF2F1] text-[#045E5C] px-4 py-2 rounded-full">
                    {
                      project.program.tracks.find(
                        (track) => track.is_selected
                      )?.name
                    }
                  </div>
                </div>

                <div className="p-6">
                  <h2 className="text-sm font-bold mb-2">
                    {project.metadata?.project_name}
                  </h2>

                  <p className="text-[#98A1B2] text-sm font-normal">
                    {project?.team
                      ? project.team.name
                      : project?.participant_name
                      ? project.participant_name
                      : null}
                  </p>

                  <div className="my-4 space-y-3">
                    <div
                      className="flex items-center gap-2 text-[#98A1B2] text-sm font-medium"
                      title={project.program.title}
                    >
                      <GoTrophy className="w-4 h-4 shrink-0" />
                      <span>{t("program")}:</span>
                      <span className="text-foreground truncate inline-block align-top">
                        {project.program.title}
                      </span>
                    </div>

                    <div className="flex items-center gap-2 text-[#98A1B2]  text-sm font-medium">
                      <FiCalendar className="w-4 h-4 shrink-0" />
                      <span>{t("project-submission-date")}:</span>
                      <span className="text-foreground">
                        {dayjs(project.created_at).format("DD/MM/YYYY")}
                      </span>
                    </div>
                    {project.program.stages.find(
                      (stage) =>
                        stage.id === project.program.current_stage_id
                    ) && (
                      <div className="flex items-center gap-2 text-[#98A1B2]  text-sm font-medium">
                        <FiFlag className="w-4 h-4 shrink-0" />
                        <span>{t("stage")}:</span>
                        <span className="text-foreground">
                          {
                            project.program.stages.find(
                              (stage) =>
                                stage.id ===
                                project.program.current_stage_id
                            )?.title
                          }
                        </span>
                      </div>
                    )}
                  </div>

                  <div className="flex items-center justify-between gap-2 text-[#98A1B2] text-sm font-medium">
                    <div className="flex items-center gap-2">
                      <span>{t("evaluation-status")}:</span>
                    </div>
                    <span
                      className={`${
                        project.is_evaluated === false
                          ? "bg-[#FFF0E6] border-[#FFE6D5] text-[#FF822C]"
                          : "bg-[#E1F7F6] border-[#CEF2F1] text-[#08BCB8]"
                      }   px-3 p-2 rounded-full`}
                    >
                      {project.is_evaluated === false
                        ? t("not-evaluated")
                        : t("evaluated")}
                    </span>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        </>
      )}
    </>
  );
}
