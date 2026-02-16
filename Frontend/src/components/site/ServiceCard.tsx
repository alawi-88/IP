import { Link } from "@/i18n/routing";
import { Button } from "antd";
import { useTranslations } from "next-intl";
import Image from "next/image";
import React from "react";

function ServiceCard({ item, detailsCard }: { item: any; detailsCard?: any }) {
  const t = useTranslations();
  return (
    <div className="flex flex-col gap-y-6 p-4 rounded-2xl bg-card border border-[#D2D6DB] h-full">
      <div className="w-12 h-12 rounded-full">
        {item.icon ? (
          <Image
            src={item.icon}
            width={48}
            height={48}
            className="object-contain w-full h-full"
            alt={item.title}
          />
        ) : (
          <svg
            className="object-contain w-full h-full"
            width="48"
            height="48"
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M0 24C0 10.7452 10.7452 0 24 0C37.2548 0 48 10.7452 48 24C48 37.2548 37.2548 48 24 48C10.7452 48 0 37.2548 0 24Z"
              className="fill-primary opacity-5"
            />
            <path
              d="M28.5529 21.5068C28.8328 21.2015 28.8121 20.727 28.5068 20.4471C28.2015 20.1672 27.727 20.1879 27.4471 20.4932L22.4764 25.9158L20.5303 23.9697C20.2374 23.6768 19.7626 23.6768 19.4697 23.9697C19.1768 24.2626 19.1768 24.7374 19.4697 25.0303L21.9697 27.5303C22.1143 27.675 22.3118 27.7543 22.5163 27.7498C22.7208 27.7454 22.9146 27.6576 23.0529 27.5068L28.5529 21.5068Z"
              className="fill-primary"
            />
            <path
              fillRule="evenodd"
              clipRule="evenodd"
              d="M24 34.75C18.0629 34.75 13.25 29.9371 13.25 24C13.25 18.0629 18.0629 13.25 24 13.25C29.9371 13.25 34.75 18.0629 34.75 24C34.75 29.9371 29.9371 34.75 24 34.75ZM14.75 24C14.75 29.1086 18.8914 33.25 24 33.25C29.1086 33.25 33.25 29.1086 33.25 24C33.25 18.8914 29.1086 14.75 24 14.75C18.8914 14.75 14.75 18.8914 14.75 24Z"
              className="fill-primary"
            />
          </svg>
        )}
      </div>
      <div className="space-y-2">
        <h3 className="text-lg font-bold">{item.title}</h3>
        <p className="">{item.description}</p>
      </div>

      {item?.tags?.length > 0 && (
        <div className="tags flex flex-wrap gap-2 items-center">
          {item?.tags?.map((tag: any, idx: any) => (
            <div
              key={`servicesTags-${item.id}-${idx}`}
              className="inline-flex py-1 px-2 text-xs font-medium rounded-md bg-[#F9FAFB] border border-[#E5E7EB]"
            >
              {tag}
            </div>
          ))}
        </div>
      )}

      <div className="flex flex-wrap items-center gap-x-4 gap-y-2 mt-auto">
        {item.main_action?.link && (
          <a href={item.main_action?.link} aria-label={item.main_action?.title}>
            <Button type="default">{item.main_action?.title}</Button>
          </a>
        )}
        {item.secondary_action?.link && (
          <a
            href={item.secondary_action?.link}
            aria-label={item.secondary_action?.title}
          >
            <Button type="primary">{item.secondary_action?.title}</Button>
          </a>
        )}
        {detailsCard && item.id && (
          <Link
            href={`/site/services/${item.id}`}
            aria-label={t("services.details")}
          >
            <Button type="primary">{t("services.details")}</Button>
          </Link>
        )}
      </div>
    </div>
  );
}

export default ServiceCard;
