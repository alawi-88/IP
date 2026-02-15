"use client";

import axiosInstance, { APIError } from "@/axios";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Card, ConfigProvider, message, Spin, TimePicker } from "antd";
import { useLocale, useTranslations } from "next-intl";
import React, { useEffect, useState } from "react";
import { IoMdClose } from "react-icons/io";
import { HiOutlinePlus } from "react-icons/hi";
import { LoadingOutlined } from "@ant-design/icons";
import dayjs, { Dayjs } from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";

dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);

interface ScheduleState {
  [day: string]: {
    key: string;
    name: string;
    slots: {
      id: string;
      start_time: string;
      end_time: string;
      duration_minutes: number;
      created_at: string | null;
      updated_at: string | null;
      status?: string;
    }[];
  };
}

export default function MyTimesPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [originalSlots, setOriginalSlots] = useState<ScheduleState>({});
  const [currentSlots, setCurrentSlots] = useState<ScheduleState>({});
  const [slotErrors, setSlotErrors] = useState<Record<string, any>>({});
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [messageApi, contextHolder] = message.useMessage();
  const queryClient = useQueryClient();
  const allDays = [
    "saturday",
    "sunday",
    "monday",
    "tuesday",
    "wednesday",
    "thursday",
    "friday",
  ];

  // get availability
  const { data: availability, isLoading: isAvailabilityLoading } = useQuery({
    queryKey: ["mentor-availability"],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get("/mentors/availability");
        return response?.data?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
    refetchOnWindowFocus: false,
  });

  // post availability
  const postAvailability = useMutation({
    mutationFn: async (_) => {
      const data = transformScheduleToApiPayload(currentSlots);
      const errorDays = Object.keys(slotErrors).some((day) => {
        return Object.keys(slotErrors[day]).length > 0;
      });
      if (!data.length) {
        messageApi.warning(t("no-changes"));
        throw new Error("no changes");
      }
      if (errorDays) {
        const firstErrorEl = document.querySelector(".text-red-500");
        console.log(firstErrorEl);

        const slotWrapper = firstErrorEl?.closest(".slot-wrapper");
        if (slotWrapper) {
          slotWrapper.scrollIntoView({ behavior: "smooth", block: "start" });
        }
        throw new Error("overlaps found");
      }
      const response = await axiosInstance.post(`/mentors/availability`, data);
      return response.data;
    },
    onSuccess: async (response) => {
      setIsRefreshing(true);
      await queryClient.invalidateQueries({
        queryKey: ["mentor-availability"],
      });
      setIsRefreshing(false);
      messageApi.success(response.message);
      setTimeout(() => {
        document
          .querySelector("main")
          ?.scrollTo({ top: 0, behavior: "smooth" });
      }, 0);
    },
    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
      console.log("Failed to post new slot:", error);
    },
  });

  // remove api slot
  const removeSlotMutation = useMutation({
    mutationFn: async (data: any) => {
      return axiosInstance.delete(`/mentors/availability/${data.slot.id}`);
    },
    onSuccess: (response, variables) => {
      handleRemoveSlot(variables.slot, variables.day, true);
      messageApi.success(t("slot-deleted"));
    },
    onError: (error: APIError) => {
      messageApi.error(error.response.data.message);
      console.log("Failed to remove slot:", error);
    },
  });

  // handle remove slot
  async function handleRemoveSlot(
    slot: ScheduleState[string]["slots"][number],
    day: string,
    delRequest?: boolean | null
  ) {
    const { created_at, id: slotId } = slot;
    const createEmptySlot = () => ({
      id: `temp-${crypto.randomUUID()}`,
      start_time: "",
      end_time: "",
      duration_minutes: 0,
      created_at: null,
      updated_at: null,
      status: "new",
    });

    if (created_at === null || delRequest) {
      let hadSlots = false;
      setCurrentSlots((prev) => {
        const filteredSlots =
          prev[day]?.slots.filter((s) => s.id !== slot.id) || [];
        hadSlots = filteredSlots.length > 0;

        return {
          ...prev,
          [day]: {
            ...prev[day],
            slots:
              filteredSlots.length > 0 ? filteredSlots : [createEmptySlot()],
          },
        };
      });

      removeOverlapping(day, slotId);

      if (hadSlots && !delRequest) {
        messageApi.success(t("slot-deleted"));
      }
    } else {
      removeSlotMutation.mutate({ slot, day });
    }
  }

  // handle add new slot
  function handleAddSlot(day: string) {
    const DaySlots = currentSlots[day]?.slots || [];

    const hasEmptySlot = DaySlots.some(
      (slot) => !slot.start_time || !slot.end_time
    );

    if (hasEmptySlot) {
      messageApi.warning(t("empty-slot-warning", { day: t(`days.${day}`) }));
      return;
    }

    const newSlot = {
      id: `temp-${crypto.randomUUID()}`,
      start_time: "",
      end_time: "",
      duration_minutes: 0,
      created_at: null,
      updated_at: null,
      status: "new",
    };

    setCurrentSlots((prev) => ({
      ...prev,
      [day]: {
        key: day,
        name: t(`days.${day}`),
        slots: [...(prev[day]?.slots || []), newSlot],
      },
    }));
  }

  // handle post availability
  function transformScheduleToApiPayload(schedule: ScheduleState) {
    return Object.entries(schedule)
      .map(([day, data]) => {
        const validSlots = data.slots
          .filter((slot) => {
            if (!slot.start_time || !slot.end_time) return false;
            const slotIdStr = String(slot.id);
            if (slotIdStr.startsWith("temp-")) return true;
            const originalSlot = originalSlots[day]?.slots.find(
              (s) => String(s.id) === slotIdStr
            );
            if (!originalSlot) return true;
            const isChanged =
              originalSlot.start_time !== slot.start_time ||
              originalSlot.end_time !== slot.end_time;

            return isChanged;
          })
          .map((slot) => ({
            id: String(slot.id).startsWith("temp-") ? null : slot.id,
            type: String(slot.id).startsWith("temp-") ? "new" : "updated",
            start_time: slot.start_time,
            end_time: slot.end_time,
          }));

        if (validSlots.length === 0) return null;

        return {
          day_of_week: day,
          slots: validSlots,
          is_recurring: true,
        };
      })
      .filter(Boolean);
  }

  // handle overlapping
  function handleSlotOverlap(
    day: string,
    slotId: string,
    start: Dayjs | null,
    end: Dayjs | null
  ) {
    // Clean previous errors for this slot first
    removeOverlapping(day, slotId);

    if (!start || !end) return false;

    const daySlots = currentSlots[day]?.slots || [];

    // check for invalid same-time slot first
    if (start.isSame(end, "minute")) {
      setSlotErrors((prev) => ({
        ...prev,
        [day]: {
          ...(prev[day] || {}),
          [slotId]: {
            slot: {
              id: slotId,
              start_time: start.format("hh:mm a"),
              end_time: end.format("hh:mm a"),
            },
            overlaps: [
              {
                id: slotId,
                start_time: start.format("hh:mm a"),
                end_time: end.format("hh:mm a"),
                message: t("slot-same-time-error", {
                  start: start.format("hh:mm a"),
                  end: end.format("hh:mm a"),
                }),
                invalid: true,
              },
            ],
          },
        },
      }));
      return true;
    }

    // Find overlapping slots
    const overlapping = daySlots.filter((s) => {
      if (s.id === slotId) return false;
      if (!s.start_time || !s.end_time) return false;
      const sStart = dayjs(s.start_time, "HH:mm");
      const sEnd = dayjs(s.end_time, "HH:mm");
      return !(end.isSameOrBefore(sStart) || start.isSameOrAfter(sEnd));
    });

    if (overlapping.length > 0) {
      // Save the current slot and what it overlaps with
      setSlotErrors((prev) => ({
        ...prev,
        [day]: {
          ...(prev[day] || {}),
          [slotId]: {
            slot: {
              id: slotId,
              start_time: start.format("hh:mm a"),
              end_time: end.format("hh:mm a"),
            },
            overlaps: overlapping.map((s) => ({
              id: s.id,
              start_time: dayjs(s.start_time, "HH:mm").format("hh:mm a"),
              end_time: dayjs(s.end_time, "HH:mm").format("hh:mm a"),
            })),
          },
        },
      }));
      return true;
    }

    return false;
  }

  // remove overlapping
  function removeOverlapping(day: string, slotId: any) {
    setSlotErrors((prev) => {
      const newErrors = { ...prev };

      // Remove errors for this slot
      if (newErrors[day]?.[slotId]) {
        delete newErrors[day][slotId];
      }

      // Remove this slot from other slots' overlaps
      if (newErrors[day]) {
        Object.keys(newErrors[day]).forEach((otherSlotId) => {
          newErrors[day][otherSlotId].overlaps = newErrors[day][
            otherSlotId
          ].overlaps.filter((s: any) => s.id !== slotId);
          // If no more overlaps, remove the error
          if (newErrors[day][otherSlotId].overlaps.length === 0) {
            delete newErrors[day][otherSlotId];
          }
        });
        // If no errors left for the day, remove the day key
        if (Object.keys(newErrors[day]).length === 0) {
          delete newErrors[day];
        }
      }

      return newErrors;
    });
  }

  // get disabled times
  function getDisabledTimes(day: string, slotId: string) {
    const slots = currentSlots[day]?.slots || [];
    const takenRanges = slots
      .filter((s) => s.id !== slotId && s.start_time && s.end_time)
      .map((s) => ({
        start: dayjs(s.start_time, "HH:mm"),
        end: dayjs(s.end_time, "HH:mm"),
      }));
  
    return {
      disabledHours: () => {
        const hours = new Set<number>();
        takenRanges.forEach(({ start, end }) => {
          for (let h = start.hour(); h <= end.hour(); h++) {
            hours.add(h);
          }
        });
        return Array.from(hours);
      },
      disabledMinutes: (selectedHour: number) => {
        const minutes = new Set<number>();
        takenRanges.forEach(({ start, end }) => {
          for (let h = start.hour(); h <= end.hour(); h++) {
            if (h === selectedHour) {
              const minStart = h === start.hour() ? start.minute() : 0;
              const minEnd = h === end.hour() ? end.minute() : 59;
              for (let m = minStart; m <= minEnd; m++) minutes.add(m);
            }
          }
        });
        return Array.from(minutes);
      },
    };
  }
  

  useEffect(() => {
    if (!availability) return;

    const days = allDays.reduce((acc: ScheduleState, day) => {
      const found = availability.find((d: any) => d.day_of_week === day);
      acc[day] = {
        key: day,
        name: t(`days.${day}`),
        slots:
          found && found.slots.length > 0
            ? found.slots.map((slot: any) => ({
                ...slot,
                status: "submitted",
              }))
            : [
                {
                  id: `temp-${crypto.randomUUID()}`,
                  start_time: "",
                  end_time: "",
                  duration_minutes: 0,
                  created_at: null,
                  updated_at: null,
                  status: "new",
                },
              ],
      };
      return acc;
    }, {} as ScheduleState);

    setCurrentSlots(days);
    setOriginalSlots(days);
  }, [availability]);

  return (
    <>
      {contextHolder}
      {isAvailabilityLoading ? (
        <Spin className="w-full flex justify-center" />
      ) : (
        <Card
          title={
            <div className="bg-[#F6F7F9] p-3 rounded-lg text-base font-medium">
              {t("session-duration-hint")}
            </div>
          }
        >
          <div className="schedule-table flex flex-col gap-y-6">
            {Object.entries(currentSlots).map(([day, { name, slots }]) => {
              return (
                <div
                  key={day}
                  className="day-item flex gap-x-2 sm:gap-x-4"
                  data-day={day}
                >
                  <p className="day-name flex items-center font-medium sm:text-lg h-12 sm:rtl:min-w-20 sm:ltr:min-w-28 rtl:min-w-14 ltr:min-w-[100px]">
                    {name}
                  </p>

                  <div className="day-slots flex gap-x-4 flex-wrap items-end">
                    <div className="slots-list flex flex-col gap-y-2">
                      {slots.map((slot, index) => (
                        <div key={slot.id} className="slot-wrapper">
                          <div className="slot-item flex gap-x-2 sm:gap-x-6">
                            <div className="time-picker-item [&_.ant-picker-active-bars]:[direction:initial]">
                              <ConfigProvider
                                direction={locale === "ar" ? "rtl" : "ltr"}
                              >
                                <TimePicker.RangePicker
                                  className="h-12"
                                  placeholder={[t("from"), t("to")]}
                                  format="hh:mm a"
                                  minuteStep={30}
                                  showNow={false}
                                  hideDisabledOptions
                                  use12Hours
                                  allowClear={false}
                                  value={
                                    slot.start_time && slot.end_time
                                      ? [
                                          dayjs(slot.start_time, "HH:mm"),
                                          dayjs(slot.end_time, "HH:mm"),
                                        ]
                                      : null
                                  }
                                  onChange={(values) => {
                                    if (!values) {
                                      handleSlotOverlap(
                                        day,
                                        slot.id,
                                        null,
                                        null
                                      );
                                      setCurrentSlots((prev) => ({
                                        ...prev,
                                        [day]: {
                                          ...prev[day],
                                          slots: prev[day].slots.map((s) =>
                                            s.id === slot.id
                                              ? {
                                                  ...s,
                                                  start_time: "",
                                                  end_time: "",
                                                  status: "new",
                                                }
                                              : s
                                          ),
                                        },
                                      }));
                                      return;
                                    }
                                    const [start, end] = values;
                                    if (start && end) {
                                      handleSlotOverlap(
                                        day,
                                        slot.id,
                                        start,
                                        end
                                      );
                                      const updatedSlot = {
                                        ...slot,
                                        start_time: start
                                          ? start.format("HH:mm")
                                          : "",
                                        end_time: end
                                          ? end.format("HH:mm")
                                          : "",
                                        duration_minutes: start && end ? 60 : 0,
                                        updated_at: new Date().toISOString(),
                                        status: "updated",
                                      };

                                      setCurrentSlots((prev) => ({
                                        ...prev,
                                        [day]: {
                                          ...prev[day],
                                          slots: prev[day]?.slots?.map((s) =>
                                            s.id === slot.id ? updatedSlot : s
                                          ),
                                        },
                                      }));
                                    }
                                  }}
                                />
                              </ConfigProvider>
                            </div>
                            <div className="item-actions flex items-center gap-x-2 sm:gap-x-6">
                              <div className="delete-slot flex items-center h-12">
                                <button
                                  type="button"
                                  className={`hover:text-primary transition`}
                                  onClick={() =>
                                    handleRemoveSlot(slot, day, false)
                                  }
                                  disabled={
                                    removeSlotMutation.isPending &&
                                    removeSlotMutation.variables.slot.id ==
                                      slot.id
                                  }
                                >
                                  {removeSlotMutation.isPending &&
                                  removeSlotMutation.variables.slot.id ==
                                    slot.id ? (
                                    <Spin
                                      indicator={
                                        <LoadingOutlined
                                          style={{ fontSize: 24 }}
                                          spin
                                        />
                                      }
                                    />
                                  ) : (
                                    <IoMdClose size={24} />
                                  )}
                                </button>
                              </div>
                              {index === slots.length - 1 && (
                                <div className="add-slot flex items-center h-12 max-sm:hidden">
                                  <button
                                    type="button"
                                    onClick={() => handleAddSlot(day)}
                                    className="hover:text-primary transition"
                                  >
                                    <HiOutlinePlus size={24} />
                                  </button>
                                </div>
                              )}
                            </div>
                          </div>
                          {slotErrors[day]?.[slot.id] && (
                            <div className="slot-errors mt-1 flex flex-col gap-y-1">
                              {slotErrors[day][slot.id].overlaps.map(
                                (overlap: any, i: number) => {
                                  const slotIndex = currentSlots[
                                    day
                                  ]?.slots.findIndex(
                                    (s) => s.id === overlap.id
                                  );

                                  return (
                                    <p
                                      key={overlap.id}
                                      className="text-red-500 text-sm sm:text-nowrap"
                                    >
                                      {overlap.invalid
                                        ? overlap.message
                                        : t("slot-overlap", {
                                            index: slotIndex + 1,
                                            start: overlap.start_time,
                                            end: overlap.end_time,
                                          })}
                                    </p>
                                  );
                                }
                              )}
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                    <div className="add-slot mt-4 w-full flex items-center sm:hidden ">
                      <button
                        type="button"
                        onClick={() => handleAddSlot(day)}
                        className="flex gap-2 hover:text-primary transition"
                      >
                        <HiOutlinePlus size={24} />
                        {t("add")}
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="flex justify-end items-center gap-4 flex-wrap mt-6">
            <Button
              type="primary"
              size="large"
              loading={postAvailability.isPending || isRefreshing}
              onClick={() => postAvailability.mutate()}
            >
              {t("save")}
            </Button>
          </div>
        </Card>
      )}
    </>
  );
}
