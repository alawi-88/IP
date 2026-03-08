"use client";

import axiosInstance from "@/axios";
import DashboardHeader from "@/components/layout/dashboard/DashboardHeader";
import DashboardSidebar from "@/components/layout/dashboard/DashboardSidebar";
import { useAutoLogin } from "@/hooks/useAutoLogin";
import { usePathname, useRouter } from "@/i18n/routing";
import { ProgramApplication } from "@/lib/interfaces";
import { useQuery } from "@tanstack/react-query";
import { Breadcrumb } from "antd";
import { BreadcrumbItemType } from "antd/es/breadcrumb/Breadcrumb";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { isLoading, isError } = useAutoLogin("judge");
  const t = useTranslations();
  const pathname = usePathname();
  const router = useRouter();
  const currentSegment = pathname.split("/").pop();

  const { projectId } = useParams<{
    projectId: string;
  }>();

  const { data: project } = useQuery<ProgramApplication>({
    queryKey: ["judge-project", projectId],
    queryFn: async () => {
      const response = await axiosInstance.get(`/judges/projects/${projectId}`);
      return response.data.data;
    },
    enabled: !!projectId,
  });

  const breadcrumbItems: BreadcrumbItemType[] = [];

  if (project?.metadata?.project_name) {
    breadcrumbItems.push(
      {
        title: t("projects"),
        onClick: () => router.push("/judge/judge-dashboard"),
        className: "cursor-pointer hover:text-primary",
      },
      {
        title: project?.metadata?.project_name,
        onClick: () =>
          router.push(`/judge/judge-dashboard/projects/${projectId}`),
        className: "cursor-pointer hover:text-primary",
      },
      ...(currentSegment === "evaluation"
        ? [
            {
              title: t("evaluate-project"),
            },
          ]
        : [])
    );
  }

  return (
    <>
      <div className="h-screen w-screen overflow-hidden flex">
        <div className="hidden lg:block z-50">
          <DashboardSidebar type="judge" />
        </div>

        <div className="flex flex-col overflow-hidden w-full">
          <DashboardHeader type="judge" dataLoading={isLoading}/>
          <main className="flex-1 h-full overflow-x-hidden overflow-y-auto p-4 pt-6 lg:p-10">
            <Breadcrumb items={breadcrumbItems} className="!mb-6" />
            {children}
          </main>
        </div>
      </div>
    </>
  );
}
