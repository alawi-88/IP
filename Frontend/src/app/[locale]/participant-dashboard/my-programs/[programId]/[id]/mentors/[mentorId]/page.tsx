"use client";
import axiosInstance, { APIError } from "@/axios";
import Empty from "@/components/Empty";
import { Mentor } from "@/lib/interfaces";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Button,
  Calendar,
  Divider,
  Flex,
  Input,
  message,
  Radio,
  Select,
  Spin,
} from "antd";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import React, { useEffect, useState } from "react";
import {
  FaRegUser,
  FaLinkedin,
  FaFacebookSquare,
  FaInstagram,
} from "react-icons/fa";
import dayjs, { Dayjs } from "dayjs";
import "dayjs/locale/en";
import { useUserStore } from "@/store/user";
import FeedbackModal from "@/components/feedback-modal/FeedbackModal";
import { useRouter } from "@/i18n/routing";

export default function MentorDetailsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { programId, id, mentorId } = useParams<{
    programId: string;
    id: string;
    mentorId: string;
  }>();
  const [bookingData, setBookingData] = useState<{
    selectedDay: Dayjs;
    selectedTime: string;
    description: string;
  }>({
    selectedDay: dayjs(),
    selectedTime: "",
    description: "",
  });
  const [step, setStep] = useState(1);
  const [successModal, setSuccessModal] = useState(false);
  const [messageApi, contextHolder] = message.useMessage();
  const queryClient = useQueryClient();
  const user = useUserStore((state) => state.participant);
  const router = useRouter();

  //get mentor info
  const { data: mentor, isLoading } = useQuery<Mentor>({
    queryKey: ["mentor", mentorId],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/mentors/${mentorId}`,
        {
          params: { application_id: id },
        }
      );
      return response.data.data;
    },
  });

  //get available time slots
  const {
    data: slotsData,
    isFetching: slotsLoading,
    refetch: refetchSlots,
  } = useQuery({
    queryKey: [
      "slots",
      locale,
      mentorId,
      bookingData.selectedDay.format("YYYY-MM-DD"),
    ],
    queryFn: async () => {
      const date = bookingData.selectedDay;
      const formattedDate = date.format("YYYY-MM-DD");
      const dayName = date.locale("en").format("dddd");
      const response = await axiosInstance.get(
        `/participants/mentors/${mentorId}/available-slots`,
        {
          params: {
            application_id: id,
            date: formattedDate,
            day: dayName,
          },
        }
      );
      return response.data.data || [];
    },
    enabled: !!bookingData.selectedDay,
    staleTime:0,
  });
  // const {
  //   mutate: fetchSlots,
  //   data: slotsData,
  //   isPending: slotsLoading,
  // } = useMutation({
  //   mutationFn: async (date: Dayjs) => {
  //     const formattedDate = date.format("YYYY-MM-DD");
  //     const dayName = date.locale("en").format("dddd");
  //     const response = await axiosInstance.get(
  //       `/participants/mentors/${mentorId}/available-slots`,
  //       {
  //         params: {
  //           application_id: id,
  //           date: formattedDate,
  //           day: dayName,
  //         },
  //       }
  //     );
  //     return response.data.data || [];
  //   },
  // });

  // book session
  const { mutate: bookSessionMutate, isPending: bookSessionLoading } =
    useMutation({
      mutationFn: async (data: any) => {
        const body = {
          application_id: id,
          description: bookingData.description,
          scheduled_at: `${bookingData.selectedDay.format("YYYY-MM-DD")} ${
            bookingData.selectedTime.split(" ")[0]
          }`,
          duration_minutes: 60,
        };
        const response = await axiosInstance.post(
          `/participants/mentors/${mentorId}/mentor-sessions`,
          body
        );
        return response.data;
      },
      onSuccess: (data) => {
        queryClient.invalidateQueries({
          queryKey: ["mentors-sessions", id, locale],
          exact: false,
        });
        queryClient.invalidateQueries({
          queryKey: [
            "slots",
            locale,
            mentorId,
            bookingData.selectedDay.format("YYYY-MM-DD"),
          ],
          exact: false,
        });
        setSuccessModal(true);
        setTimeout(() => {
          document
            .querySelector("main")
            ?.scrollTo({ top: 0, behavior: "smooth" });
        }, 0);
      },
      onError: (error: APIError) => {
        if (error.response.data.message) {
          messageApi.error(error.response.data.message);
        }
      },
    });

  //fetch today's slots on mount
  // useEffect(() => {
  //   fetchSlots(dayjs());
  // }, [fetchSlots]);

  if (isLoading) {
    return <Spin />;
  }
  if (!mentor) {
    return <Empty description={t("no-result-found")} />;
  }

  return (
    <>
      {contextHolder}
      {step === 1 && (
        <section className="mentor-details-section bg-card rounded-2xl">
          <div className="dashboard-card gap-y-0 h-full rounded-[0px] bg-[transparent]">
            <p className="text-lg font-bold">{t("mentor.book-session")}</p>
            <Divider className="bg-[#DEE1E6] mb-0" />
          </div>

          <div className="grid lg:grid-cols-12">
            <div className="lg:col-span-5 xl:col-span-4 border border-y-0 border-s-0 max-lg:border-l-0 border-[#DEE1E6]">
              <div className="dashboard-card gap-y-0 h-full rounded-[0px] bg-[transparent] lg:py-0">
                <div className="flex flex-col items-center justify-center">
                  <div className="mentor-pic mb-4">
                    {mentor.image ? (
                      <Image
                        src={mentor.image}
                        className="w-32 h-32 rounded-full object-cover flex-shrink-0"
                        width={128}
                        height={128}
                        alt={mentor.name}
                      />
                    ) : (
                      <div className="w-32 h-32 rounded-full bg-primary flex items-center justify-center text-4xl flex-shrink-0">
                        <FaRegUser className="text-white b" />
                      </div>
                    )}
                  </div>
                  <div className="space-y-3 text-center">
                    <div className="space-y-1">
                      <p className="font-bold">{mentor.name}</p>
                      <p className="font-medium text-[#667084]">
                        {mentor.profession}
                      </p>
                    </div>
                    <div className="flex items-center justify-center gap-x-3 gap-y-2 flex-wrap">
                      {mentor.linkedin && (
                        <a
                          className="hover:text-primary transition"
                          href={mentor.linkedin}
                          target="_blank"
                        >
                          <FaLinkedin size={24} />
                        </a>
                      )}
                      {mentor.facebook && (
                        <a
                          className="hover:text-primary transition"
                          href={mentor.facebook}
                          target="_blank"
                        >
                          <FaFacebookSquare size={24} />
                        </a>
                      )}
                      {mentor.instagram && (
                        <a
                          className="hover:text-primary transition"
                          href={mentor.instagram}
                          target="_blank"
                        >
                          <FaInstagram size={24} />
                        </a>
                      )}
                    </div>
                  </div>
                </div>
                <Divider className="bg-[#DEE1E6] my-4" />
                <p className="font-medium text-[#667084]">
                  {mentor.brief || mentor.profession || ""}
                </p>
                <Divider className="bg-[#DEE1E6] mb-0 lg:hidden" />
              </div>
            </div>
            <div className="lg:col-span-7 xl:col-span-8">
              <div className="dashboard-card gap-y-0 h-full rounded-[0px] bg-[transparent] lg:py-0">
                <div className="calender-grid">
                  <div className="calender-wrapper">
                    <p className="font-bold mb-3">
                      {t("mentor.date")}{" "}
                      {bookingData.selectedDay && (
                        <span className="text-[#667084]">
                          - {bookingData.selectedDay.format("dddd, DD MMMM")}
                        </span>
                      )}
                    </p>
                    <div className="inline-calender">
                      <Calendar
                        fullscreen={false}
                        value={bookingData.selectedDay || undefined}
                        disabledDate={(current) =>
                          current && current < dayjs().startOf("day")
                        }
                        onSelect={(date, info) => {
                          setBookingData((prev) => ({
                            ...prev,
                            selectedDay: date,
                            selectedTime: "",
                          }));
                          refetchSlots();
                          // fetchSlots(date);
                        }}
                      />
                    </div>
                  </div>

                  <Divider className="bg-[#DEE1E6] my-4" />
                  <div className="slots-wrapper">
                    <p className="font-bold mb-3">{t("mentor.time")}</p>
                    <div className="slot-select h-12">
                      {slotsLoading ? (
                        <Spin />
                      ) : slotsData && slotsData.length > 0 ? (
                        <Select
                          placeholder={t("select")}
                          className="w-full"
                          value={bookingData.selectedTime || undefined}
                          options={slotsData?.map((slot: any) => ({
                            value: `${slot.start_time} ${slot.end_time}`,
                            label: `${dayjs(slot.start_time, "HH:mm").format(
                              "hh:mm A"
                            )} - ${dayjs(slot.end_time, "HH:mm").format(
                              "hh:mm A"
                            )}`,
                          }))}
                          loading={slotsLoading}
                          disabled={slotsLoading}
                          onChange={(value) =>
                            setBookingData((prev) => ({
                              ...prev,
                              selectedTime: value,
                            }))
                          }
                          notFoundContent={
                            <p className="text-[#667084] font-bold text-center py-4">
                              {t("mentor.no-available-slots")}
                            </p>
                          }
                          allowClear
                        />
                      ) : (
                        <p className="text-[#667084] font-bold">
                          {t("mentor.no-available-slots")}
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="dashboard-card gap-y-0 h-full rounded-[0px] bg-[transparent]">
            <Divider className="bg-[#DEE1E6] mt-0" />
            <Button
              className="w-fit"
              size="large"
              type="primary"
              disabled={!bookingData.selectedDay || !bookingData.selectedTime}
              onClick={() => setStep(2)}
            >
              {t("next")}
            </Button>
          </div>
        </section>
      )}

      {step === 2 && (
        <>
          <section className="confirm-book-section">
            <div className="dashboard-card gap-y-0">
              <p className="text-lg font-bold">{t("mentor.book-session")}</p>
              <Divider className="bg-[#DEE1E6]" />
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-8">
                <div className="flex flex-col gap-y-2">
                  <label className="font-normal text-sm text-secondary">
                    {t("full-name")}
                  </label>
                  <p className="font-medium text-base text-foreground">
                    {user?.name}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <label className="font-normal text-sm text-secondary">
                    {t("email")}
                  </label>
                  <p className="font-medium text-base text-foreground break-all">
                    {user?.email}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <label className="font-normal text-sm text-secondary">
                    {t("program")}
                  </label>
                  <p className="font-medium text-base text-foreground break-all">
                    {mentor?.program?.title}
                  </p>
                </div>
                <div className="flex flex-col gap-y-2">
                  <label className="font-normal text-sm text-secondary">
                    {t("mentor.date")}
                  </label>
                  <p className="font-medium text-base text-foreground break-all">
                    <span className="inline-block me-3">
                      {bookingData.selectedDay.format("dddd, DD MMMM")}
                    </span>
                    <span className="inline-block">
                      {bookingData.selectedTime
                        ?.split(" ")
                        ?.map((time) => dayjs(time, "HH:mm").format("hh:mm A"))
                        ?.join(" - ")}
                    </span>
                  </p>
                </div>
                <div className="col-span-full max-w-[700px]">
                  <label
                    className="block text-sm font-medium mb-3"
                    htmlFor="description"
                  >
                    {t("mentor.book-prepare-message")}
                  </label>
                  <Input.TextArea
                    name="description"
                    id="description"
                    value={bookingData.description}
                    rows={3}
                    placeholder={t("enter-message")}
                    onChange={(e) =>
                      setBookingData((prev) => ({
                        ...prev,
                        description: e.target.value,
                      }))
                    }
                  />
                </div>
              </div>
              <Divider className="bg-[#DEE1E6] mt-8" />
              <div className="flex gap-4 flex-wrap">
                <Button
                  size="large"
                  type="primary"
                  onClick={bookSessionMutate}
                  loading={bookSessionLoading}
                >
                  {t("confirm")}
                </Button>
                <Button size="large" type="default" onClick={() => setStep(1)}>
                  {t("back")}
                </Button>
              </div>
            </div>
          </section>

          <FeedbackModal
            openModal={successModal}
            title={t("mentor.session-book-success")}
            subtitle={t("mentor.session-book-invitation")}
            btnLabel={t("mentor.back-to-sessions")}
            type="success"
            onBtnClick={() => {
              queryClient.invalidateQueries({
                queryKey: ["mentors", id, locale],
                exact: false,
              });
              router.push(
                `/participant-dashboard/my-programs/${programId}/${id}/mentors-sessions`
              );
            }}
          />
        </>
      )}
    </>
  );
}
