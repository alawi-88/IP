"use client";

import axiosInstance, { APIError } from "@/axios";
import Empty from "@/components/Empty";
import FilterResultsModal from "@/components/FilterResultsModal";
import { Link } from "@/i18n/routing";
import { Mentor, Session } from "@/lib/interfaces";
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import {
  Button,
  DatePicker,
  Divider,
  Form,
  Select,
  Spin,
  Input,
  Modal,
  message,
  Calendar,
} from "antd";
import { useForm } from "antd/es/form/Form";
import dayjs, { Dayjs } from "dayjs";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { FaRegUser } from "react-icons/fa";
import { IoCalendarOutline } from "react-icons/io5";
import { IoVideocamOutline } from "react-icons/io5";
import { IoTimeOutline } from "react-icons/io5";
import { FaStar } from "react-icons/fa6";

function MentorSessionsPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = useForm();
  const [messageApi, contextHolder] = message.useMessage();
  const queryClient = useQueryClient();
  const { programId, id } = useParams<{
    programId: string;
    id: string;
  }>();
  const [selectedSession, setSelectedSession] = useState<Session | null>(null);
  const [bookingData, setBookingData] = useState<{
    selectedDay: Dayjs | undefined;
    selectedTime: string | undefined;
  }>({
    selectedDay: undefined,
    selectedTime: undefined,
  });
  const [cancelModalOpen, setCancelModalOpen] = useState(false);
  const [modifyBookingModalOpen, setModifyBookingModalOpen] = useState(false);
  const [query, setQuery] = useState({});

  //get sessions
  const { data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery<{
      data: Session[];
      pagination: any;
    }>({
      queryKey: ["mentors-sessions", id, locale, query],
      queryFn: async ({ pageParam = undefined }) => {
        const response = await axiosInstance.get(
          "/participants/mentor-sessions",
          {
            params: {
              application_id: id,
              page: pageParam,
              ...query,
            },
          }
        );
        return response.data;
      },
      getNextPageParam: (lastPage) => {
        const { current_page, last_page } = lastPage.pagination;
        return current_page < last_page ? current_page + 1 : undefined;
      },
      initialPageParam: undefined,
    });

  const sessions = data?.pages.flatMap((page) => page.data) ?? [];

  //get available time slots
  const {
    data: slotsData,
    isFetching: slotsLoading,
    refetch: refetchSlots,
  } = useQuery({
    queryKey: [
      "slots",
      locale,
      selectedSession?.mentor.id,
      bookingData?.selectedDay?.format("YYYY-MM-DD"),
    ],
    queryFn: async () => {
      const date = bookingData.selectedDay;
      if (!date) {
        return;
      }
      const formattedDate = date.format("YYYY-MM-DD");
      const dayName = date.locale("en").format("dddd");
      const response = await axiosInstance.get(
        `/participants/mentors/${selectedSession?.mentor.id}/available-slots`,
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
    enabled: !!(selectedSession && bookingData.selectedDay),
    staleTime: 0,
  });

  // cancel session
  const { mutate: cancelSessionMutate, isPending: isCancelSessionsPending } =
    useMutation({
      mutationFn: async (data: any) => {
        return axiosInstance.post(
          `/participants/mentor-sessions/${selectedSession?.id}/cancel`,
          data
        );
      },
      onSuccess: (response, variables) => {
        queryClient.invalidateQueries({
          queryKey: ["mentors-sessions", id, locale],
          exact: false,
        });
        setCancelModalOpen(false);
        setSelectedSession(null);
        form.resetFields();
        messageApi.success(response.data.message);
      },
      onError: (error: APIError) => {
        messageApi.error(error.response.data.message);
        console.log(error);
      },
    });

  // reschedule session
  const {
    mutate: rescheduleSessionMutate,
    isPending: rescheduleSessionLoading,
  } = useMutation({
    mutationFn: async (data: any) => {
      if (!bookingData?.selectedTime) {
        messageApi.error(`${t("mentor.reschedule-time required")}`);
        throw new Error("time required");
      }
      const body = {
        scheduled_at: `${bookingData?.selectedDay?.format("YYYY-MM-DD")} ${
          bookingData?.selectedTime?.split(" ")[0]
        }`,
      };
      const response = await axiosInstance.put(
        `/participants/mentor-sessions/${selectedSession?.id}/reschedule`,
        body
      );
      return response.data;
    },
    onSuccess: (response) => {
      queryClient.invalidateQueries({
        queryKey: ["mentors-sessions", id, locale],
        exact: false,
      });
      queryClient.invalidateQueries({
        queryKey: [
          "slots",
          locale,
          selectedSession?.mentor.id,
          bookingData?.selectedDay?.format("YYYY-MM-DD"),
        ],
        exact: false,
      });

      setModifyBookingModalOpen(false);
      setSelectedSession(null);
      setBookingData({
        selectedDay: undefined,
        selectedTime: undefined,
      });
      console.log(response);
      messageApi.success(response.message);
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  const onSubmit = (values: any) => {
    setQuery({ status: values.status === "all" ? undefined : values.status });
  };

  useEffect(() => {
    if (selectedSession?.scheduled_at) {
      setBookingData({
        selectedDay: dayjs(selectedSession.scheduled_at),
        selectedTime: undefined,
      });
    } else {
      setBookingData({
        selectedDay: undefined,
        selectedTime: undefined,
      });
    }
  }, [selectedSession]);

  return (
    <>
      {contextHolder}
      <div className="flex items-center justify-between flex-wrap gap-x-2 gap-y-3">
        <div className="tabs-link flex flex-wrap gap-x-2 gap-y-3">
          <Link
            href={`/participant-dashboard/my-programs/${programId}/${id}/mentors`}
          >
            <Button type="default">{t("mentors")}</Button>
          </Link>
          <Link
            href={`/participant-dashboard/my-programs/${programId}/${id}/mentors-sessions`}
          >
            <Button type="primary">{t("mentor.sessions")}</Button>
          </Link>
        </div>
        <FilterResultsModal form={form} filterResults={onSubmit}>
          <Form.Item label={t("status")} name={"status"} initialValue={"all"}>
            <Select
              placeholder={t("choose")}
              optionFilterProp="children"
              options={[
                {
                  label: t("all"),
                  value: "all",
                },
                {
                  label: t("mentor.upcoming"),
                  value: "scheduled",
                },
                {
                  label: t("mentor.in_progress"),
                  value: "in_progress",
                },
                {
                  label: t("mentor.completed"),
                  value: "completed",
                },
                {
                  label: t("mentor.cancelled"),
                  value: "cancelled",
                },
                {
                  label: t("mentor.no_show"),
                  value: "no_show",
                },
              ]}
            />
          </Form.Item>
        </FilterResultsModal>
      </div>
      {isLoading ? (
        <Spin />
      ) : sessions && sessions.length > 0 ? (
        <div className="grid grid-cols-1 sm:[grid-auto-rows:1frs] sm:grid-cols-2 xl:grid-cols-3 gap-4">
          {sessions.map((session: Session) => (
            <div
              key={session.id}
              className="px-5 py-4 bg-card rounded-xl flex flex-col h-full"
            >
              <div className="flex items-start justify-between gap-y-2 gap-x-4 relative  mb-6">
                <div className="flex gap-x-2 ">
                  {session.mentor.image ? (
                    <Image
                      src={session.mentor.image}
                      className="w-12 h-12 rounded-full object-cover flex-shrink-0"
                      width={48}
                      height={48}
                      alt={session.mentor.name}
                    />
                  ) : (
                    <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center  flex-shrink-0">
                      <FaRegUser className="text-white b" />
                    </div>
                  )}
                  <div className="flex flex-col gap-y-2">
                    <h3 className="font-bold text-sm m-0">
                      {session.mentor.name}
                    </h3>
                    <p className="text-[#667084] text-sm">
                      {session.mentor.profession}
                    </p>
                  </div>
                </div>
                <div
                  className={`relative end-0 top-0 z-1 px-4 py-2 text-sm font-medium rounded-md ${
                    session.status === "scheduled" ||
                    session.status === "confirmed"
                      ? "bg-[#E1F7F6] text-[#08BCB8]"
                      : session.status === "cancelled"
                      ? "bg-[#FDE8EC] text-[#F13C61]"
                      : "bg-[#F3F4F6]"
                  }`}
                >
                  {t(`mentor.${session.status}`)}
                </div>
              </div>

              <div className="space-y-3">
                <div className="flex items-center gap-2">
                  <IoCalendarOutline size={24} />
                  <p className="text-[#626262] font-medium">
                    <span className="inline-block">
                      {dayjs(session.scheduled_at).format("dddd, DD MMMM")}
                    </span>{" "}
                    <span className="inline-block">
                      - {dayjs(session.scheduled_at).format("hh:mm A")}
                    </span>
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <IoTimeOutline size={24} />
                  <p className="text-[#626262] font-medium">
                    <span className="inline-block">
                      {session.duration_minutes} {t("mentor.minute")}
                    </span>
                  </p>
                </div>
              </div>

              <div className="mt-auto">
                {session.is_upcoming && (
                  <>
                    <Divider className="bg-[#DEE1E6] my-4" />
                    <div className="flex gap-x-4 gap-y-2 ">
                      <Button
                        className="w-full min-w-auto"
                        type="primary"
                        onClick={() => {
                          setSelectedSession(session);
                          setModifyBookingModalOpen(true);
                        }}
                      >
                        {t("mentor.reschedule")}
                      </Button>
                      <Button
                        className="w-full min-w-auto"
                        type="default"
                        onClick={() => {
                          setSelectedSession(session);
                          setCancelModalOpen(true);
                        }}
                      >
                        {t("mentor.cancel-session")}
                      </Button>
                    </div>
                  </>
                )}
                {session.is_in_progress && session.join_url && (
                  <div className="card-actions flex gap-x-4 gap-y-2 mt-4">
                    <a href={session.join_url} target="_blank">
                      <Button
                        className="min-w-auto border border-[#CCCFD6]"
                        color="default"
                        variant="filled"
                        icon={<IoVideocamOutline size={20} />}
                      >
                        {t("mentor.join-meeting")}
                      </Button>
                    </a>
                  </div>
                )}
                {session.is_cancelled && (
                  <>
                    {(session.cancellation_reason ||
                      session.declined_reason) && (
                      <>
                        <Divider className="bg-[#DEE1E6] my-4" />
                        <div className="space-y-1">
                          <span className="font-bold text-sm">
                            {t("mentor.cancellation-reason")}
                          </span>
                          <p className="text-[#626262] font-medium text-sm">
                            {session.cancellation_reason ||
                              session.declined_reason}
                          </p>
                        </div>
                      </>
                    )}
                  </>
                )}
                {session.rating && session.feedback_comments && (
                  <>
                    <Divider className="bg-[#DEE1E6] my-4" />
                    <div className="space-y-1">
                      <p className="font-bold text-sm">
                        {t("mentor.rating-session")}
                      </p>
                      <p className="text-[#626262] font-medium text-sm">
                        {session.feedback_comments}
                      </p>
                      <p className="font-bold text-base flex items-center gap-1">
                        <FaStar size={20} color="#FF822C" />
                        {session.rating}
                      </p>
                    </div>
                  </>
                )}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <Empty description={t("mentor.no-sessions-found")} />
      )}

      {hasNextPage && (
        <div className="flex justify-center">
          <Button onClick={() => fetchNextPage()} loading={isFetchingNextPage}>
            {t("load-more")}
          </Button>
        </div>
      )}

      {/* Cancel Modal */}
      <Modal
        footer={null}
        open={cancelModalOpen}
        onCancel={() => {
          setSelectedSession(null);
          setCancelModalOpen(false);
          form.resetFields();
        }}
      >
        <div className="py-2">
          <p className="text-xl font-bold">{t("mentor.cancel-session")}</p>
          <Divider className="bg-[#DEE1E6]" />
          <Form layout="vertical" form={form} onFinish={cancelSessionMutate}>
            <Form.Item
              label={t("mentor.cancellation-reason")}
              required
              name={"reason"}
              rules={[
                {
                  required: true,
                },
              ]}
              className="pb-2"
            >
              <Input.TextArea placeholder={t("enter-message")} rows={3} />
            </Form.Item>
            <Divider className="bg-[#DEE1E6]" />
            <div className="flex gap-4">
              <Button
                type="primary"
                htmlType="submit"
                className="w-full"
                loading={isCancelSessionsPending}
              >
                {t("confirm")}
              </Button>
              <Button
                type="default"
                className="w-full"
                onClick={() => {
                  setSelectedSession(null);
                  setCancelModalOpen(false);
                  form.resetFields();
                }}
              >
                {t("cancel")}
              </Button>
            </div>
          </Form>
        </div>
      </Modal>

      {/* Modify Booking Modal */}
      <Modal
        footer={null}
        open={modifyBookingModalOpen}
        onCancel={() => {
          setSelectedSession(null);
          setModifyBookingModalOpen(false);
          setBookingData({
            selectedDay: undefined,
            selectedTime: undefined,
          });
        }}
      >
        <div className="py-2">
          <p className="text-xl font-bold">{t("mentor.reschedule")}</p>
          <p className="text-[#667084] font-medium mt-2">
            {t("mentor.reschedule-with")}{" "}
            <span className="inline-block text-primary">
              {selectedSession?.mentor.name}
            </span>
          </p>
          <Divider className="bg-[#DEE1E6]" />
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
                  }));
                  refetchSlots();
                }}
              />
            </div>
          </div>
          <Divider className="bg-[#DEE1E6]" />
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
                    value: `${slot.start_time}`,
                    label: `${dayjs(slot.start_time, "HH:mm").format(
                      "hh:mm A"
                    )} - ${dayjs(slot.end_time, "HH:mm").format("hh:mm A")}`,
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
          <Divider className="bg-[#DEE1E6]" />
          <div className="flex gap-4">
            <Button
              type="primary"
              className="w-full"
              onClick={rescheduleSessionMutate}
              loading={rescheduleSessionLoading}
            >
              {t("confirm")}
            </Button>
            <Button
              type="default"
              className="w-full"
              onClick={() => {
                setSelectedSession(null);
                setModifyBookingModalOpen(false);
                setBookingData({
                  selectedDay: undefined,
                  selectedTime: undefined,
                });
              }}
            >
              {t("cancel")}
            </Button>
          </div>
        </div>
      </Modal>
    </>
  );
}

export default MentorSessionsPage;
