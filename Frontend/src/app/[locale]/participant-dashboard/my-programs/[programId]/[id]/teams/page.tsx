"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import FilterResultsModal from "@/components/FilterResultsModal";
import { Link } from "@/i18n/routing";
import { ProgramApplicationList, Team, ProgramApplication} from "@/lib/interfaces";
import { useQuery } from "@tanstack/react-query";
import { Form, Select, Spin } from "antd";
import { useForm, useWatch } from "antd/es/form/Form";
import { useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { createPortal } from "react-dom";

export default function TeamsPage() {
  const t = useTranslations();
  const [form] = useForm();
  const { id } = useParams<{ id: string }>();
  const [query, setQuery] = useState<{
    track_id?: string;
    sub_track_id?: string;
  }>();
  const [subTrackOptions, setSubTrackOptions] = useState<
    { label: string; value: number }[]
  >([]);

  
  // get my application
  const { data: myApplication, isLoading: isApplicationLoading } =
    useQuery<ProgramApplication>({
      queryKey: ["my-application", id],
      queryFn: async () => {
        const response = await axiosInstance.get(
          `/participants/program-applications/${id}`
        );
        return response.data.data;
      },
    });

  // get all teams
  const { data: teams, isLoading , refetch } = useQuery<Team[]>({
    queryKey: [
      "teams",
      id,
      query?.track_id ?? null,
      query?.sub_track_id ?? null,
    ],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/teams`, {
        params: {
          ...query,
          application_id: id,
        },
      });
      return response.data.data;
    },
  });

  // get sub_tracks
  function getSubTracks(selectedTrackId: any) {
    const selectedTrack = myApplication?.program?.tracks?.find(
      (track) => track.id === selectedTrackId
    );
    const subTrack =
      selectedTrack?.sub_tracks?.map((sub) => ({
        label: sub.name,
        value: sub.id,
      })) || [];
    setSubTrackOptions(subTrack);
  }

  // project filters
  const onSubmit = (values: any) => {
    setQuery((prev) => {
      const updated = {
        ...prev,
        track_id: values.track_id,
        sub_track_id: values.sub_track_id,
      };
      return updated;
    });
  };
  

  if (isLoading || isApplicationLoading) {
    return <Spin />;
  }

  return (
    <>
      {createPortal(
        <FilterResultsModal form={form} filterResults={onSubmit}>
          <Form.Item label={t("track")} name={"track_id"}>
            <Select
              placeholder={t("choose")}
              notFoundContent={null}
              showSearch={true}
              allowClear={true}
              options={myApplication?.program.tracks?.map((track) => ({
                label: track.name,
                value: track.id,
              }))}
              filterOption={(input, option) =>
                (option?.label ?? "")
                  .toString()
                  .toLowerCase()
                  .includes(input.toLowerCase())
              }
              onChange={(selectedTrackId) => {
                getSubTracks(selectedTrackId);
                form.setFieldsValue({ sub_track_id: undefined });
              }}
            />
          </Form.Item>

          <Form.Item label={t("sub-track")} name={"sub_track_id"}>
            <Select
              placeholder={t("choose")}
              notFoundContent={null}
              showSearch={true}
              allowClear={true}
              options={subTrackOptions}
              filterOption={(input, option) =>
                (option?.label ?? "")
                  .toString()
                  .toLowerCase()
                  .includes(input.toLowerCase())
              }
              disabled={!subTrackOptions?.length}
            />
          </Form.Item>
        </FilterResultsModal>,
        document.getElementById("filter-section") as HTMLElement
      )}

      {!teams?.length ? (
        <Empty
          description={
            query?.track_id || query?.sub_track_id
              ? t("no-team-matches-the-selected-criteria")
              : t("no-teams-registered-yet")
          }
        />
      ) : (
        <div className="w-full lg:max-w-6xl h-full">
          <div className="grid grid-cols-auto-fit-300 gap-6 justify-center lg:justify-start">
            {teams?.map((team, index) => (
              <Link
                key={index}
                href={`teams/${team.id}`}
                className="px-5 py-4 bg-card rounded-xl flex flex-col items-start gap-y-4 h-[360px] w-[320px]"
              >
                <div className="relative w-full">
                  {team.track && (
                    <span className="absolute top-4 right-4 bg-[#CEF2F1] text-[#045E5C] text-sm font-medium rounded-[40px] px-4 py-2 z-10">
                      {team.track.name}
                    </span>
                  )}
                  <div className="w-full h-[172px] bg-gray-100 rounded-lg overflow-hidden">
                    <Image
                      src={team.team_logo ?? "/project.png"}
                      alt={team.name}
                      width={300}
                      height={172}
                      className="w-full h-full object-cover"
                    />
                  </div>
                </div>
                <div className="flex flex-col gap-y-2 w-full flex-grow">
                  <h2 className="m-0 text-[#5B656A] text-base font-medium line-clamp-1">
                    {team.name}
                  </h2>
                  <p className="m-0 text-[#667085] text-sm line-clamp-2">
                    {team.idea_description}
                  </p>
                </div>
                {team?.is_completed ? (
                  <span className="text-sm bg-[#EAECF0] border border-solid border-[#D0D5DD] text-[#667085] rounded-[40px] px-4 py-2">
                    {t("team-is-completed")}
                  </span>
                ) : (
                  team.skills &&
                  team.skills?.length > 0 && (
                    <div className="flex flex-col gap-y-2 w-full">
                      <h3 className="m-0 text-primary text-xs font-medium">
                        {t("required-skills")}
                      </h3>
                      <div className="flex items-center gap-2 w-full overflow-hidden">
                        {team.skills?.slice(0, 2)?.map((skill, index) => (
                          <span
                            key={index}
                            className="text-xs border border-solid border-primary text-primary font-medium rounded-lg px-3 py-1 whitespace-nowrap"
                          >
                            {typeof skill === "string" ? skill : ""}
                          </span>
                        ))}
                        {team.skills?.length > 2 && (
                          <span className="text-[#667085] flex-shrink-0">
                            ...
                          </span>
                        )}
                      </div>
                    </div>
                  )
                )}
              </Link>
            ))}
          </div>
        </div>
      )}
    </>
  );
}
