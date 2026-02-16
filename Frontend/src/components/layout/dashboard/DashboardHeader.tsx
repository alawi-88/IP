"use client";

import { Button, Drawer, Space } from "antd";
import { useState } from "react";
import { TiThMenuOutline } from "react-icons/ti";
import DashboardMenu from "./DashboardMenu";
import { FaRegUser } from "react-icons/fa";
import { useUserStore } from "@/store/user";
import { Link, useRouter } from "@/i18n/routing";
import { useTranslations } from "next-intl";
import LocaleSwitcher from "../LocaleSwitcher";
import { Badge } from "antd";
import { BsBell } from "react-icons/bs";
import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/axios";
import ThemeToggle from "../ThemeToggle";

type HeaderProps = {
  className?: string;
  dataLoading?: boolean;
  type: string;
};

export default function DashboardHeader({
  className,
  type,
  dataLoading,
}: HeaderProps) {
  const [open, setOpen] = useState(false);
  const user = useUserStore((state) => state.getUser());
  const t = useTranslations();
  const router = useRouter();
  const dashboardPrefix =
    type === "judge"
      ? "/judge/judge-dashboard"
      : type === "mentor"
      ? "/mentor/mentor-dashboard"
      : "/participant-dashboard";

  const { data: notificationsCount } = useQuery({
    queryKey: ["participant-notifications-count"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/notifications`);

      const notificationsCountNum =
        response.data?.data?.filter(
          (notification: any) => notification.read_at === null
        ).length || 0;

      return notificationsCountNum;
    },
  });

  const showDrawer = () => {
    setOpen(true);
  };

  const onClose = () => {
    setOpen(false);
  };

  return (
    <header className="flex justify-between w-full items-center p-3 lg:p-6 bg-card">
      <div className="flex flex-col font-medium">
        <p className="text-lg text-[#5B656A] dark:text-[#fff]">{t("welcome")}👋</p>
        <p className="text-sm text-[#667085] dark:text-[#B1B1B1] text-wrap">
          {t("welcome-to-innvocation-platform")}
        </p>
      </div>

      <div className="flex items-center gap-x-2 max-lg:ms-auto max-lg:me-4">
        <ThemeToggle />
        <LocaleSwitcher />
        <Button
          type="text"
          shape="default"
          className="notification-button min-w-auto"
          onClick={() => router.push(`${dashboardPrefix}/notifications`)}
        >
          <Badge
            offset={[-30, -5]}
            count={notificationsCount}
            overflowCount={99}
          >
            <BsBell size={25} />
          </Badge>
        </Button>
        <Link href={`/${dashboardPrefix}/profile`} className="hidden lg:block">
          {" "}
          <Button type="text" className="!p-2 !items-center !flex">
            <Space>
              <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center">
                <FaRegUser className="text-white" />
              </div>
              <p>{user?.name}</p>
            </Space>
          </Button>
        </Link>
      </div>
      <Button
        type="primary"
        className="lg:!hidden !h-auto min-w-auto"
        onClick={showDrawer}
      >
        <TiThMenuOutline />
      </Button>
      <Drawer title={t("innovation-platform")} onClose={onClose} open={open}>
        <DashboardMenu type={type} closeDrawer={onClose} />
      </Drawer>
    </header>
  );
}
