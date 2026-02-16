"use client";
import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useQuery } from "@tanstack/react-query";
import { Card, Spin } from "antd";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";

interface PrivacyPolicy {
  id: number;
  slug: string;
  title: string;
  content: string;
  is_published: boolean;
  created_at: string;
  updated_at: string;
}

export default function Terms() {
  const t = useTranslations();

  const { data: privacyPolicy, isLoading } = useQuery<PrivacyPolicy>({
    queryKey: ["privacy-policy"],
    queryFn: async () => {
      const response = await axiosInstance.get("/pages/privacy-policy");
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!privacyPolicy || !privacyPolicy.is_published) {
    return <Empty description={t("no-privacy-policy-found")} />;
  }

  const content = privacyPolicy?.content?.replaceAll(
    "<a",
    "<a target='_blank' rel='noreferrer noopener'"
  );

  return (
    <div className="max-w-full overflow-x-hidden ">
      <div className="mx-auto text-wrap">
        <Card
          className="text-center !py-8 !bg-primary !rounded-none !mb-8"
          bordered={false}
          hoverable={false}
          loading={isLoading}
        >
          <h1 className="text-4xl text-white font-bold">
            {privacyPolicy.title}
          </h1>
        </Card>
        <Card
          loading={isLoading}
          className="!rounded-lg lg:!mx-20 !py-8 !px-4"
        >
          <p className="text-base text-secondary mb-10">
            {t("last-updated")}: {dayjs(privacyPolicy.updated_at).format("DD/MM/YYYY")}
          </p>
          <div
            className="[&_ul]:list-disc [&_ul]:m-6"
            dangerouslySetInnerHTML={{
              __html: content || "",
            }}
          />
        </Card>
      </div>
    </div>
  );
}
