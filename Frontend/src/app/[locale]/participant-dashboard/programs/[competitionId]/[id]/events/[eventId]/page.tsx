"use client";

import axiosInstance from "@/axios";
import { Link } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import { Avatar, Button, Card, Col, Divider, Flex, Row, Spin } from "antd";
import { MdArrowForward, MdDateRange } from "react-icons/md";
import { useParams } from "next/navigation";
import { MdArrowBack } from "react-icons/md";
import dayjs from "dayjs";
import { BiLinkAlt } from "react-icons/bi";
import Empty from "@/components/Empty";
import { useLocale, useTranslations } from "next-intl";
import NextLink from "next/link";
import Image from "next/image";
import { FaRegUser } from "react-icons/fa";

interface Event {
  id: number;
  competition: {
    id: number;
    title: string;
    about: string;
    terms_and_conditions: string;
    registration_closed_date: string;
    banner: string;
    is_closed: boolean;
    is_published: boolean;
  };
  title: string;
  brief: string;
  badge: string;
  date: string;
  time: string;
  location: string;
  speaker_photo: string;
  speaker_name: string;
  speaker_experience: string;
  speaker_brief: string;
  event_link: string;
  speakers: {
    photo: string;
    name: string;
    experience: string;
    brief: string;
  }[];
}

export default function EventDetailsPage() {
  const t = useTranslations();
  const locale = useLocale();

  const { id, eventId } = useParams<{ id: string; eventId: string }>();

  const { data: event, isLoading } = useQuery<Event>({
    queryKey: ["events", eventId],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/events/${eventId}`,
        {
          params: { application_id: id },
        }
      );
      return response.data.data;
    },
  });

  if (isLoading) {
    return <Spin />;
  }
  if (!event) {
    return <Empty description={t("sorry-there-are-no-events-now")} />;
  }

  return (
    <div>
      <div className="flex flex-col gap-y-4">
        <div className="flex flex-col gap-y-4">
          <Card className="w-100 meetup-card">
            <Row justify="space-between" align="top" gutter={[32, 32]}>
              <Col xs={24} md={14} xl={16}>
                <div className="flex flex-col gap-y-2 lg:flex-row lg:items-center gap-x-8">
                  <div className="m-0">
                    <div className="flex items-center gap-x-2 text-sm text-[#98A1B2]">
                      <MdDateRange size={20} className="text-[#98A1B2]" />
                      {dayjs(event.date).format("D/MM/YYYY")}
                    </div>
                  </div>
                  <div className="m-0">
                    <p className="flex items-center gap-x-2 text-sm text-[#98A1B2]">
                      <MdDateRange size={20} className="text-[#98A1B2]" />
                      {dayjs(`2000-01-01 ${event.time}`).format("hh:mm ")}{" "}
                      {locale === "en"
                        ? dayjs(`2000-01-01 ${event.time}`).format("A")
                        : dayjs(`2000-01-01 ${event.time}`).format("A") === "AM"
                        ? "ص"
                        : "م"}
                    </p>
                  </div>
                  <div className="m-0">
                    <div className="flex items-center gap-x-2 text-sm text-[#98A1B2]">
                      <MdDateRange size={20} className="text-[#98A1B2]" />
                      {event.location === "virtual"
                        ? t("virtual")
                        : t("on-site")}
                    </div>
                  </div>
                </div>
                <h1 className="text-[#5B656A] font-bold m-0 mt-4 mb-2 text-[20px]">
                  {event.title}
                </h1>
                <p className="m-0 text-[14px]">{event.brief}</p>
              </Col>
              {event?.speakers?.length > 0 && (
                <Col xs={24} md={10} xl={8}>
                  <div className="bg-[#F2F4F7] rounded-lg p-4 flex flex-col gap-y-6">
                    <div className="space-y-4">
                      <h2 className="font-bold text-sm m-0">{t("speakers")}</h2>
                      <Divider />
                    </div>

                    {event.speakers.map((speaker, index) => (
                      <div className="speaker-item space-y-4" key={index}>
                        <div className="flex gap-x-2 items-start">
                          {speaker.photo ? (
                            <Image
                              src={speaker.photo}
                              className="w-12 h-12 rounded-full object-cover flex-shrink-0"
                              width={48}
                              height={48}
                              alt={speaker.name}
                            />
                          ) : (
                            <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                              <FaRegUser className="text-white b" />
                            </div>
                          )}

                          <div className="flex flex-col gap-y-2">
                            <p className="font-bold text-sm m-0">
                              {speaker.name}
                            </p>
                            {speaker.experience && (
                              <p className="font-bold text-[#626262] text-xs m-0">
                                {speaker.experience}
                              </p>
                            )}
                            {speaker.brief && (
                              <>
                                <p className="text-xs text-[#626262] font-medium">
                                  {speaker.brief}
                                </p>
                              </>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </Col>
              )}
            </Row>
          </Card>
        </div>
        {event.event_link && (
          <div className="flex flex-col gap-y-4">
            <Card className="w-100 !shadow-none !rounded-md" bordered={false}>
              <Flex justify="space-between" align="center" gap={10} wrap>
                <div className="flex gap-x-4 items-start">
                  <div className="bg-[#F2F4F7] w-12 h-12 flex items-center justify-center rounded">
                    <BiLinkAlt size={24} className="text-primary" />
                  </div>
                  <Flex gap={4} vertical>
                    <p className="m-0 text-foreground font-bold mb-2 text-[16px]">
                      {t("event-link")}
                    </p>
                    {event.event_link && (
                      <p className="text-[#667085] max-w-[300px] p-wrap text-[14px]">
                        <Link
                          href={event.event_link}
                          target="_blank"
                          rel="noreferrer"
                          className="p-wrap text-[#667085] break-all"
                        >
                          {event.event_link}
                        </Link>
                      </p>
                    )}
                  </Flex>
                </div>
                {event.badge != "completed" && (
                  <NextLink
                    href={event.event_link}
                    target="_blank"
                    rel="noreferrer"
                  >
                    <Button type="primary">
                      <Flex align="center" gap={4}>
                        {t("join-event")}
                        {locale === "en" ? (
                          <MdArrowForward size={20} />
                        ) : (
                          <MdArrowBack size={20} />
                        )}
                      </Flex>
                    </Button>
                  </NextLink>
                )}
              </Flex>
            </Card>
          </div>
        )}
      </div>
    </div>
  );
}
