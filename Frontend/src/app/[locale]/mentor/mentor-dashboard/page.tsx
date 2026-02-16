"use client";

import { useTranslations } from "next-intl";
import MentorSessionsList from "@/components/mentor/MentorSessionsList";
import { Button, Card, Col, Flex, Row, Skeleton, Typography } from "antd";
import { Link } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import axiosInstance from "@/axios";
const { Text, Title } = Typography;

export default function MentorDashboard() {
  const t = useTranslations();

  const { data: history, isLoading } = useQuery({
    queryKey: ["mentor-statistics"],
    queryFn: async () => {
      const response = await axiosInstance.get(`/mentors/sessions/history`);
      return response.data;
    },
  });

  return (
    <>
      <div className="mentor-statistics mb-6">
        <h1 className="text-2xl text-foreground font-bold mb-4">
          {t("mentor.quick-statistics")}
        </h1>
        {/* Category Cards */}
        <Row gutter={[16, 16]}>
          <Col xs={24} sm={12} lg={8}>
            <Card>
              <Flex vertical gap={8} align="center" justify="center">
                <Text className="text-3xl font-bold text-primary-900 flex items-center h-8">
                  {isLoading ? (
                    <Skeleton.Button
                      active
                      style={{ height: "32px", width: "32px" }}
                    />
                  ) : (
                    history?.categories?.upcoming || 0
                  )}
                </Text>
                <Text className="text-base text-gray-600">
                  {t("upcoming-sessions")}
                </Text>
              </Flex>
            </Card>
          </Col>
          <Col xs={24} sm={12} lg={8}>
            <Card>
              <Flex vertical gap={8} align="center" justify="center">
                <Text className="text-3xl font-bold text-primary-900 flex items-center h-8">
                  {isLoading ? (
                    <Skeleton.Button
                      active
                      style={{ height: "32px", width: "32px" }}
                    />
                  ) : (
                    history?.categories?.past || 0
                  )}
                </Text>
                <Text className="text-base text-gray-600">
                  {t("past-sessions")}
                </Text>
              </Flex>
            </Card>
          </Col>
          <Col xs={24} sm={12} lg={8}>
            <Card>
              <Flex vertical gap={8} align="center" justify="center">
                <Text className="text-3xl font-bold text-primary-900 flex items-center h-8">
                  {isLoading ? (
                    <Skeleton.Button
                      active
                      style={{ height: "32px", width: "32px" }}
                    />
                  ) : (
                    history?.categories?.canceled || 0
                  )}
                </Text>
                <Text className="text-base text-gray-600">
                  {t("canceled-sessions")}
                </Text>
              </Flex>
            </Card>
          </Col>
        </Row>
      </div>
      <div className="mentor-last-sessions flex gap-4 mb-4 justify-between items-center">
        <h1 className="text-2xl text-foreground font-bold">
          {t("mentor.last-sessions")}
        </h1>
        <Link href={"/mentor/mentor-dashboard/sessions"}>
          <Button type="default">{t("more")}</Button>
        </Link>
      </div>
      <MentorSessionsList />
    </>
  );
}
