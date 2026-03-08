"use client";
import { Badge, Flex, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import axiosInstance from "@/axios";
import dayjs from "dayjs";
import { MdDateRange } from "react-icons/md";
import { useEffect } from "react";
import Empty from "@/components/Empty";

interface Notification {
  id: string;
  type: string;
  notifiable_type: string;
  notifiable_id: number;
  title: string;
  body: string;
  message?: string;
  read_at: string;
  created_at: string;
  updated_at: string;
}

export default function NotificationsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const queryClient = useQueryClient();
  const { data: notifications, isLoading } = useQuery<Notification[]>({
    queryKey: ["notifications"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/notifications`);
      return response.data.data;
    },
  });

  const { mutate: markAllAsRead } = useMutation({
    mutationFn: async () => {
      await axiosInstance.post(`/participants/notifications/mark-all-as-read`);
    },
  });

  function localizedDateTime(date: string) {
    if (locale === "ar") {
      return date.replace("AM", "ص").replace("PM", "م");
    }
    return date;
  }

  useEffect(() => {
    markAllAsRead();
    queryClient.invalidateQueries({ queryKey: ["participant-notifications-count"] });
  }, []);

  return (
    <section className="flex flex-col gap-y-6">
      <h1 className="text-2xl text-primary-900 font-bold">
        {t("notifications")}
      </h1>
      {isLoading ? (
        <Spin />
      ) : (
        !notifications?.length && (
          <div className="dashboard-card">
            <Empty description={t("no-notifications")} />
          </div>
        )
      )}
      {!!notifications?.length &&
        notifications?.map((notification) => (
          <div className="w-100 bg-card rounded-lg p-4" key={notification.id}>
            <Flex vertical gap={12}>
              <p className="!m-0 font-bold">
                <Badge
                  status={
                    notification?.read_at == null ? "processing" : "default"
                  }
                  text={notification?.title}
                />
              </p>
              <p className="!m-0">{notification?.body}</p>
              <p className="!m-0 flex items-center gap-2">
                <MdDateRange size={20} />{" "}
                {localizedDateTime(
                  dayjs(notification?.created_at).format("D-MM-YYYY  h:mm A")
                )}
              </p>
            </Flex>
          </div>
        ))}
    </section>
  );
}
