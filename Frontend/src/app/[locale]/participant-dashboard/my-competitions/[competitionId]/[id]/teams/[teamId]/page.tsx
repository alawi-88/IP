"use client";

import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import { Divider, Spin } from "antd";
import { useParams } from "next/navigation";
import { AiOutlineMail } from "react-icons/ai";
import { FaRegUser } from "react-icons/fa";
import { useTranslations } from "next-intl";
import { Team } from "@/lib/interfaces";

export default function EventDetailsPage() {
  const t = useTranslations();
  const { id, teamId } = useParams<{ id: string; teamId: string }>();

  const { data: team, isLoading } = useQuery<Team>({
    queryKey: ["teams", teamId],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/teams/${teamId}`,
        {
          params: { application_id: id },
        }
      );
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  return (
    <div className="flex flex-col gap-y-6">
      <div className="bg-card text-[#5B656A] rounded-xl p-6 flex gap-16 flex-wrap justify-between">
        <div className="flex flex-col gap-y-6 md:w-1/3 xl:w-1/2">
          <div className="flex flex-col">
            <h3 className="font-medium m-0 text-[#5B656A]"></h3>

            <div className="text-sm text-foreground flex flex-col gap-y-2">
              <h3 className="font-bold m-0">{t("track")}</h3>
              <p className="mb-6 text-secondary">{team?.track?.name}</p>
            </div>

            {team?.sub_track && (
              <div className="text-sm text-foreground flex flex-col gap-y-2">
                <h3 className="font-bold m-0">{t("sub-track")}</h3>
                <p className="mb-2 text-secondary">{team?.sub_track?.name}</p>
              </div>
            )}
          </div>
          {team?.idea_description && (
            <div className="flex flex-col gap-y-3">
              <h3 className="font-bold m-0 text-foreground">
                {t("project-breif")}
              </h3>
              <p className="text-secondary text-sm m-0 text-wrap break-all">
                {team.idea_description}
              </p>
            </div>
          )}

          {team?.skills && team.skills.length > 0 && (
            <div className="flex flex-col gap-y-3 text-foreground">
              <h3 className="m-0 font-bold ">{t("required-skills")}</h3>
              <div className="flex gap-2 flex-wrap">
                {team?.skills?.map((skill, index) => (
                  <span
                    key={index}
                    className="text-xs border border-solid border-[#EAECF0] text-foreground font-medium rounded-lg px-3 py-[10px]"
                  >
                    {skill}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
        <div className="bg-[#F2F4F7] rounded-lg p-4 flex flex-col gap-y-4 w-[292px] min-h-[228px] overflow-y-auto">
          <h3 className="font-bold text-sm m-0">
            {t("team-members")}
          </h3>
          <Divider className="!m-0" />
          {team?.members?.map((member, index) => (
            <div key={index} className="flex gap-x-2 items-center">
              <div className="w-12 min-w-12 h-12 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                <FaRegUser className="text-white b" />
              </div>
              <div className="flex flex-col gap-y-2">
                <h3 className="font-bold text-sm m-0 text-foreground">
                  {member.participant.name}
                </h3>
                <p className="text-xs text-[#626262] font-medium m-0 break-all">
                  {member.participant.experience_or_skills}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
      {team?.contact_email && (
        <div className="bg-card text-[#5B656A] rounded-xl p-6 flex gap-x-4">
          <span className="text-primary bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] p-3 rounded-lg text-2xl pb-1">
            <AiOutlineMail />
          </span>

          <div>
            <h3 className="font-bold m-0 text-foreground">
              {t("email-to-contact")}
            </h3>
            <p className="text-secondary text-sm m-0 mt-1 break-all">
              {team?.contact_email}
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
