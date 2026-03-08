"use client";

import axiosInstance, { APIError } from "@/axios";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Card, message, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { IoMdClose } from "react-icons/io";
import { LoadingOutlined } from "@ant-design/icons";
import React, { useEffect, useLayoutEffect, useState } from "react";

export default function ScheduleSettingsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [messageApi, contextHolder] = message.useMessage();
  const queryClient = useQueryClient();
  const [isStartRedirect, setIsStartRedirect] = useState(false);

  const tools = [
    {
      logo: "/google-calendar.png",
      name: t("schedule-settings.tools.google-meet"),
      type: "google_meet",
    },
    {
      logo: "/zoom-logo.png",
      name: t("schedule-settings.tools.zoom"),
      type: "zoom",
    },
    // {
    //   logo: "/teams-logo.png",
    //   name: t("schedule-settings.tools.teams"),
    //   type: "teams",
    // },
  ];

  // get integrations
  const { data: integrations, isLoading } = useQuery({
    queryKey: ["mentor-integrations"],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(
          "/mentors/video-tools/integrations"
        );
        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  // post integrations
  const {
    mutate: postIntegration,
    isPending: isPostPending,
    variables: postType,
  } = useMutation({
    mutationFn: async (toolType: any) => {
      const response = await axiosInstance.post(
        "/mentors/video-tools/authorize",
        {
          lang: locale,
          redirect_uri: `${window.location.origin}/${locale}/mentor/mentor-dashboard/my-schedule/settings`,
          tool_type: toolType,
        }
      );
      return response.data;
    },

    onSuccess: (data, type) => {
      if (data?.authorization_url) {
        sessionStorage.setItem("integration_initiated", type);
        window.location.href = data.authorization_url;
        setIsStartRedirect(true);
      }
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  // set default video tool
  const {
    mutate: setDefaultMutate,
    isPending: isPending,
    variables: setDefaultType,
  } = useMutation({
    mutationFn: async (toolType: any) => {
      const response = await axiosInstance.post(
        "/mentors/video-tools/set-default",
        {
          tool_type: toolType,
        }
      );
      return response.data;
    },

    onSuccess: (response, type) => {
      messageApi.success(response.message);
      queryClient.invalidateQueries({
        queryKey: ["mentor-integrations"],
      });
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  // delete integrations
  const {
    mutate: deleteIntegration,
    isPending: isDeletePending,
    variables: deleteType,
  } = useMutation({
    mutationFn: async (toolType: any) => {
      const response = await axiosInstance.post(
        "/mentors/video-tools/disconnect",
        {
          tool_type: toolType,
        }
      );
      return response.data;
    },
    onSuccess: (response) => {
      messageApi.success(response.message);
      queryClient.invalidateQueries({
        queryKey: ["mentor-integrations"],
      });
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  useLayoutEffect(() => {
    const navigation = performance.getEntriesByType("navigation")[0] as
      | PerformanceNavigationTiming
      | undefined;
    if (navigation?.type === "reload") {
      sessionStorage.removeItem("integration_initiated");
    }

    const url = new URL(window.location.href);
    const success = url.searchParams.get("success");
    const fail = url.searchParams.get("fail");
    const initiated = sessionStorage.getItem("integration_initiated");
    if (initiated) {
      if (success) {
        messageApi.success(t("schedule-settings.integration-success"));
      }
      if (fail) {
        messageApi.success(t("schedule-settings.integration-error"));
      }
    }
    sessionStorage.removeItem("integration_initiated");
    if (url.search) {
      const cleanUrl = window.location.origin + url.pathname;
      window.history.replaceState({}, "", cleanUrl);
    }
  }, []);

  return (
    <>
      {contextHolder}
      {isLoading ? (
        <Spin className="w-full flex justify-center" />
      ) : (
        <Card
          title={
            <div className="card_title space-y-1">
              <p className="font-bold">{t("schedule-settings.title")}</p>
              <p className="font-medium text-sm text-[#808898]">
                {t("schedule-settings.sub-title")}
              </p>
            </div>
          }
        >
          <p className="font-medium mb-6">{t("schedule-settings.hint")}</p>
          <div className="tools-list space-y-4">
            {tools.map((tool, index) => (
              <div
                className="tool-item flex max-sm:flex-wrap items-center justify-between p-4 gap-x-6 gap-y-4 rounded-lg border border-[#CCCFD6]"
                key={index}
              >
                <div className="tool-name w-full flex items-center gap-x-3 sm:gap-x-4">
                  {tool.logo && (
                    <Image
                      src={tool.logo}
                      alt={tool.name}
                      className="w-10 h-10 object-contain flex-shrink-0"
                      width={40}
                      height={40}
                    />
                  )}
                  <div className="space-y-2">
                    <p className="flex gap-1 font-medium">
                      {tool.name}
                      {integrations?.find(
                        (integration: any) =>
                          integration.tool_type === tool.type
                      )?.is_default && (
                        <span className="text-xs py-1 px-2 rounded-lg font-medium border bg-[#F3F4F6]">
                          {t("default")}
                        </span>
                      )}
                    </p>
                    {integrations?.find(
                      (integration: any) => integration.tool_type === tool.type
                    )?.account_email && (
                      <p className="text-[#808898] text-sm font-medium break-all">
                        {
                          integrations.find(
                            (integration: any) =>
                              integration.tool_type === tool.type
                          ).account_email
                        }
                      </p>
                    )}
                  </div>
                </div>
                {integrations?.find(
                  (integration: any) => integration.tool_type === tool.type
                )?.id ? (
                  <div className="flex max-sm:flex-wrap gap-x-4 gap-y-2">
                    {integrations?.length > 1 &&
                      !integrations?.find(
                        (integration: any) =>
                          integration.tool_type === tool.type
                      )?.is_default && (
                        <Button
                          type="primary"
                          onClick={() => setDefaultMutate(tool.type)}
                          loading={isPending && setDefaultType === tool.type}
                        >
                          {t("mentor.set-default")}
                        </Button>
                      )}
                    <Button
                      type="default"
                      onClick={() => deleteIntegration(tool.type)}
                      loading={isDeletePending && deleteType === tool.type}
                    >
                      {t("delete")}
                    </Button>
                  </div>
                ) : (
                  <Button
                    type="primary"
                    onClick={() => postIntegration(tool.type)}
                    loading={
                      (isPostPending || isStartRedirect) &&
                      postType === tool.type
                    }
                  >
                    {t("add")}
                  </Button>
                )}
              </div>
            ))}
          </div>
        </Card>
      )}
    </>
  );
}
