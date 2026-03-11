"use client";

import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/routing";
import { Spin, Button, Empty, Tag } from "antd";
import { HiOutlineLightBulb } from "react-icons/hi";
import { FiPlus } from "react-icons/fi";

export default function StartupBuilderPage() {
  const t = useTranslations();
  const locale = useLocale();

  const { data, isLoading } = useQuery({
    queryKey: ["ventures", locale],
    queryFn: async () => {
      const res = await axiosInstance.get("/participants/ventures");
      return res.data;
    },
    refetchOnWindowFocus: false,
  });

  const ventures = data?.data || [];

  const statusColors: Record<string, string> = {
    completed: "green",
    generating: "blue",
    draft: "default",
    failed: "red",
    partially_completed: "orange",
  };

  return (
    <section className="flex flex-col gap-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-800">
          {t("startup-builder")}
        </h1>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20">
          <Spin size="large" />
        </div>
      ) : ventures.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 bg-white rounded-xl shadow-sm">
          <Empty
            description={
              <span className="text-gray-500">No ventures yet</span>
            }
          />
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {ventures.map((venture: any) => (
            <Link
              key={venture.id}
              href={`/participant-dashboard/startup-builder/${venture.id}`}
              className="block"
            >
              <div className="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden border border-gray-100">
                <div className="bg-gradient-to-r from-[#25935F] to-[#1a7a4e] p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                      <h3 className="text-white text-lg font-semibold line-clamp-2">
                        {venture.title}
                      </h3>
                      <Tag
                        color={statusColors[venture.status] || "default"}
                        className="mt-2 !text-xs"
                      >
                        {venture.status?.replace("_", " ")}
                      </Tag>
                    </div>
                    {venture.viability_score && (
                      <div className="flex-shrink-0 flex items-center justify-center w-14 h-14 rounded-full bg-white/20 backdrop-blur-sm">
                        <span className="text-white font-bold text-lg">
                          {venture.viability_score}%
                        </span>
                      </div>
                    )}
                  </div>
                </div>
                <div className="p-4">
                  <p className="text-sm text-gray-500 line-clamp-2">
                    {venture.idea_prompt}
                  </p>
                  <div className="flex items-center gap-2 mt-3 text-xs text-gray-400">
                    <span>{venture.industry}</span>
                    {venture.tabs_count && (
                      <>
                        <span>·</span>
                        <span>{venture.tabs_count} tabs</span>
                      </>
                    )}
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </section>
  );
}
