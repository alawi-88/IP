"use client";

import { useUserStore } from "@/store/user";
import { useTranslations } from "next-intl";
import { Button, Spin } from "antd";
import { Link } from "@/i18n/routing";
import { FaRegUser } from "react-icons/fa";
import Image from "next/image";

export default function ProfilePage() {
  const t = useTranslations();
  const user = useUserStore((state) => state.mentor);

  return (
    <section className="flex flex-col gap-y-6">
      <div className="flex justify-between gap-4">
        <h1 className="text-2xl text-foreground font-bold">{t("profile")}</h1>
        <Link href={"/mentor/mentor-dashboard/profile/edit"}>
          <Button type="primary">{t("edit")}</Button>
        </Link>
      </div>
      {!user ? (
        <Spin className="flex justify-center w-full" />
      ) : (
        <div className="dashboard-card">
          <div className="flex flex-col gap-y-4">
            <div className="user-img">
              {user.image ? (
                <Image
                  src={user.image}
                  className="w-20 h-20 rounded-full object-cover flex-shrink-0"
                  width={80}
                  height={80}
                  alt={user.name}
                />
              ) : (
                <div className="w-20 h-20 rounded-full bg-primary flex items-center justify-center text-2xl flex-shrink-0">
                  <FaRegUser className="text-white b" />
                </div>
              )}
            </div>

            <h2 className="text-lg font-bold text-primary col-span-5">
              {t("personal-information")}
            </h2>

            <div className="grid md:grid-cols-3 sm:grid-cols-2 flex-wrap gap-y-4 gap-x-6 [&_label]:text-nowrap [&_div]:min-w-52">
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("full-name")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.name}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("email")}
                </label>
                <p className="font-medium text-base text-foreground break-all">
                  {user.email}
                </p>
              </div>

              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("phone-number")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.phone || "-"}
                </p>
              </div>

              <div className="flex flex-col gap-y-2 col-span-full">
                <label className="font-normal text-sm text-secondary">
                  {t("bio")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.brief || "-"}
                </p>
              </div>

            </div>
          </div>

          <div className="flex flex-col gap-y-4">
            <h2 className="text-lg font-bold text-primary col-span-5">
              {t("experiences")}
            </h2>

            <div className="grid md:grid-cols-3 sm:grid-cols-2 flex-wrap gap-y-4 gap-x-6 [&_label]:text-nowrap [&_div]:min-w-52">
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("profession")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.profession || "-"}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("the-experience")}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.experience || "-"}
                </p>
              </div>
            </div>
          </div>

          <div className="flex flex-col gap-y-4">
            <h2 className="text-lg font-bold text-primary col-span-5">
              {t("social-accounts")}
            </h2>

            <div className="grid md:grid-cols-3 sm:grid-cols-2 flex-wrap gap-y-4 gap-x-6 [&_label]:text-nowrap [&_div]:min-w-52">
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("socialMedia.facebook")}
                </label>
                <p className="font-medium text-base text-foreground break-all">
                  {user.facebook ? (
                    <a href={user.facebook} target="_blank">
                      {user.facebook}
                    </a>
                  ) : (
                    "-"
                  )}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("socialMedia.linkedIn")}
                </label>
                <p className="font-medium text-base text-foreground break-all">
                  {user.linkedin ? (
                    <a href={user.linkedin} target="_blank">
                      {user.linkedin}
                    </a>
                  ) : (
                    "-"
                  )}
                </p>
              </div>
              <div className="flex flex-col gap-y-2">
                <label className="font-normal text-sm text-secondary">
                  {t("socialMedia.instagram")}
                </label>
                <p className="font-medium text-base text-foreground break-all">
                  {user.instagram ? (
                    <a href={user.instagram} target="_blank">
                      {user.instagram}
                    </a>
                  ) : (
                    "-"
                  )}
                </p>
              </div>
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
