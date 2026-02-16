"use client";
import { Button } from "antd";
import Image from "next/image";
import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Navigation } from "swiper/modules";
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/navigation";
import {
  MdOutlineKeyboardArrowLeft,
  MdOutlineKeyboardArrowRight,
} from "react-icons/md";
import { useLocale, useTranslations } from "next-intl";
import ServiceCard from "@/components/site/ServiceCard";

function SiteHomePage({
  landingPage,
  services,
}: {
  landingPage: any;
  services?: any;
}) {
  const locale = useLocale();
  const t = useTranslations();

  return (
    <div className="home_content">
      <div
        id="dga-info"
        data-enabled={landingPage?.government_verification_banner_enabled}
        data-url={landingPage?.dga_certificate_url}
        data-number={landingPage?.dga_registration_number}
      />

      {landingPage?.data?.map((section: any, index: any) => {
        if (section.type === "banner" && section?.data?.length) {
          return (
            <section
              className="section-style overflow-hidden"
              key={`${section.type}-${index}`}
            >
              <div
                className={`slider-container banners-slider-container-${index} relative`}
              >
                <Swiper
                  dir={locale === "ar" ? "rtl" : "ltr"}
                  modules={[Pagination]}
                  slidesPerView={1}
                  loop={true}
                  pagination={{
                    el: `.banners-slider-container-${index} .theme-pagination`,
                    clickable: true,
                  }}
                  grabCursor={true}
                >
                  {section?.data?.map((item: any, idx: any) => (
                    <SwiperSlide
                      className="!h-auto"
                      key={`${section.type}-${index}-data-${idx}`}
                    >
                      <div className="slide-item min-h-[491px] pt-[141px] pb-[92px] flex item-center bg-[#F3F4F6] h-full">
                        <div className="container">
                          <div className="lg:grid lg:grid-cols-12 lg:gap-x-8 lg:items-center">
                            <div className="slide-content space-y-6 lg:col-span-7">
                              <h2 className="font-bold text-6xl">
                                {item.title}
                              </h2>
                              <p className="text-xl">{item.description}</p>
                              {item.main_action && (
                                <div className="block">
                                  <a
                                    href={item.main_action?.link}
                                    aria-label={item.main_action?.title}
                                  >
                                    <Button type="primary">
                                      {item.main_action?.title}
                                    </Button>
                                  </a>
                                </div>
                              )}
                            </div>
                            <div className="slide-img hidden lg:flex lg:items-center lg:col-span-5">
                              <Image
                                className="object-contain h-[300px]"
                                src={item.image}
                                width={500}
                                height={300}
                                alt={item.title}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </SwiperSlide>
                  ))}
                </Swiper>
                <div className="theme-pagination swiper-pagination swiper-pagination-horizontal !bottom-[40px]" />
              </div>
            </section>
          );
        }
        return null;
      })}

      {services?.length > 0 && (
        <section className="section-style py-10 overflow-hidden">
          <div className="container">
            <div className="section-head mb-8">
              <div className="flex items-center justify-between gap-x-6">
                <h2 className="text-3xl font-bold">{t("services.mostUsed")}</h2>
              </div>
            </div>
            <div
              className={`slider-container most-used-services-slider-container relative`}
            >
              <Swiper
                dir={locale === "ar" ? "rtl" : "ltr"}
                modules={[Pagination]}
                spaceBetween={16}
                loop={true}
                pagination={{
                  el: `.most-used-services-slider-container .theme-pagination`,
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
                {services?.map((item: any, idx: any) => (
                  <SwiperSlide
                    key={`most-used-service-${idx}`}
                    className="!h-auto"
                  >
                    <ServiceCard item={item} detailsCard={true} />
                  </SwiperSlide>
                ))}
              </Swiper>
              <div className="theme-pagination mt-6 flex justify-center py-2" />
            </div>
          </div>
        </section>
      )}

      {landingPage?.data?.map((section: any, index: any) => {
        if (section.type === "about" && section?.data?.list?.length) {
          return (
            <section
              className="section-style py-10 overflow-hidden"
              key={`${section.type}-${index}`}
            >
              <div className="container">
                <div className="section-head mb-8">
                  <div className="flex items-center justify-between gap-x-6">
                    <h2 className="text-3xl font-bold">{section.data.title}</h2>
                    {section.data?.main_action?.link && (
                      <a
                        href={section.data.main_action?.link}
                        aria-label={section.data.main_action?.title}
                      >
                        <Button type="default">
                          {section.data.main_action?.title}
                        </Button>
                      </a>
                    )}
                  </div>
                  {section.data.description && (
                    <p className="mt-6">{section.data.description}</p>
                  )}
                </div>
                <div className="flex flex-wrap justify-center gap-8 max-w-[992px] mx-auto">
                  {section?.data?.list?.map((item: any, idx: any) => (
                    <div
                      className="flex flex-col items-center gap-y-6 p-4 basis-1/2 max-w-[calc(50%-1.5rem)] lg:basis-1/4 lg:max-w-[calc(25%-1.5rem)]"
                      key={`${section.type}-${index}-data-${idx}`}
                    >
                      <div className="w-14 h-14 rounded-full">
                        <Image
                          src={item.icon}
                          width={56}
                          height={56}
                          className="object-contain w-full h-full"
                          alt="test"
                        />
                      </div>
                      <div className="text-center">
                        <p className="text-primary text-5xl">{item.number}</p>
                        <p className="">{item.title}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </section>
          );
        }
        if (section.type === "services" && section?.data?.list?.length) {
          return (
            <section
              className="section-style py-10 overflow-hidden bg-[#f9fafb]"
              key={`${section.type}-${index}`}
            >
              <div className="container">
                <div className="section-head mb-8">
                  <div className="flex items-center justify-between gap-x-6">
                    <h2 className="text-3xl font-bold">{section.data.title}</h2>
                    {section?.data?.main_action?.link && (
                      <a
                        href={section.data.main_action?.link}
                        aria-label={section.data.main_action?.title}
                      >
                        <Button type="default">
                          {section.data.main_action?.title}
                        </Button>
                      </a>
                    )}
                  </div>
                  {section.data.description && (
                    <p className="mt-6">{section.data.description}</p>
                  )}
                </div>
                <div
                  className={`slider-container services-slider-container-${index} relative`}
                >
                  <Swiper
                    dir={locale === "ar" ? "rtl" : "ltr"}
                    modules={[Pagination]}
                    spaceBetween={16}
                    loop={true}
                    pagination={{
                      el: `.services-slider-container-${index} .theme-pagination`,
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
                    {section?.data?.list?.map((item: any, idx: any) => (
                      <SwiperSlide
                        key={`${section.type}-${index}-data-${idx}`}
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
          );
        }
        if (section.type === "partners" && section?.data?.logos?.length) {
          return (
            <section
              className="section-style py-10 overflow-hidden"
              key={`${section.type}-${index}`}
            >
              <div className="container">
                <div className="section-head mb-8">
                  <div className="flex items-center justify-between gap-x-6">
                    <h2 className="text-3xl font-bold">{section.data.title}</h2>
                  </div>
                </div>
                <div
                  className={`slider-container partner-slider-container-${index} relative lg:px-[3.25rem]`}
                >
                  <Swiper
                    dir={locale === "ar" ? "rtl" : "ltr"}
                    modules={[Navigation]}
                    navigation={{
                      nextEl: `.partner-slider-container-${index} .custom-next`,
                      prevEl: `.partner-slider-container-${index} .custom-prev`,
                    }}
                    spaceBetween={14}
                    loop={true}
                    grabCursor={true}
                    breakpoints={{
                      0: {
                        slidesPerView: 2,
                      },
                      640: {
                        slidesPerView: 3,
                      },
                      768: {
                        slidesPerView: 5,
                      },
                      1024: {
                        slidesPerView: 7,
                      },
                      1280: {
                        slidesPerView: 9,
                      },
                    }}
                  >
                    {section?.data?.logos?.map((item: any, idx: any) => (
                      <SwiperSlide key={`${section.type}-${index}-data-${idx}`}>
                        <div className="flex items-center justify-center text-center p-5 rounded-2xl bg-card border border-[#D2D6DB]">
                          <Image
                            src={item.image}
                            width={56}
                            height={56}
                            className="object-contain h-[80px]"
                            alt={item.title}
                          />
                        </div>
                      </SwiperSlide>
                    ))}
                  </Swiper>
                  <button className="custom-prev absolute start-[-.75rem] lg:start-0 top-1/2 -translate-y-1/2 bg-[#F3F4F6] rounded-md w-8 h-8 lg:w-10 lg:h-10 flex items-center justify-center z-[10] hover:bg-primary hover:text-white transition text-xl lg:text-2xl">
                    <MdOutlineKeyboardArrowLeft className="rtl:rotate-180" />
                  </button>
                  <button className="custom-next absolute end-[-.75rem] lg:end-0 top-1/2 -translate-y-1/2 bg-[#F3F4F6] rounded-md w-8 h-8 lg:w-10 lg:h-10 flex items-center justify-center z-[10] hover:bg-primary hover:text-white transition text-xl lg:text-2xl">
                    <MdOutlineKeyboardArrowRight className="rtl:rotate-180" />
                  </button>
                </div>
              </div>
            </section>
          );
        }
        return null;
      })}
    </div>
  );
}

export default SiteHomePage;
