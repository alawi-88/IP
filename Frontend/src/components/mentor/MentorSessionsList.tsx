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
  ConfigProvider,
  Segmented,
  Radio,
} from "antd";
import { useForm } from "antd/es/form/Form";
import dayjs, { Dayjs } from "dayjs";
import { useLocale, useTranslations } from "next-intl";
import Image from "next/image";
import { useEffect, useState } from "react";
import { FaRegUser } from "react-icons/fa";
import { IoCalendarOutline } from "react-icons/io5";
import { IoVideocamOutline } from "react-icons/io5";
import { IoIosCheckmarkCircleOutline } from "react-icons/io";
import { IoIosCloseCircleOutline } from "react-icons/io";
import { IoIosArrowDown, IoIosArrowUp } from "react-icons/io";
import { BsRecordCircle } from "react-icons/bs";
import { FaStar } from "react-icons/fa6";
import { IoTimeOutline } from "react-icons/io5";
import EvaluationCommentInput from "@/components/judge-evaluate/EvolutionCommentInput";

interface QueryParams {
  status?: string | undefined;
  [key: string]: any;
}

interface MentorSessionsListProps {
  query?: QueryParams;
  showPagination?: boolean;
  enableCollapse?: boolean;
  type?: string;
}

export default function MentorSessionsList({
  query = {},
  showPagination,
  enableCollapse,
  type,
}: MentorSessionsListProps) {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = useForm();
  const [messageApi, contextHolder] = message.useMessage();
  const queryClient = useQueryClient();
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
  const [collapseOpen, setCollapseOpen] = useState<number[]>([]);

  //get sessions
  const { data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery<{
      data: Session[];
      pagination: any;
    }>({
      queryKey: ["mentor-sessions", locale, query],
      queryFn: async ({ pageParam = undefined }) => {
        const filters = {
          ...query,
          status: query?.status === "all" ? undefined : query?.status,
        };
        const response = await axiosInstance.get("/mentors/sessions", {
          params: {
            page: pageParam,
            ...filters,
          },
        });
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
      "mentor-slots",
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
        `/mentors/sessions/available-slots`,
        {
          params: {
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

  // handle session
  const {
    mutate: handleSessionMutate,
    isPending,
    variables: handleSessionVariables,
  } = useMutation({
    mutationFn: async (data: any) => {
      if (!data) return;
      const response = await axiosInstance.post(
        `/mentors/sessions/${data?.sessionId}/${data.action}`,
        data
      );
      return response.data;
    },
    onSuccess: (response, variables) => {
      queryClient.invalidateQueries({
        queryKey: ["mentor-sessions", locale],
        exact: false,
      });
      messageApi.success(response.message);
      if (variables.action === "start" && response.data?.join_url) {
        window.open(response.data.join_url, "_blank", "noopener,noreferrer");
      }
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  // cancel session
  const { mutate: cancelSessionMutate, isPending: isCancelSessionsPending } =
    useMutation({
      mutationFn: async (data: any) => {
        return axiosInstance.post(
          `/mentors/sessions/${selectedSession?.id}/cancel`,
          data
        );
      },
      onSuccess: (response, variables) => {
        queryClient.invalidateQueries({
          queryKey: ["mentor-sessions", locale],
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
        `/mentors/sessions/${selectedSession?.id}`,
        body
      );
      return response.data;
    },
    onSuccess: (response) => {
      queryClient.invalidateQueries({
        queryKey: ["mentor-sessions", locale],
        exact: false,
      });
      queryClient.invalidateQueries({
        queryKey: [
          "mentor-slots",
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
      messageApi.success(response.message);
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  //handle toggle collapse
  const toggleCollapse = (id: number) => {
    setCollapseOpen((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
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
      {isLoading ? (
        <Spin className="flex justify-center w-full" />
      ) : sessions && sessions.length > 0 ? (
        <div className="grid grid-cols-1 gap-4">
          {sessions.map((session: Session) => {
            const isCollapsed =
              type === "history" ||
              session.cancellation_reason ||
              session.declined_reason ||
              session.rating;
            return (
              <div
                key={session.id}
                className="session__card px-5 py-6 bg-card rounded-xl flex flex-col h-full"
                data-id={session.id}
              >
                <div className="card__head space-y-4">
                  <div className="flex items-start justify-between gap-4 flex-wrap relative">
                    <div className="flex gap-x-2 ">
                      <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                        <FaRegUser className="text-white b" />
                      </div>
                      <div className="flex flex-col gap-y-2">
                        <h3 className="font-bold text-sm m-0">
                          {session.participant.name}
                        </h3>
                        <p className="text-[#667084] text-sm">
                          {session.participant.current_role}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center flex-wrap gap-4">
                      <div
                        className={`relative px-4 py-2 text-sm font-medium rounded-md ${
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
                      {enableCollapse && isCollapsed && (
                        <button
                          type="button"
                          onClick={() => toggleCollapse(session.id)}
                          className={`${
                            collapseOpen.includes(session.id)
                              ? "rotate-180"
                              : "rotate-0"
                          }`}
                        >
                          <IoIosArrowDown size={24} />
                        </button>
                      )}
                    </div>
                  </div>
                  {/* <p className="font-medium text-sm">{session.title}</p> */}
                  <Divider className="bg-[#DEE1E6] my-4" />
                  <div className="space-y-4">
                    <div className="flex items-center gap-x-6 gap-y-3 flex-wrap">
                      <div className="flex items-center gap-2">
                        <IoCalendarOutline size={20} />
                        <p className="text-[#626262] font-medium">
                          <span className="inline-block">
                            {dayjs(session.scheduled_at).format(
                              "dddd, DD MMMM"
                            )}
                          </span>{" "}
                          <span className="inline-block">
                            - {dayjs(session.scheduled_at).format("hh:mm A")}
                          </span>
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <IoTimeOutline size={20} />
                        <p className="text-[#626262] font-medium">
                          <span className="inline-block">
                            {session.duration_minutes} {t("mentor.minute")}
                          </span>
                        </p>
                      </div>
                    </div>
                    {session.description && (
                      <p className="font-medium text-sm">
                        {/* {t("mentor.session-description")}: {session.description} */}
                        {session.description}
                      </p>
                    )}
                    {session.is_upcoming && (
                      <div className="flex gap-x-4 gap-y-2 flex-wrap ">
                        <Button
                          className=" min-w-auto"
                          type="primary"
                          onClick={() => {
                            handleSessionMutate({
                              sessionId: session.id,
                              action:
                                session.status === "confirmed"
                                  ? "start"
                                  : "accept",
                            });
                          }}
                          loading={
                            isPending &&
                            handleSessionVariables?.sessionId === session.id
                          }
                          icon={
                            session.status === "confirmed" ? (
                              <BsRecordCircle size={20} />
                            ) : (
                              <IoIosCheckmarkCircleOutline size={20} />
                            )
                          }
                        >
                          {session.status === "confirmed"
                            ? t("mentor.start-session")
                            : t("mentor.accept-session")}
                        </Button>
                        <Button
                          className="min-w-auto border border-[#CCCFD6]"
                          color="default"
                          variant="filled"
                          onClick={() => {
                            setSelectedSession(session);
                            setModifyBookingModalOpen(true);
                          }}
                          icon={<IoTimeOutline size={20} />}
                        >
                          {t("mentor.reschedule")}
                        </Button>
                        <Button
                          className=" min-w-auto"
                          color="danger"
                          variant="outlined"
                          onClick={() => {
                            setSelectedSession(session);
                            setCancelModalOpen(true);
                          }}
                          icon={<IoIosCloseCircleOutline size={20} />}
                        >
                          {t("mentor.decline-session")}
                        </Button>
                      </div>
                    )}
                    {session.is_in_progress && (
                      <div className="flex gap-x-4 gap-y-2 flex-wrap ">
                        <Button
                          className=" min-w-auto"
                          type="primary"
                          onClick={() => {
                            handleSessionMutate({
                              sessionId: session.id,
                              action: "end",
                            });
                          }}
                          loading={
                            isPending &&
                            handleSessionVariables?.sessionId === session.id
                          }
                          icon={<IoIosCheckmarkCircleOutline size={20} />}
                        >
                          {t("mentor.end-session")}
                        </Button>
                        {session.join_url && (
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
                        )}
                      </div>
                    )}
                  </div>
                </div>
                <div
                  className={`card__body space-y-4 mt-4 empty:hidden ${
                    enableCollapse && isCollapsed
                      ? collapseOpen.includes(session.id)
                        ? "block"
                        : "hidden"
                      : ""
                  }`}
                >
                  {session.is_cancelled &&
                    (session.cancellation_reason ||
                      session.declined_reason) && (
                      <>
                        <Divider className="bg-[#DEE1E6] mt-0 mb-4" />
                        <div className="space-y-2">
                          <span className="font-bold">
                            {t("mentor.cancellation-reason")}
                          </span>
                          <p className="text-[#626262] font-medium text-sm">
                            {session.cancellation_reason ||
                              session.declined_reason}
                          </p>
                        </div>
                      </>
                    )}

                  {(session.is_completed || type === "history") && (
                    <SessionRating session={session} type={type} />
                  )}
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        <Empty description={t("mentor.no-sessions-found")} />
      )}

      {showPagination && hasNextPage && (
        <div className="flex justify-center mt-6">
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
          <p className="text-xl font-bold">{t("mentor.decline-session")}</p>
          <Divider className="bg-[#DEE1E6]" />
          <Form layout="vertical" form={form} onFinish={cancelSessionMutate}>
            <Form.Item
              label={t("mentor.decline-reason")}
              name={"reason"}
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

// Session rating component
const SessionRating = ({
  session,
  type,
}: {
  session: Session;
  type?: string;
}) => {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = useForm();
  const [messageApi, contextHolder] = message.useMessage();
  const [isRated, setIsRated] = useState(false);
  const queryClient = useQueryClient();

  const { mutate: submitRating, isPending } = useMutation({
    mutationFn: async (values: any) => {
      return axiosInstance.post(`/mentors/sessions/${session.id}/feedback`, {
        ...values,
        strengths: "null",
        improvements: "null",
      });
    },
    onSuccess: (response) => {
      queryClient.invalidateQueries({
        queryKey: ["mentor-sessions", locale],
        exact: false,
      });
      messageApi.success(response.data.message);
      setIsRated(true);
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
    },
  });

  const onFinish = (values: any) => {
    submitRating(values);
  };

  useEffect(() => {
    if (session?.rating || session?.feedback_comments) {
      setIsRated(true);
    }
  }, [session, form]);

  return (
    <div className="session-info">
      {contextHolder}
      {isRated || type === "history" ? (
        <>
          <div className="dashboard-card rounded-xl bg-[#F6F7F9] border border-[#DEE1E6] gap-y-0 p-4">
            <div className="session-overview">
              <p className="font-bold mb-4">{t("mentor.session-details")}</p>
              <div className="dashboard-card rounded-xl bg-[#F6F7F9] gap-y-0 p-0">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                  <div className="flex flex-col gap-2">
                    <p className="text-sm text-[#667084] font-medium">
                      {t("participant")}
                    </p>
                    <p className="font-bold text-sm flex items-center gap-1">
                      {session.participant.name}
                    </p>
                  </div>
                  <div className="flex flex-col gap-2">
                    <p className="text-sm text-[#667084] font-medium">
                      {t("competition")}
                    </p>
                    <p className="font-bold text-sm flex items-center gap-1">
                      {session.competition.title}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            {isRated && (
              <>
                <Divider className="bg-[#DEE1E6] my-5" />
                <div className="rating-overview">
                  <p className="font-bold mb-4">{t("mentor.rating-session")}</p>
                  <div className="dashboard-card rounded-xl gap-y-0 p-4">
                    <p className="font-medium mb-4">
                      {form.getFieldValue("comments") ||
                        session.feedback_comments}
                    </p>
                    <div className="rating-row">
                      <div className="flex flex-col ">
                        <p className="text-sm text-[#667084] font-medium">
                          {t("mentor.overall-rating")}
                        </p>
                        <p className="font-bold text-base flex items-center gap-1">
                          <FaStar size={20} color="#FF822C" />
                          {form.getFieldValue("rating") || session.rating}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </>
            )}
          </div>
        </>
      ) : (
        <div className="session-rating">
          <Divider className="bg-[#DEE1E6] !mt-0 my-4" />
          <Form
            form={form}
            layout="vertical"
            onFinish={onFinish}
            disabled={isRated}
          >
            <p className="font-bold mb-4">{t("mentor.rating-session")}</p>

            <Form.Item
              name="rating"
              label={t("mentor.overall-rating")}
              required
              rules={[{ required: true }]}
              className="!mb-4"
            >
              <Radio.Group className="!flex !gap-4 !flex-wrap !items-center">
                {Array.from({ length: 5 }, (_, i) => i + 1).map((val) => (
                  <Radio.Button
                    key={val}
                    value={val}
                    rootClassName="evaluate"
                    title={`${val}/5`}
                  >
                    {val}
                  </Radio.Button>
                ))}
              </Radio.Group>
            </Form.Item>

            <div className="mb-4">
              <EvaluationCommentInput
                name={`comments`}
                label={t("write-comment")}
                maxChars={200}
                rows={3}
                placeholder={t("enter-message")}
                isRequired={true}
              />
            </div>

            <Button htmlType="submit" type="primary" loading={isPending}>
              {t("send")}
            </Button>
          </Form>
        </div>
      )}
    </div>
  );
};
