"use client";
import React from "react";
import { Breadcrumb, Button, ConfigProvider, Divider, Tabs } from "antd";
import { PiLink } from "react-icons/pi";
import { TbExternalLink } from "react-icons/tb";
import Image from "next/image";
import { useLocale, useTranslations } from "next-intl";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Navigation } from "swiper/modules";
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/navigation";
import ServiceCard from "@/components/site/ServiceCard";
import { useRouter } from "@/i18n/routing";
import Empty from "@/components/Empty";

function ServicePage({ service }: { service: any }) {
  const t = useTranslations();
  const locale = useLocale();
  const router = useRouter();
  return service ? (
    <>
      <div className="section-style service-details-section py-10">
        <div className="container">
          <div className="grid lg:grid-cols-12 lg:gap-x-8 gap-y-8">
            <div className="details-wrapper lg:col-span-8 overflow-hidden">
              <div className="details-head space-y-4 mb-8 lg:mb-16">
                <Breadcrumb
                  className="site_breadcrumb_style"
                  separator=">"
                  items={[
                    {
                      title: t("home"),
                      className: "cursor-pointer",
                      href: `/${locale}/site`,
                      onClick: (e) => {
                        e.preventDefault();
                        router.push("/site");
                      },
                    },
                    {
                      title: service.title,
                    },
                  ]}
                />

                <div className="title-wrap flex items-center justify-between gap-x-6 gap-y-2 max-md:flex-wrap">
                  <h1 className="text-3xl font-bold">{service.title}</h1>
                  {service.startServiceLink && (
                    <a
                      href={service.startServiceLink}
                      aria-label={t("services.startService")}
                    >
                      <Button type="primary">
                        {t("services.startService")}
                      </Button>
                    </a>
                  )}
                </div>
                {service?.tags?.length > 0 && (
                  <div className="tags flex flex-wrap gap-2 items-center">
                    {service?.tags?.map((tag: any, idx: any) => (
                      <div
                        key={`servicesTags-${service.id}-${idx}`}
                        className="inline-flex py-1 px-2 text-xs font-medium rounded-md bg-[#F9FAFB] border border-[#E5E7EB]"
                      >
                        {tag}
                      </div>
                    ))}
                  </div>
                )}
                {service.description && <p>{service.description}</p>}
                {service.serviceLevelLink && (
                  <a
                    href={service.serviceLevelLink}
                    className="flex items-center flex-wrap gap-x-2 gap-y-1 text-primary"
                    aria-label={t("services.serviceLevel")}
                    target="_blank"
                  >
                    {t("services.serviceLevel")}
                    <PiLink size={20} />
                  </a>
                )}
              </div>
              <div className="details-body rich_text_area">
                <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
                  <Tabs
                    defaultActiveKey="1"
                    items={[
                      {
                        key: "1",
                        label: t("services.steps"),
                        children: service.steps ? (
                          <div
                            dangerouslySetInnerHTML={{ __html: service.steps }}
                          />
                        ) : (
                          t("not-available")
                        ),
                      },
                      {
                        key: "2",
                        label: t("services.termsOfUse"),
                        children: service.termsOfUse ? (
                          <div
                            dangerouslySetInnerHTML={{
                              __html: service.termsOfUse,
                            }}
                          />
                        ) : (
                          t("not-available")
                        ),
                      },
                      {
                        key: "3",
                        label: t("services.requiredDocuments"),
                        children: service.requiredDocuments ? (
                          <div
                            dangerouslySetInnerHTML={{
                              __html: service.requiredDocuments,
                            }}
                          />
                        ) : (
                          t("not-available")
                        ),
                      },
                    ]}
                    className="site_tabs_style"
                  />
                </ConfigProvider>
              </div>
            </div>
            <aside className="details-side lg:col-span-4">
              <div className="side-box py-10 px-4 md:px-8 lg:px-10 rounded-2xl border border-[#D2D6DB]">

                <div className="info-group space-y-4 ">
                  {service.targetAudience && (
                    <div className="info-item flex items-start gap-x-3">
                      <svg
                        className="w-6 h-6 flex-shrink-0 text-primary"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M11.9999 1.25C9.10038 1.25 6.74988 3.6005 6.74988 6.5C6.74988 9.39949 9.10038 11.75 11.9999 11.75C14.8994 11.75 17.2499 9.39949 17.2499 6.5C17.2499 3.6005 14.8994 1.25 11.9999 1.25ZM8.24988 6.5C8.24988 4.42893 9.92881 2.75 11.9999 2.75C14.0709 2.75 15.7499 4.42893 15.7499 6.5C15.7499 8.57107 14.0709 10.25 11.9999 10.25C9.92881 10.25 8.24988 8.57107 8.24988 6.5Z"
                        />
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M18.2287 15.0792C18.0661 14.9874 17.9225 14.9064 17.8062 14.8372C14.2521 12.7209 9.74797 12.7209 6.19392 14.8372C6.0776 14.9064 5.93393 14.9875 5.77126 15.0793C5.05845 15.4814 3.98094 16.0893 3.24277 16.8118C2.78111 17.2637 2.34245 17.8592 2.26271 18.5888C2.1779 19.3646 2.51636 20.0927 3.19538 20.7396C4.36683 21.8556 5.77261 22.75 7.59092 22.75H16.4092C18.2275 22.75 19.6333 21.8556 20.8047 20.7396C21.4837 20.0927 21.8222 19.3646 21.7374 18.5888C21.6577 17.8592 21.219 17.2637 20.7573 16.8118C20.0192 16.0893 18.9415 15.4813 18.2287 15.0792ZM6.96134 16.126C10.0425 14.2913 13.9576 14.2913 17.0388 16.126C17.2067 16.226 17.3906 16.3303 17.5833 16.4397C18.2959 16.8439 19.128 17.316 19.7081 17.8838C20.0682 18.2363 20.2217 18.5271 20.2463 18.7517C20.2658 18.9301 20.2207 19.2242 19.7701 19.6535C18.7342 20.6404 17.6816 21.25 16.4092 21.25H7.59092C6.31848 21.25 5.2659 20.6404 4.23005 19.6535C3.77942 19.2242 3.73433 18.9301 3.75382 18.7517C3.77838 18.5271 3.93189 18.2363 4.29202 17.8838C4.87211 17.316 5.70413 16.844 6.41674 16.4397C6.60941 16.3304 6.79348 16.226 6.96134 16.126Z"
                        />
                      </svg>

                      <div className="item-content space-y-1">
                        <p className="font-bold">
                          {t("services.targetAudience")}
                        </p>
                        <p>{service.targetAudience}</p>
                      </div>
                    </div>
                  )}
                  {service.serviceDuration && (
                    <div className="info-item flex items-start gap-x-3">
                      <svg
                        className="w-6 h-6 flex-shrink-0 text-primary"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M12.75 8C12.75 7.58579 12.4142 7.25 12 7.25C11.5858 7.25 11.25 7.58579 11.25 8V12C11.25 12.1989 11.329 12.3897 11.4697 12.5303L13.4697 14.5303C13.7626 14.8232 14.2374 14.8232 14.5303 14.5303C14.8232 14.2374 14.8232 13.7626 14.5303 13.4697L12.75 11.6893V8Z" />
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M12 1.25C6.06294 1.25 1.25 6.06294 1.25 12C1.25 17.9371 6.06294 22.75 12 22.75C17.9371 22.75 22.75 17.9371 22.75 12C22.75 6.06294 17.9371 1.25 12 1.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75C17.1086 2.75 21.25 6.89137 21.25 12C21.25 17.1086 17.1086 21.25 12 21.25C6.89137 21.25 2.75 17.1086 2.75 12Z"
                        />
                      </svg>

                      <div className="item-content space-y-1">
                        <p className="font-bold">
                          {t("services.serviceDuration")}
                        </p>
                        <p>{service.serviceDuration}</p>
                      </div>
                    </div>
                  )}
                  {service.serviceChannels && (
                    <div className="info-item flex items-start gap-x-3">
                      <svg
                        className="w-6 h-6 flex-shrink-0 text-primary"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path d="M17.5 10.5C16.9477 10.5 16.5 10.9477 16.5 11.5C16.5 12.0523 16.9477 12.5 17.5 12.5H17.509C18.0613 12.5 18.509 12.0523 18.509 11.5C18.509 10.9477 18.0613 10.5 17.509 10.5H17.5Z" />
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M16.948 1.25H18.052C18.9505 1.24997 19.6997 1.24995 20.2945 1.32991C20.9223 1.41432 21.4891 1.59999 21.9445 2.05546C22.4 2.51093 22.5857 3.07773 22.6701 3.70552C22.7501 4.30027 22.75 5.04943 22.75 5.94784V10.052C22.75 10.9504 22.7501 11.6997 22.6701 12.2945C22.5857 12.9223 22.4 13.4891 21.9445 13.9445C21.4891 14.4 20.9223 14.5857 20.2945 14.6701C19.6997 14.7501 18.9505 14.75 18.052 14.75H16.948C16.0495 14.75 15.3003 14.7501 14.7055 14.6701C14.0777 14.5857 13.5109 14.4 13.0555 13.9445C12.6 13.4891 12.4143 12.9223 12.3299 12.2945C12.2499 11.6997 12.25 10.9505 12.25 10.052V5.948C12.25 5.04954 12.2499 4.3003 12.3299 3.70552C12.4143 3.07773 12.6 2.51093 13.0555 2.05546C13.5109 1.59999 14.0777 1.41432 14.7055 1.32991C15.3003 1.24995 16.0495 1.24997 16.948 1.25ZM14.9054 2.81654C14.4439 2.87858 14.2464 2.9858 14.1161 3.11612C13.9858 3.24644 13.8786 3.44393 13.8165 3.9054C13.7516 4.38843 13.75 5.03599 13.75 6V10C13.75 10.964 13.7516 11.6116 13.8165 12.0946C13.8786 12.5561 13.9858 12.7536 14.1161 12.8839C14.2464 13.0142 14.4439 13.1214 14.9054 13.1835C15.3884 13.2484 16.036 13.25 17 13.25H18C18.964 13.25 19.6116 13.2484 20.0946 13.1835C20.5561 13.1214 20.7536 13.0142 20.8839 12.8839C21.0142 12.7536 21.1214 12.5561 21.1835 12.0946C21.2484 11.6116 21.25 10.964 21.25 10V6C21.25 5.03599 21.2484 4.38843 21.1835 3.9054C21.1214 3.44393 21.0142 3.24644 20.8839 3.11612C20.7536 2.9858 20.5561 2.87858 20.0946 2.81654C19.6116 2.7516 18.964 2.75 18 2.75H17C16.036 2.75 15.3884 2.7516 14.9054 2.81654Z"
                        />
                        <path d="M9.92035 1.25H9.96412C10.3783 1.25 10.7141 1.58579 10.7141 2C10.7141 2.41421 10.3783 2.75 9.96412 2.75C8.31557 2.75 7.13798 2.75098 6.22587 2.84756C5.32731 2.9427 4.77328 3.12377 4.34178 3.42727C3.98538 3.67795 3.67522 3.98945 3.42544 4.34779C3.12261 4.78222 2.942 5.34017 2.84718 6.24386C2.75097 7.16072 2.75 8.34424 2.75 10C2.75 11.6558 2.75097 12.8393 2.84718 13.7561C2.942 14.6598 3.12261 15.2178 3.42544 15.6522C3.67522 16.0106 3.98538 16.3221 4.34178 16.5727C4.77328 16.8762 5.32731 17.0573 6.22587 17.1524C7.13798 17.249 8.31557 17.25 9.96412 17.25H13.9462C15.5947 17.25 16.7723 17.249 17.6844 17.1524C18.583 17.0573 19.137 16.8762 19.5685 16.5727C19.9073 16.3344 20.3752 16.4159 20.6135 16.7547C20.8518 17.0935 20.7703 17.5613 20.4315 17.7996C19.7059 18.31 18.8653 18.5358 17.8424 18.6441C16.8421 18.75 15.5851 18.75 13.9899 18.75H12.75V21.25H16C16.4142 21.25 16.75 21.5858 16.75 22C16.75 22.4142 16.4142 22.75 16 22.75H8C7.58579 22.75 7.25 22.4142 7.25 22C7.25 21.5858 7.58579 21.25 8 21.25H11.25V18.75H9.92037C8.32519 18.75 7.06822 18.75 6.06793 18.6441C5.04498 18.5358 4.20436 18.31 3.47882 17.7996C2.97908 17.4481 2.54458 17.0116 2.1949 16.51C1.68756 15.7822 1.46308 14.9392 1.35537 13.9127C1.24999 12.9084 1.24999 11.6462 1.25 10.0434V9.95657C1.24999 8.35376 1.24999 7.09158 1.35537 6.08732C1.46308 5.06083 1.68756 4.21784 2.1949 3.49002C2.54458 2.98836 2.97908 2.55186 3.47882 2.20036C4.20436 1.69005 5.04498 1.46421 6.06793 1.3559C7.06822 1.24999 8.32518 1.24999 9.92035 1.25Z" />
                      </svg>

                      <div className="item-content space-y-1">
                        <p className="font-bold">
                          {t("services.serviceChannels")}
                        </p>
                        <p>{service.serviceChannels}</p>
                      </div>
                    </div>
                  )}
                  {service.serviceCost && (
                    <div className="info-item flex items-start gap-x-3">
                      <svg
                        className="w-6 h-6 flex-shrink-0 text-primary"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          d="M12 22.75C17.9371 22.75 22.75 17.9369 22.75 12C22.75 6.06292 17.9371 1.25 12 1.25C6.06292 1.25 1.25 6.06292 1.25 12C1.25 17.9369 6.06291 22.75 12 22.75ZM12 21.25C6.89136 21.25 2.75 17.1085 2.75 12C2.75 6.89135 6.89135 2.75 12 2.75C17.1087 2.75 21.25 6.89135 21.25 12C21.25 17.1085 17.1086 21.25 12 21.25ZM10.3359 15.3135C10.6075 15.2588 10.8414 15.1035 10.9932 14.8896L11.6045 14.0117C11.668 13.9209 11.7051 13.8113 11.7051 13.6934V12.4023L12.8818 12.1611V14.4883L16.6582 13.7109C16.8365 13.3283 16.9548 12.9131 17 12.4775L14.0586 13.083V11.9189L16.6582 11.3838C16.8365 11.0012 16.9548 10.5859 17 10.1504L14.0586 10.7549L14.0586 6.56934C13.6079 6.81421 13.2072 7.13996 12.8818 7.52441L12.8818 10.9971L11.7051 11.2393L11.7051 6C11.2546 6.24472 10.8546 6.57078 10.5293 6.95508L10.5293 11.4805L7.89648 12.0225C7.71818 12.4051 7.59999 12.8204 7.55469 13.2559L10.5293 12.6445V14.1104L7.3418 14.7656C7.16335 15.1484 7.04519 15.5642 7 16L10.3359 15.3135ZM16.668 16.2275C16.8413 15.8468 16.956 15.4334 17 15L13.332 15.7725C13.1587 16.1533 13.0439 16.5666 13 17L16.668 16.2275Z"
                          fill="currentColor"
                        />
                      </svg>

                      <div className="item-content space-y-1">
                        <p className="font-bold">{t("services.serviceCost")}</p>
                        <p>{service.serviceCost}</p>
                      </div>
                    </div>
                  )}

                  {service?.paymentChannels?.length > 0 && (
                    <div className="info-item flex items-start gap-x-3">
                      <div className="item-content space-y-1">
                        <p className="font-bold text-lg">
                          {t("services.paymentChannels")}
                        </p>
                        <div className="flex items-center flex-wrap gap-2">
                          {service.paymentChannels.map(
                            (item: any, idx: any) =>
                              item.logo && (
                                <div
                                  className="flex items-center justify-center border border-[#D2D6DB] px-2 h-12 rounded-lg"
                                  key={`paymentChannels-${service.id}-${idx}`}
                                >
                                  <Image
                                    className="w-12 h-4 object-contain"
                                    src={item.logo}
                                    width={48}
                                    height={16}
                                    alt={item.alt || ""}
                                  />
                                </div>
                              )
                          )}
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                <Divider className="my-6 divider-row border-[#D2D6DB]" />

                <div className="info-group space-y-4 ">
                  {service.FAQsLink && (
                    <div className="info-item flex items-start gap-x-3">
                      <div className="item-content space-y-1">
                        <p className="font-bold text-lg">
                          {t("services.FAQs")}
                        </p>
                        <a
                          href={service.FAQsLink}
                          className="flex items-center flex-wrap gap-x-2 gap-y-1 text-primary"
                          target="_blank"
                          aria-label={t("services.FAQs")}
                        >
                          {t("services.FAQsLink")}
                          <TbExternalLink size={20} />
                        </a>
                      </div>
                    </div>
                  )}
                  {service.phone && (
                    <div className="info-item flex items-start gap-x-3">
                      <svg
                        className="w-6 h-6 flex-shrink-0 text-primary"
                        width="24"
                        height="25"
                        viewBox="0 0 24 25"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M5.31677 1.69111C5.88346 1.80823 6.33476 2.18897 6.61515 2.692L7.50836 4.29444C7.83737 4.88465 8.11424 5.38134 8.29505 5.81311C8.48686 6.27112 8.60078 6.72278 8.5487 7.22214C8.49663 7.72151 8.29197 8.13994 8.0098 8.54853C7.74379 8.93371 7.3704 9.36258 6.92669 9.87221L4.69884 12.4312C6.46568 15.3272 9.07461 17.9373 11.9736 19.706L14.5326 17.4781C15.0422 17.0344 15.4711 16.661 15.8563 16.395C16.2649 16.1128 16.6833 15.9082 17.1827 15.8561C17.682 15.804 18.1337 15.9179 18.5917 16.1098C19.0235 16.2906 19.5202 16.5674 20.1104 16.8965L21.7128 17.7897C22.2158 18.07 22.5966 18.5213 22.7137 19.088C22.832 19.6606 22.6575 20.2362 22.2719 20.7092C20.873 22.4256 18.6317 23.5184 16.2805 23.0441C14.8353 22.7526 13.4093 22.2669 11.6846 21.2777C8.21921 19.2904 5.11214 16.1816 3.12706 12.7202C2.13795 10.9955 1.65223 9.56954 1.36069 8.12428C0.886392 5.77306 1.97923 3.53178 3.69559 2.13287C4.16862 1.74733 4.74417 1.57276 5.31677 1.69111ZM13.3707 20.4784C14.5371 21.0532 15.5516 21.3668 16.5771 21.5737C18.2732 21.9159 19.9854 21.1403 21.1092 19.7615C21.2568 19.5805 21.2577 19.4541 21.2447 19.3916C21.2306 19.3232 21.1733 19.2062 20.9825 19.0999L19.416 18.2267C18.7803 17.8724 18.3572 17.6378 18.0123 17.4933C17.6849 17.3562 17.4971 17.3315 17.3382 17.348C17.1794 17.3646 17.0007 17.4276 16.7087 17.6293C16.401 17.8417 16.0354 18.1586 15.4865 18.6365L13.3707 20.4784ZM3.92636 11.0341L5.76835 8.91831C6.24618 8.36944 6.56306 8.00379 6.77553 7.69613C6.97721 7.4041 7.04023 7.2254 7.05679 7.06656C7.07336 6.90773 7.04857 6.71987 6.91148 6.39251C6.76705 6.04764 6.53243 5.62447 6.17812 4.98883L5.30494 3.42232C5.19856 3.23147 5.08158 3.1742 5.01317 3.16006C4.95066 3.14714 4.82429 3.14805 4.64326 3.2956C3.26448 4.41936 2.48894 6.13162 2.83107 7.82767C3.03795 8.85325 3.35164 9.86773 3.92636 11.0341Z"
                        />
                      </svg>

                      <div className="item-content space-y-1">
                        <p className="font-bold">{t("services.phone")}</p>
                        <p>
                          <a
                            href={`tel:${service.phone}`}
                            className="flex items-center flex-wrap gap-x-2 gap-y-1 text-primary"
                            target="_blank"
                            aria-label={`tel ${service.phone}`}
                          >
                            {service.phone}
                            <TbExternalLink size={20} />
                          </a>
                        </p>
                      </div>
                    </div>
                  )}
                  {service.email && (
                    <div className="info-item flex items-start gap-x-3">
                      <svg
                        className="w-6 h-6 flex-shrink-0 text-primary"
                        width="24"
                        height="25"
                        viewBox="0 0 24 25"
                        fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fillRule="evenodd"
                          clipRule="evenodd"
                          d="M14.92 3.19159C12.967 3.14252 11.033 3.14252 9.07999 3.19158L9.02182 3.19304C7.497 3.23133 6.27002 3.26214 5.2867 3.43339C4.2572 3.61268 3.42048 3.95656 2.71362 4.66611C2.00971 5.37269 1.66764 6.19738 1.49176 7.21019C1.32429 8.17456 1.29878 9.37159 1.26719 10.8544L1.26593 10.9132C1.24469 11.9095 1.24469 12.9001 1.26594 13.8964L1.26719 13.9551C1.29879 15.438 1.32429 16.635 1.49176 17.5994C1.66764 18.6122 2.00972 19.4369 2.71362 20.1435C3.42048 20.853 4.2572 21.1969 5.2867 21.3762C6.27001 21.5474 7.49697 21.5782 9.02177 21.6165L9.07999 21.618C11.033 21.6671 12.967 21.6671 14.92 21.618L14.9782 21.6165C16.503 21.5782 17.73 21.5474 18.7133 21.3762C19.7428 21.1969 20.5795 20.853 21.2864 20.1435C21.9903 19.4369 22.3324 18.6122 22.5082 17.5994C22.6757 16.635 22.7012 15.438 22.7328 13.9551L22.7341 13.8964C22.7553 12.9001 22.7553 11.9095 22.7341 10.9132L22.7328 10.8545C22.7012 9.37161 22.6757 8.17458 22.5082 7.2102C22.3324 6.1974 21.9903 5.37271 21.2864 4.66613C20.5795 3.95657 19.7428 3.61269 18.7133 3.4334C17.73 3.26215 16.503 3.23134 14.9782 3.19305L14.92 3.19159ZM9.11766 4.69111C11.0456 4.64267 12.9544 4.64268 14.8823 4.69112C16.479 4.73123 17.5947 4.76117 18.4559 4.91116C19.2835 5.05528 19.7994 5.29889 20.2237 5.72477C20.3977 5.89937 20.5405 6.08733 20.6582 6.30308L14.7173 9.66923C13.4621 10.3805 12.7003 10.6548 12.0001 10.6548C11.2999 10.6548 10.5381 10.3805 9.28285 9.66923L3.34181 6.30299C3.4595 6.08727 3.60237 5.89934 3.77629 5.72475C4.20055 5.29888 4.71652 5.05526 5.54405 4.91114C6.40529 4.76116 7.52099 4.73122 9.11766 4.69111ZM2.92102 7.78861C2.81754 8.58043 2.79468 9.58125 2.76559 10.9452C2.7448 11.9202 2.7448 12.8894 2.7656 13.8644C2.79877 15.4199 2.82385 16.5032 2.96964 17.3427C3.10923 18.1466 3.34907 18.656 3.77629 19.0848C4.20056 19.5107 4.71653 19.7543 5.54406 19.8984C6.4053 20.0484 7.521 20.0783 9.11767 20.1185C11.0456 20.1669 12.9544 20.1669 14.8823 20.1185C16.479 20.0783 17.5947 20.0484 18.4559 19.8984C19.2835 19.7543 19.7994 19.5107 20.2237 19.0848C20.6509 18.656 20.8908 18.1466 21.0304 17.3427C21.1762 16.5032 21.2012 15.4199 21.2344 13.8644C21.2552 12.8894 21.2552 11.9202 21.2344 10.9452C21.2053 9.58132 21.1825 8.58052 21.079 7.78872L15.4568 10.9743C14.1635 11.7071 13.1126 12.1548 12.0001 12.1548C10.8876 12.1548 9.83667 11.7071 8.54339 10.9743L2.92102 7.78861Z"
                        />
                      </svg>

                      <div className="item-content space-y-1">
                        <p className="font-bold">{t("services.email")}</p>
                        <p>
                          <a
                            href={`mailto:${service.email}`}
                            className="flex items-center flex-wrap gap-x-2 gap-y-1 text-primary"
                            target="_blank"
                            aria-label={`mailto ${service.email}`}
                          >
                            {service.email}
                            <TbExternalLink size={20} />
                          </a>
                        </p>
                      </div>
                    </div>
                  )}
                  {service.userManual && (
                    <div className="info-item flex items-start gap-x-3">
                      <a
                        href={service.userManual}
                        download
                        aria-label={t("services.userManual")}
                      >
                        <Button
                          className="!bg-[#F3F4F6] border-none"
                          type="default"
                        >
                          {t("services.userManual")}
                        </Button>
                      </a>
                    </div>
                  )}
                </div>
                
                <Divider className="my-6 divider-row border-[#D2D6DB]" />

                <div className="info-group space-y-4">
                  {service?.mobileApp?.length > 0 && (
                    <div className="info-item flex items-start gap-x-3">
                      <div className="item-content space-y-1">
                        <p className="font-bold text-lg">
                          {t("services.mobileApp")}
                        </p>
                        <div className="flex items-center flex-wrap gap-3">
                          {service.mobileApp.map(
                            (item: any, idx: any) =>
                              item.logo && (
                                <a
                                  href={item.link}
                                  key={`mobileApps-${service.id}-${idx}`}
                                  target="_blank"
                                >
                                  <Image
                                    className="max-w-[120px] max-h-[40px] object-contain"
                                    src={item.logo}
                                    width={120}
                                    height={40}
                                    alt={item.alt || ""}
                                  />
                                </a>
                              )
                          )}
                        </div>
                      </div>
                    </div>
                  )}
                </div>

              </div>
            </aside>
          </div>
        </div>
      </div>
      {service?.relatedService?.list?.length > 0 && (
        <section className="section-style py-10 overflow-hidden bg-[#F7FDF9]">
          <div className="container">
            <div className="section-head mb-8">
              <div className="flex items-center justify-between gap-x-6">
                <h2 className="text-3xl font-bold">
                  {service.relatedService.title}
                </h2>
                {service.relatedService.main_action?.link && (
                  <a
                    href={service.relatedService.main_action?.link}
                    aria-label={service.relatedService.main_action?.title}
                  >
                    <Button type="default">
                      {service.relatedService.main_action?.title}
                    </Button>
                  </a>
                )}
              </div>
              {service.relatedService.description && (
                <p className="mt-6">{service.relatedService.description}</p>
              )}
            </div>
            <div
              className={`slider-container services-slider-container relative`}
            >
              <Swiper
                dir={locale === "ar" ? "rtl" : "ltr"}
                modules={[Pagination]}
                spaceBetween={16}
                loop={true}
                pagination={{
                  el: `.services-slider-container .theme-pagination`,
                  clickable: true,
                }}
                grabCursor={true}
                breakpoints={{
                  0: {
                    slidesPerView: 1,
                  },
                  640: {
                    slidesPerView: 2,
                  },
                  768: {
                    slidesPerView: 3,
                  },
                  1024: {
                    slidesPerView: 4,
                  },
                }}
              >
                {service.relatedService.list?.map((item: any, idx: any) => (
                  <SwiperSlide
                    key={`services-slider-item-${idx}`}
                    className="!h-auto"
                  >
                    <ServiceCard item={item} />
                  </SwiperSlide>
                ))}
              </Swiper>
              <div className="theme-pagination mt-6 flex justify-center py-2" />
            </div>
          </div>
        </section>
      )}
    </>
  ) : (
    <div className="py-10">
      <Empty description={t("no-result-found")} />
    </div>
  );
}

export default ServicePage;
