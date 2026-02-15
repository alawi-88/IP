"use client";

import { FaRegUser, FaPhone, FaEnvelope, FaBriefcase } from "react-icons/fa";
import { useUserStore } from "@/store/user";
import { useTranslations } from "next-intl";

export default function ProfilePage() {
  const t = useTranslations();
  const user = useUserStore((state) => state.judge);

  if (user == null) {
    return null;
  }

  return (
    <section className="flex flex-col gap-y-6">
      <h1 className="text-2xl text-foreground font-bold">{t("profile")}</h1>
      <div className="dashboard-card">
        <div className="flex flex-col gap-y-6">
          <h2 className="text-lg font-bold text-foreground col-span-5">
            {t("personal-information")}
          </h2>
          <div className="w-[104px] h-[104px] rounded-full bg-primary flex items-center justify-center text-4xl text-white">
            <FaRegUser />
          </div>
          <div className="flex flex-col gap-y-6 px-8">
            <div className="flex items-center gap-x-4">
              <div className="w-6 text-secondary">
                <FaRegUser />
              </div>
              <div className="flex flex-col sm:flex-row items-start sm:items-center flex-1 sm:gap-x-16">
                <label className="font-normal text-sm text-secondary w-[120px]">
                  {t("full-name") + ":"}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.name}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-x-4 max-w-[600px]">
              <div className="w-6 text-secondary">
                <FaPhone />
              </div>
              <div className="flex flex-col sm:flex-row items-start sm:items-center flex-1 sm:gap-x-16">
                <label className="font-normal text-sm text-secondary w-[120px]">
                  {t("phone-number") + ":"}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.phone_number}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-x-4 max-w-[600px]">
              <div className="w-6 text-secondary">
                <FaEnvelope />
              </div>
              <div className="flex flex-col sm:flex-row items-start sm:items-center flex-1 sm:gap-x-16">
                <label className="font-normal text-sm text-secondary w-[120px]">
                  {t("email") + ":"}
                </label>
                <p className="font-medium text-base text-foreground break-all">
                  {user.email}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-x-4 max-w-[600px]">
              <div className="w-6 text-secondary">
                <FaBriefcase />
              </div>
              <div className="flex flex-col sm:flex-row items-start sm:items-center flex-1 sm:gap-x-16">
                <label className="font-normal text-sm text-secondary w-[120px]">
                  {t("experience") + ":"}
                </label>
                <p className="font-medium text-base text-foreground">
                  {user.experience_field}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
