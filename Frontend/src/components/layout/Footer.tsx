"use client";

import { Divider } from "antd";
import Image from "next/image";
import { BsTwitterX } from "react-icons/bs";
import {
  FaFacebook,
  FaInstagram,
  FaLinkedin,
  FaSnapchat,
  FaYoutube,
} from "react-icons/fa";
import { MdArrowOutward } from "react-icons/md";
import { useLocale, useTranslations } from "next-intl";
import { Link, usePathname } from "@/i18n/routing";
import NextLink from "next/link";
import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import { useThemeStore } from "@/store/theme";

interface PrivacyPolicy {
  id: number;
  slug: string;
  title: string;
  content: string;
  is_published: boolean;
  created_at: string;
  updated_at: string;
}

interface SocialLink {
  name: string;
  url: string | null;
}

type FooterProps = {
  className?: string;
};

export default function Footer({ className = "" }: FooterProps) {
  const t = useTranslations();
  const locale = useLocale();
  const pathname = usePathname();
  const isLadingPage = pathname.includes("site");
  const theme = useThemeStore((state) => state.theme)!;

  const { data: privacyPolicy } = useQuery<PrivacyPolicy>({
    queryKey: ["privacy-policy"],
    queryFn: async () => {
      const response = await axiosInstance.get("/pages/privacy-policy");
      return response.data.data;
    },
  });

  const { data: socialLinks } = useQuery<SocialLink[]>({
    queryKey: ["social-links"],
    queryFn: async () => {
      const response = await axiosInstance.get("/social-links");
      return response.data.data;
    },
  });

  const socialIcons = {
    facebook: <FaFacebook size={24} color="#FFFFFF" />,
    x: <BsTwitterX size={24} color="#FFFFFF" />,
    instagram: <FaInstagram size={24} color="#FFFFFF" />,
    linkedin: <FaLinkedin size={24} color="#FFFFFF" />,
    youtube: <FaYoutube size={24} color="#FFFFFF" />,
    snapchat: <FaSnapchat size={24} color="#FFFFFF" />,
  };

  return (
    <footer className={`bg-primary dark:bg-[var(--card-bg)] ${className ? className : ""}`}>
      <div className="container max-w-full w-full px-8 lg:px-[100px] pt-14 pb-6 flex flex-col gap-y-8 items-center">
        <div className="flex flex-col gap-y-8 md:flex-row justify-between w-full">
          <div>
            <Link href={"/site"}>
              <Image
                className="dynamic-logo lg"
                src={theme.white_logo}
                alt="logo"
                width={200}
                height={200}
                priority
              />
            </Link>
          </div>
          {socialLinks?.some((link) => link.url) && (
            <div className="flex flex-col">
              <p className="text-white mb-4">{t("social-accounts")}</p>
              <div className="flex flex-wrap items-center gap-4">
                {socialLinks?.map(
                  (link) =>
                    link.url && (
                      <NextLink
                        key={link.name}
                        href={link.url}
                        target="_blank"
                        className="bg-[rgb(255_252_252_/20%)] p-2 rounded-full"
                      >
                        {socialIcons[link.name as keyof typeof socialIcons]}
                      </NextLink>
                    )
                )}
              </div>
            </div>
          )}
        </div>

        <Divider className="!bg-card !opacity-50 w-full" />

        <div className="w-full flex flex-col gap-y-2 md:flex-row justify-between items-center">
          {privacyPolicy?.is_published === true && (
            <Link
              className="text-white flex items-center gap-x-2"
              href="/privacy-policy"
            >
              {t("privacy-policy")}
              <MdArrowOutward
                size={18}
                className={locale === "ar" ? "-rotate-90" : ""}
              />
            </Link>
          )}
          <p className="text-white text-xs">
            © {new Date().getFullYear()} {t("all-rights-reserved")}
          </p>
        </div>
      </div>
    </footer>
  );
}
