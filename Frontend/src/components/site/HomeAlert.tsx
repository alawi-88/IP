"use client";

import axiosInstance from "@/axios";
import { useQuery } from "@tanstack/react-query";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import React, { useState } from "react";
import { MdOutlineKeyboardArrowLeft } from "react-icons/md";
import { FiExternalLink } from "react-icons/fi";

function HomeAlert() {
  const t = useTranslations();
  const locale = useLocale();
  const [open, setOpen] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ["landing-page", locale],
    queryFn: async () => {
      const response = await axiosInstance.get(`/landing-page`);
      return response.data;
    },
  });

  const isEnabled = React.useMemo(() => {
    return (
      data?.government_verification_banner_enabled === true ||
      data?.government_verification_banner_enabled === "true"
    );
  }, [data]);

  return (
    isEnabled && (
      <div className="section-style bg-[#F3F4F6] py-2">
        <div className="container">
          <div className="digital-stamp-header flex flex-wrap gap-x-1 gap-y-2 text-sm font-medium ">
            <div className="flex items-center gap-2">
              <Image
                className="inline-block w-[20px] h-[20px] object-contain"
                width={20}
                height={20}
                src={"/SR_Flag.svg"}
                alt="SR Flag"
              />
              {t("site.dga-banner")}
            </div>
            <button
              onClick={() => setOpen((prev) => !prev)}
              className="flex items-center gap-1 text-primary py-[1px]"
              aria-expanded={open}
            >
              {t("site.dga.how-to-verify")}
              <MdOutlineKeyboardArrowLeft
                className={`text-lg transition-transform duration-300 ${
                  open ? "rotate-90" : "-rotate-90"
                }`}
              />
            </button>
          </div>
          <div
            className={`grid transition-[grid-template-rows,opacity] duration-500 ease-in-out ${
              open ? "grid-rows-[1fr]" : "grid-rows-[0fr]"
            }`}
          >
            <div className="overflow-hidden">
              <div className="digital-stamp-body mt-2 py-8">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                  <div className="flex items-start gap-4">
                    <div className="flex items-center justify-center w-12 h-12 border border-primary text-primary text-xl rounded-full flex-shrink-0">
                      <svg
                        className="w-5 h-5"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 17.5 17.5"
                      >
                        <path
                          d="M16.22,1.35a4.32,4.32,0,0,0-6.27,0l-.71.74a.75.75,0,1,0,1.08,1L11,2.39a2.81,2.81,0,0,1,4.11,0,3.17,3.17,0,0,1,0,4.35L11.9,10.11a2.82,2.82,0,0,1-4.1,0,2.84,2.84,0,0,1-.41-.52.75.75,0,1,0-1.28.78,4.5,4.5,0,0,0,.6.78,4.3,4.3,0,0,0,5.39.71,4.22,4.22,0,0,0,.88-.71l3.24-3.37A4.67,4.67,0,0,0,16.22,1.35Zm-5.43,5a4.32,4.32,0,0,0-6.27,0L1.28,9.72a4.67,4.67,0,0,0,0,6.43,4.32,4.32,0,0,0,6.27,0l.71-.74a.75.75,0,1,0-1.08-1l-.71.74a2.81,2.81,0,0,1-4.11,0,3.17,3.17,0,0,1,0-4.35L5.6,7.39a2.81,2.81,0,0,1,4.11,0,3.28,3.28,0,0,1,.4.52.74.74,0,0,0,1,.25.75.75,0,0,0,.25-1A4.5,4.5,0,0,0,10.79,6.35Z"
                          fill="currentColor"
                        />
                      </svg>
                    </div>
                    <div>
                      <p className="text-lg font-bold mb-3">
                        {t.rich("site.dga.domain.title", {
                          primary: (chunks) => (
                            <span className="text-primary">{chunks}</span>
                          ),
                        })}
                      </p>
                      <p className="text-base">
                        {t("site.dga.domain.sub-title")}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <div className="flex items-center justify-center w-12 h-12 border border-primary text-primary text-xl rounded-full flex-shrink-0">
                      <svg
                        className="w-5 h-5"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 17.5 21.5"
                      >
                        <path
                          d="M5.25,14.25a1,1,0,0,1,1-1h0a1,1,0,0,1,0,2h0A1,1,0,0,1,5.25,14.25Zm5,0a1,1,0,0,1,1-1h0a1,1,0,0,1,0,2h0A1,1,0,0,1,10.24,14.25Zm7-3.44A4.3,4.3,0,0,0,14,7.21v-2a5.25,5.25,0,0,0-10.5,0v2a4.3,4.3,0,0,0-3.23,3.6A27.09,27.09,0,0,0,0,14.25a27.09,27.09,0,0,0,.27,3.44,4.28,4.28,0,0,0,4,3.71c1.42.07,2.87.1,4.47.1s3.05,0,4.47-.1a4.28,4.28,0,0,0,4-3.71,27.09,27.09,0,0,0,.27-3.44A27.09,27.09,0,0,0,17.23,10.81ZM5,5.25a3.75,3.75,0,0,1,7.5,0V7.07C11.3,7,10.08,7,8.75,7S6.2,7,5,7.07ZM15.74,17.49a2.77,2.77,0,0,1-2.58,2.41c-1.41.07-2.84.1-4.41.1s-3,0-4.41-.1a2.77,2.77,0,0,1-2.58-2.41,25.12,25.12,0,0,1-.26-3.24A25.12,25.12,0,0,1,1.76,11,2.77,2.77,0,0,1,4.34,8.6c1.41-.07,2.84-.1,4.41-.1s3,0,4.41.1A2.77,2.77,0,0,1,15.74,11,25.12,25.12,0,0,1,16,14.25,25.12,25.12,0,0,1,15.74,17.49Z"
                          fill="currentColor"
                        />
                      </svg>
                    </div>
                    <div>
                      <p className="text-lg font-bold mb-3">
                        {t.rich("site.dga.https.title", {
                          primary: (chunks) => (
                            <span className="text-primary font-semibold">
                              {chunks}
                            </span>
                          ),
                        })}
                      </p>
                      <p className="text-base">
                        {t("site.dga.https.sub-title")}
                      </p>
                    </div>
                  </div>
                </div>
                {data?.dga_registration_number && (
                  <div className="flex items-center gap-3 rounded-lg bg-white py-2 px-6">
                    <Image
                      src="/DGA-logo-icon.svg"
                      width={20}
                      height={20}
                      alt="Dga Logo"
                    />
                    <div className="flex items-center gap-x-3 flex-wrap">
                      <p>{t("site.dga.registration-number")}</p>
                      <a
                        href={data?.dga_certificate_url || "#"}
                        target="_blank"
                        className="inline-flex items-center gap-1 text-primary underline"
                      >
                        {data?.dga_registration_number}
                        <FiExternalLink />
                      </a>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    )
  );
}

export default HomeAlert;
