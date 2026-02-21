"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import AddMemberModal from "@/components/my-team/AddMemberModal";
import AddTeamModal from "@/components/my-team/AddTeamModal";
import DeleteMemberModal from "@/components/my-team/DeleteMemberModal";
import MarkTeamFullModal from "@/components/my-team/MarkTeamFullModal";
import { Link } from "@/i18n/routing";
import { ProgramApplicationList, Team, ProgramApplication} from "@/lib/interfaces";
import { useQuery } from "@tanstack/react-query";
import { Button, Spin } from "antd";
import { useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import { FaRegUser } from "react-icons/fa";

export default function YourTeamPage() {
  const t = useTranslations();
  const { id, programId } = useParams<{
    id: string;
    programId: string;
  }>();

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

  // get team form config
  const { data: formConfig, isLoading: isFormConfigLoading } = useQuery({
    queryKey: ["teamConfig", programId],
    enabled: !!programId,
    queryFn: async () => {
      try {
        const response = await axiosInstance.get("/forms/team-form-config", {
          params: {
            program_id: programId,
          },
        });

        return response?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  // get my team
  const {
    data: team,
    isLoading,
    refetch,
  } = useQuery<Team>({
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
    refetchOnWindowFocus: false,
  });

  const isTeamLeader = team?.is_participant_leader === true;
  const isTeamFormation =
    myApplication?.program?.current_stage_slug === "team-formation";

  if (isLoading || isApplicationLoading || isFormConfigLoading) {
    return <Spin />;
  }

  if (!team) {
    return (
      <Empty
        description={
          <div>
            <p>{t("you-havent-joined-a-team-yet")}</p>
            <p>{t("you-can-create-a-new-team-or-join-an-existing-one")}</p>
          </div>
        }
      >
        {isTeamFormation && (
          <div className="flex justify-center flex-wrap gap-4">
            <AddTeamModal
              applicationId={id}
              programId={programId}
              program={myApplication?.program}
              formConfig={formConfig}
              refetch={refetch}
            />
            <Link
              href={`/participant-dashboard/my-programs/${programId}/${id}/teams`}
            >
              <Button type="default" className="!px-4">
                {t("join-a-team")}
              </Button>
            </Link>
          </div>
        )}
      </Empty>
    );
  }


  return (
    <div className="bg-card rounded-xl p-6 flex flex-col gap-y-8">
      <div className="flex justify-between gap-10 items-center flex-wrap-reverse">
        <div className="flex gap-x-4 items-center md:w-3/4 xl:w-10/12">
          {team?.logo && (
            <Image
              src={team?.logo}
              alt="Team Image"
              width={56}
              height={56}
              className="rounded-lg"
            />
          )}
          <div className="flex flex-col gap-y-1">
            <div className="flex flex-wrap gap-[6px] items-center">
              <h3 className="text-[#5B656A] font-medium m-0">{team.name}</h3>
              <span className="px-4 py-2 bg-primary text-white rounded-md whitespace-nowrap">
                {team?.is_published ? t("published") : t("unpublished")}
              </span>
            </div>
          </div>
        </div>

        {isTeamLeader === true && isTeamFormation && (
          <div>
            <AddTeamModal
              applicationId={id}
              programId={programId}
              program={myApplication?.program}
              formConfig={formConfig}
              refetch={refetch}
              isEdit
              editData={team}
            />
          </div>
        )}
      </div>
      {(team?.track || team?.sub_track) && (
        <div className="p-4 bg-[#F9FAFB] flex gap-5 flex-wrap">
          <div className="flex flex-col gap-y-4 text-[#5B656A]">
            <div className="text-sm">
              <p className="m-0 font-bold">{t("project-breif")}</p>
              <p className="m-0 mt-2 text-[#758085]">
                {team?.idea_description}
              </p>
            </div>
            {team?.track && (
              <div className="text-sm">
                <p className="m-0 font-bold">{t("track")}</p>
                <p className="m-0 mt-2 text-[#758085]">{team?.track?.name}</p>
              </div>
            )}

            {team?.sub_track && (
              <div className="text-sm">
                <p className="m-0 font-bold">{t("sub-track")}</p>
                <p className="m-0 mt-2 text-[#758085]">
                  {team?.sub_track?.name}
                </p>
              </div>
            )}
          </div>
        </div>
      )}
      <div className="flex flex-col gap-y-4">
        {isTeamLeader === true && isTeamFormation && (
          <div className="flex gap-x-4 justify-end">
            <MarkTeamFullModal
              teamId={String(team.id)}
              disabled={team.is_completed || !isTeamLeader}
              applicationId={id}
              refetch={refetch}
            />
            {team?.members?.length < (formConfig?.max_team_members || 6) && (
              <AddMemberModal
                teamId={String(team.id)}
                refetch={refetch}
                disabled={!isTeamLeader}
              />
            )}
          </div>
        )}
        <div className="grid grid-cols-1  md:grid-cols-auto-fit-330 gap-4 ">
          {team.members?.map((member, index) => (
            <div
              className="bg-[#F9FAFB] rounded-lg p-4 flex flex-col items-center gap-y-2 relative"
              key={index}
            >
              {isTeamLeader === true &&
                team?.members?.length > (formConfig?.min_team_members || 2) &&
                !member.is_leader &&
                isTeamFormation && (
                  <DeleteMemberModal
                    teamId={team.id}
                    id={member.id}
                    serialNumber={member.participant.serial_number}
                    refetch={refetch}
                  />
                )}

              <div className="w-12 min-w-12 h-12 rounded-full bg-primary flex items-center justify-center">
                <FaRegUser className="text-white" />
              </div>
              <div className="text-center">
                <h4 className="text-[#5B656A] text-sm font-medium m-0">
                  {member.participant.name}
                </h4>
                <p className="text-sm text-[#667085] m-0 break-all">
                  {member.participant.experience_or_skills}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
