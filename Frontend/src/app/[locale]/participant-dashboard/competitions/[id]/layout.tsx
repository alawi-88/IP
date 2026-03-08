"use client";

import axiosInstance from "@/axios";
import { useRouter } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import { Breadcrumb } from "antd";
import { BreadcrumbItemType } from "antd/es/breadcrumb/Breadcrumb";
import { useTranslations } from "next-intl";
import { useParams } from "next/navigation";

interface Competition {
  id: number;
  title: string;
  about: string;
  terms_and_conditions: string;
  banner: string;
  registration_closed_date: string;
  created_at: string;
  updated_at: string;
}

export default function CompetitionsLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { id } = useParams();
  const router = useRouter();
  const t = useTranslations();

  const { data: competition } = useQuery<Competition>({
    queryKey: ["competition", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/competitions/${id}`);
      return response.data.data;
    },
  });

  const breadcrumbItems: BreadcrumbItemType[] = [
    {
      title: t("competitions"),
      onClick: () => router.push("/participant-dashboard"),
      className: "cursor-pointer hover:text-primary",
    },
    {
      title: competition?.title ? competition.title : "-",
    },
  ];

  return (
    <div className="flex flex-col gap-y-6">
      <Breadcrumb items={breadcrumbItems} />
      {children}
    </div>
  );
}
