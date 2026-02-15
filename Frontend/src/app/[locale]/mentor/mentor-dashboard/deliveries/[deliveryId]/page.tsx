"use client";
import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { Link } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import { Breadcrumb, Button, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import React from "react";
import { FaRegUser } from "react-icons/fa";
import { FiArrowLeft, FiArrowRight } from "react-icons/fi";

function DeliveryDetailsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { deliveryId } = useParams();

  // get delivery details
  const { data: delivery, isLoading } = useQuery({
    queryKey: ["mentor-delivery", locale, deliveryId],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(
          `/mentors/teams/${deliveryId}`
        );
        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
  });

  return (
    <div className="flex flex-col gap-y-4">
      {isLoading ? (
        <Spin className="flex justify-center w-full" />
      ) : delivery ? (
        <>
          <Breadcrumb
            items={[
              {
                title: (
                  <Link href={`/mentor/mentor-dashboard/deliveries`}>
                    {t("deliveries")}
                  </Link>
                ),
              },
              {
                title: delivery.name,
              },
            ]}
          />

          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 flex-wrap relative px-5 py-6 bg-card rounded-xl">
            <div className="flex gap-x-2 items-center">
              {delivery.logo ? (
                <Image
                  src={delivery.logo}
                  className="w-12 h-12 rounded-full object-cover flex-shrink-0"
                  width={48}
                  height={48}
                  alt={delivery.name}
                />
              ) : (
                <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                  <FaRegUser className="text-white b" />
                </div>
              )}
              <div className="flex flex-col gap-y-2">
                <h2 className="font-bold text-sm m-0">{delivery.name}</h2>
                <p className="text-[#667084] text-sm empty:hidden"></p>
              </div>
            </div>
            <p className="font-medium m-0 empty:hidden">{delivery?.track?.name}</p>
          </div>
          {delivery.projects?.length > 0 && (
            <div className="grid grid-cols-1 xl:grid-cols-3 sm:grid-cols-2 gap-x-6 gap-y-4">
              {delivery.projects.map((project: any) => (
                <div
                  key={project.id}
                  className="flex flex-col bg-card p-5 rounded-xl space-y-4"
                >
                  <div className="relative">
                    <Image
                      src={"/project.png"}
                      alt={project.project_name}
                      className="w-full h-[200px] object-cover rounded-lg"
                      width={300}
                      height={200}
                    />
                  </div>

                  <div className="space-y-2">
                    <h3 className="text-base font-bold">
                      {project.project_name}
                    </h3>
                    <p className="text-[#667084] text-sm empty:hidden">
                      {project?.track?.name}
                    </p>
                  </div>

                  <div className="flex flex-1 items-end">
                    <Link
                      href={`/mentor/mentor-dashboard/deliveries/${deliveryId}/projects/${project.id}`}
                      className="w-full"
                    >
                      <Button
                        variant="filled"
                        type="default"
                        className="w-full"
                      >
                        {t("show-details")}
                      </Button>
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      ) : (
        <Empty description={t("no-result-found")} />
      )}
    </div>
  );
}

export default DeliveryDetailsPage;
