"use client";
import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useQuery } from "@tanstack/react-query";
import { Spin } from "antd";
import { useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import React, { useEffect, useState } from "react";
import { FaRegUser } from "react-icons/fa";

export default function WinnersPage() {
  const { id } = useParams<{ id: string }>();
  const t = useTranslations();
  const [groupedWinners, setGroupedWinners] = useState<TrackGroup[]>([]);

  interface Winner {
    id: number;
    rank: number;
    name: string;
    subtitle: string;
    image: string;
    track?: {
      id: number;
      name: string;
      winners_count: number;
    };
    [key: string]: any;
  }

  interface TrackGroup {
    track: string;
    winners: Winner[];
  }

  // get winners
  const { data: winners, isLoading } = useQuery({
    queryKey: ["winners", id],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(`/participants/winners`, {
          params: {
            application_id: id,
          },
        });
        return response.data.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  useEffect(() => {
    if (!winners || !winners.length) return;

    const trackMap: Record<string, TrackGroup> = {};

    winners.forEach((winner: Winner) => {
      const trackName = winner.track?.name || t("other-track");

      if (!trackMap[trackName]) {
        trackMap[trackName] = {
          track: trackName,
          winners: [],
        };
      }

      trackMap[trackName].winners.push(winner);
    });

    const groupedAndSorted = Object.values(trackMap)
      .map((group) => ({
        track: group.track,
        winners: group.winners
          .filter((winner) => winner.is_visible !== false)
          .sort((a, b) => a.rank - b.rank),
      }))
      .filter((group) => group.winners.length > 0);

    setGroupedWinners(groupedAndSorted);
  }, [winners]);

  if (isLoading) {
    return <Spin />;
  }

  return (
    <>
      <div className="dashboard-card">
        {groupedWinners.length ? (
          groupedWinners?.map((group, index) => (
            <div key={index} className="winners-wrapper">
              {groupedWinners.length === 1 &&
              group.track === t("other-track") ? null : (
                <h2 className="text-base font-bold mb-6">{group.track}</h2>
              )}
              <div className="grid sm:[grid-auto-rows:1fr] xl:grid-cols-3 md:grid-cols-1 gap-y-4 gap-x-6">
                {group.winners.map((winner, index) => (
                  <div
                    key={winner.id}
                    className={`winner-card flex items-center justify-between gap-x-4 gap-y-3 p-3 rounded-2xl bg-[#F2F3F6] ${
                      index + 1 <= 3 ? "active" : ""
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <div className="winner-img-wrap sm:w-16 sm:h-16 w-12 h-12 flex flex-shrink-0 items-center justify-center">
                        {winner.image ? (
                          <Image
                            className="winner-img w-full h-full object-cover rounded-full"
                            src={winner.image}
                            width={64}
                            height={64}
                            alt={winner.name}
                          />
                        ) : (
                          <div className="winner-img w-full h-full rounded-full bg-primary flex items-center justify-center">
                            <FaRegUser className="text-white" />
                          </div>
                        )}
                      </div>

                      <div className="winner-info">
                        <h3 className="text-base font-medium">{winner.name}</h3>
                        {winner.subtitle && (
                          <p className="text-xs font-medium text-[#626262] mt-2">
                            {winner.subtitle}
                          </p>
                        )}
                      </div>
                    </div>
                    <div className="winner-rank font-bold text-[40px] text-[#9E96EE]">
                      {winner.rank}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))
        ) : (
          <Empty description={null} customImage="/winners.svg">
            <p className="font-semibold text-lg mb-0">
              {t("no-winners-found")}
            </p>
            <p className="font-medium text-sm text-[#626262] mt-3">
              {t("no-winners-found-hint")}
            </p>
          </Empty>
        )}
      </div>
    </>
  );
}
