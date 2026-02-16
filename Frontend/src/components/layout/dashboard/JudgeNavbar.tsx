"use client";

import { Button, Menu, MenuProps } from "antd";
import { GoSignOut, GoTrophy } from "react-icons/go";
import { FaRegUser, FaRegListAlt } from "react-icons/fa";
import { Link, usePathname} from "@/i18n/routing";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import { useState } from "react";
import { useUserStore } from "@/store/user";
import { useLocale, useTranslations } from "next-intl";
import { BiMessageDetail } from "react-icons/bi";
import { MdHelpOutline } from "react-icons/md";
import { useRouter } from "next/navigation";

type MenuItem = Required<MenuProps>["items"][number];

export default function DashboardNavbar({
  closeDrawer,
}: {
  closeDrawer?: () => void;
}) {
  const path = usePathname();
  const router = useRouter();
  const t = useTranslations();
  const locale = useLocale();

  const [logoutModal, setLogoutModal] = useState(false);

  const logoutUser = useUserStore((state) => state.logout);

  const dashboardPrefix = "/judge/judge-dashboard";

  const logout = () => {
    logoutUser();
    router.replace(`/${locale}/judge/login`);
    // window.location.href = `/${locale}/judge/login`;
  };

  const items: MenuItem[] = [
    {
      key: "projects",
      onClick: closeDrawer,
      label: (
        <Link href={dashboardPrefix} className="flex items-center gap-x-2">
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
  ];

  const getSelectedKey = (path: string) => {
    if (path === dashboardPrefix) return "projects";
    if (path === `${dashboardPrefix}/profile`) return "profile";
    if (path === `${dashboardPrefix}/help/contact-us`) return "contact-us";
    if (path === `${dashboardPrefix}/help/inquiries`) return "inquiries";
    return "";
  };

  return (
    <>
      <Menu
        className="dashboard-menu [&_li]:flex-shrink-0 lg:overflow-auto !flex !flex-col !h-full !px-6 !text-sm !text-primary-900 !font-medium"
        mode="inline"
        items={items}
        selectedKeys={[getSelectedKey(path)]}
        defaultOpenKeys={
          path.startsWith(`${dashboardPrefix}/help`) ? ["help"] : []
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
