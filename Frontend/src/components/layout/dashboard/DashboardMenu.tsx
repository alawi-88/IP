"use client";

import { Button, Menu, MenuProps } from "antd";
import { GoSignOut, GoTrophy } from "react-icons/go";
import { FaRegUser, FaRegListAlt } from "react-icons/fa";
import { Link, usePathname } from "@/i18n/routing";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import { useMemo, useState } from "react";
import { useUserStore } from "@/store/user";
import { useLocale, useTranslations } from "next-intl";
import { BiMessageDetail } from "react-icons/bi";
import { FaList } from "react-icons/fa6";
import { MdHelpOutline, MdHistory } from "react-icons/md";
import { GoHome } from "react-icons/go";
import { TbCalendarTime } from "react-icons/tb";
import { LuListChecks } from "react-icons/lu";
import { useRouter, useSearchParams } from "next/navigation";
import { programsTypes } from "@/lib/constants";
import { logoutAndRedirect } from "@/lib/utils/logout";

type MenuItem = Required<MenuProps>["items"][number];

export default function DashboardMenu({
  type,
  closeDrawer,
  collapsed = false,
}: {
  type: string;
  closeDrawer?: () => void;
  collapsed?: boolean;
}) {
  const path = usePathname();
  const router = useRouter();
  const t = useTranslations();
  const locale = useLocale();
  const searchParams = useSearchParams();
  const [logoutModal, setLogoutModal] = useState(false);
  const logoutUser = useUserStore((state) => state.logout);
  const dashboardPrefix =
    type === "judge"
      ? "/judge/judge-dashboard"
      : type === "mentor"
      ? "/mentor/mentor-dashboard"
      : "/participant-dashboard";
  const currentProgramType = useMemo(() => {
    if (type != "participant") return null;
    const param = searchParams.get("program_type");
    return param && programsTypes.includes(param) ? param : programsTypes[0];
  }, [type, searchParams]);

  const logout = () => {
    // logoutUser();
    // router.replace(
    //   type === "participant" ? `/${locale}/login` : `/${locale}/${type}/login`
    // );
    logoutAndRedirect();
  };

  const programsTypesItems = programsTypes.map((type, index) => ({
    key: type,
    onClick: closeDrawer,
    label: (
      <Link
        href={
          index === 0
            ? dashboardPrefix
            : `${dashboardPrefix}?program_type=${type}`
        }
        className="flex items-center gap-x-2"
      >
        <GoTrophy className="flex-shrink-0" size={20} />
        {t(`programs-types.${type}`)}
      </Link>
    ),
  }));

  const participantItems: MenuItem[] =
    type === "participant"
      ? [
          ...programsTypesItems,
          // {
          //   key: "competition",
          //   onClick: closeDrawer,
          //   label: (
          //     <Link href={dashboardPrefix} className="flex items-center gap-x-2">
          //       <GoTrophy className="flex-shrink-0" size={20} />
          //       {t("competitions")}
          //     </Link>
          //   ),
          // },
          {
            key: "my-competition",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/programs`}
                className="flex items-center gap-x-2"
              >
                <FaList className="flex-shrink-0" size={20} />
                {t("my-competitions")}
              </Link>
            ),
          },
          {
            key: "profile",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/profile`}
                className="flex items-center gap-x-2"
              >
                <FaRegUser className="flex-shrink-0" size={20} />
                {t("profile")}
              </Link>
            ),
          },
          {
            key: "help",
            label: (
              <div className="flex items-center gap-x-2">
                <MdHelpOutline className="flex-shrink-0" size={20} />
                {t("help")}
              </div>
            ),
            children: [
              {
                key: "contact-us",
                onClick: closeDrawer,
                label: (
                  <Link
                    href={`${dashboardPrefix}/help/contact-us`}
                    className="flex items-center gap-x-2"
                  >
                    <BiMessageDetail className="flex-shrink-0" size={20} />
                    {t("contact-us")}
                  </Link>
                ),
              },
              {
                key: "inquiries",
                onClick: closeDrawer,
                label: (
                  <Link
                    href={`${dashboardPrefix}/help/inquiries`}
                    className="flex items-center gap-x-2"
                  >
                    <FaRegListAlt className="flex-shrink-0" size={20} />
                    {t("inquiries")}
                  </Link>
                ),
              },
            ],
          },
          // {
          //   type: "divider",
          //   className: "!mt-auto",
          // },
          {
            key: "logout",
            className: "sm:!mb-3 !mt-auto",
            onClick: () => setLogoutModal(true),
            label: (
              <p className="flex items-center gap-x-2">
                <GoSignOut className="flex-shrink-0" size={20} />
                {t("logout")}
              </p>
            ),
          },
        ]
      : [];

  const judgeItems: MenuItem[] =
    type === "judge"
      ? [
          {
            key: "projects",
            onClick: closeDrawer,
            label: (
              <Link
                href={dashboardPrefix}
                className="flex items-center gap-x-2"
              >
                <GoTrophy size={20} />
                {t("projects")}
              </Link>
            ),
          },
          {
            key: "profile",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/profile`}
                className="flex items-center gap-x-2"
              >
                <FaRegUser size={20} />
                {t("profile")}
              </Link>
            ),
          },
          {
            key: "help",
            label: (
              <div className="flex items-center gap-x-2">
                <MdHelpOutline size={20} />
                {t("help")}
              </div>
            ),
            children: [
              {
                key: "contact-us",
                onClick: closeDrawer,
                label: (
                  <Link
                    href={`${dashboardPrefix}/help/contact-us`}
                    className="flex items-center gap-x-2"
                  >
                    <BiMessageDetail size={20} />
                    {t("contact-us")}
                  </Link>
                ),
              },
              {
                key: "inquiries",
                onClick: closeDrawer,
                label: (
                  <Link
                    href={`${dashboardPrefix}/help/inquiries`}
                    className="flex items-center gap-x-2"
                  >
                    <FaRegListAlt size={20} />
                    {t("inquiries")}
                  </Link>
                ),
              },
            ],
          },
          // {
          //   type: "divider",
          //   className: "!mt-auto",
          // },
          {
            key: "logout",
            className: "sm:!mb-3 !mt-auto",
            onClick: () => setLogoutModal(true),
            label: (
              <p className="flex items-center gap-x-2">
                <GoSignOut size={20} />
                {t("logout")}
              </p>
            ),
          },
        ]
      : [];

  const mentorItems: MenuItem[] =
    type === "mentor"
      ? [
          {
            key: "summary",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}`}
                className="flex items-center gap-x-2"
              >
                <GoHome size={20} />
                {t("home")}
              </Link>
            ),
          },
          {
            key: "sessions",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/sessions`}
                className="flex items-center gap-x-2"
              >
                <FaRegListAlt size={20} />
                {t("mentor.sessions")}
              </Link>
            ),
          },
          {
            key: "my-schedule",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/my-schedule/times`}
                className="flex items-center gap-x-2"
              >
                <TbCalendarTime size={20} />
                {t("my-schedule")}
              </Link>
            ),
          },
          {
            key: "history",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/history`}
                className="flex items-center gap-x-2"
              >
                <MdHistory size={20} />
                {t("session-history")}
              </Link>
            ),
          },
          {
            key: "deliveries",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/deliveries`}
                className="flex items-center gap-x-2"
              >
                <LuListChecks size={20} />
                {t("deliveries")}
              </Link>
            ),
          },
          {
            key: "profile",
            onClick: closeDrawer,
            label: (
              <Link
                href={`${dashboardPrefix}/profile`}
                className="flex items-center gap-x-2"
              >
                <FaRegUser size={20} />
                {t("profile")}
              </Link>
            ),
          },
          {
            key: "logout",
            className: "sm:!mb-3 !mt-auto",
            onClick: () => setLogoutModal(true),
            label: (
              <p className="flex items-center gap-x-2">
                <GoSignOut size={20} />
                {t("logout")}
              </p>
            ),
          },
        ]
      : [];

  const items = [...participantItems, ...judgeItems, ...mentorItems];

  const getSelectedKey = (path: string) => {
    if (path === dashboardPrefix) {
      if (type === "participant") {
        return currentProgramType && programsTypes.includes(currentProgramType)
          ? currentProgramType
          : programsTypes[0];
      }
      if (type === "judge") {
        return "projects";
      }
      if (type === "mentor") {
        return "summary";
      }
    }
    if (
      type === "participant" &&
      currentProgramType &&
      ["sandbox", "idea_bank"].includes(currentProgramType)
    ) {
      return currentProgramType;
    }
    if (path === `${dashboardPrefix}/programs`) return "my-competition";
    if (
      path === `${dashboardPrefix}/my-schedule/times` ||
      path === `${dashboardPrefix}/my-schedule/settings`
    )
      return "my-schedule";
    if (path === `${dashboardPrefix}/sessions`) return "sessions";
    if (path === `${dashboardPrefix}/history`) return "history";
    if (path === `${dashboardPrefix}/deliveries`) return "deliveries";
    if (path === `${dashboardPrefix}/profile`) return "profile";
    if (path === `${dashboardPrefix}/help/contact-us`) return "contact-us";
    if (path === `${dashboardPrefix}/help/inquiries`) return "inquiries";

    return "";
  };

  return (
    <>
      <Menu
        className={`dashboard-menu [&_li]:flex-shrink-0 lg:overflow-auto !flex !flex-col !h-full !text-sm !text-primary-900 !font-medium ${
          collapsed ? "!px-2" : "!px-6"
        }`}
        mode="inline"
        inlineCollapsed={collapsed}
        items={items}
        selectedKeys={[getSelectedKey(path)]}
        defaultOpenKeys={
          collapsed ? [] : path.startsWith(`${dashboardPrefix}/help`) ? ["help"] : []
        }
      />
      <FeedbackModal
        openModal={logoutModal}
        onBtnClick={logout}
        title={t("are-you-sure-you-want-to-logout")}
        type="error"
      >
        <div className="flex gap-x-6 flex-wrap mt-8">
          <Button type="default" onClick={() => setLogoutModal(false)}>
            {t("no-back")}
          </Button>
          <Button
            type="primary"
            onClick={() => {
              setLogoutModal(false);
              logout();
            }}
          >
            {t("yes-logout")}
          </Button>
        </div>
      </FeedbackModal>
    </>
  );
}
