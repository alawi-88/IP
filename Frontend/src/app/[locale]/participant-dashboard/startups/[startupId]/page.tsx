"use client";

import { Card, Row, Col, Statistic, Progress, Button, Spin } from "antd";
import { useTranslations } from "next-intl";
import { useRouter, useParams } from "@/i18n/routing";
import { useQuery } from "@tanstack/react-query";
import * as startupApi from "@/config/startup-api";
import { FiArrowRight } from "react-icons/fi";
import dayjs from "dayjs";

export default function StartupDashboard() {
  const t = useTranslations();
  const router = useRouter();
  const params = useParams();
  const startupId = params.startupId as string;

  const { data: startup, isLoading } = useQuery({
    queryKey: ["startup", startupId],
    queryFn: () => startupApi.getStartup(startupId),
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!startup) {
    return <div>{t("notFound", "Not Found")}</div>;
  }

  const sections = startup.sections || [];
  const lowestSection = sections.reduce(
    (prev, current) =>
      prev.completionPercentage < current.completionPercentage ? prev : current,
    sections[0]
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold text-primary-900 mb-2">
          {startup.name}
        </h1>
        {startup.tagline && (
          <p className="text-gray-600">{startup.tagline}</p>
        )}
      </div>

      {/* Stats Cards */}
      <Row gutter={[16, 16]}>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title={t("va.overallProgress", "Overall Progress")}
              value={startup.completionPercentage}
              suffix="%"
              prefix={
                <Progress
                  type="circle"
                  percent={startup.completionPercentage}
                  width={40}
                />
              }
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title={t("va.activeApplications", "Active Applications")}
              value={sections.length}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title={t("va.teamMembers", "Team Members")}
              value={1}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title={t("va.lastUpdated", "Last Updated")}
              value={startup.updatedAt ? dayjs(startup.updatedAt).format("DD/MM/YYYY") : "-"}
            />
          </Card>
        </Col>
      </Row>

      {/* Section Progress Cards */}
      <div>
        <h2 className="text-xl font-bold text-primary-900 mb-4">
          {t("va.yourSections", "Your Sections")}
        </h2>
        <Row gutter={[16, 16]}>
          {sections.map((section) => (
            <Col key={section.id} xs={24} sm={12} lg={8}>
              <Card
                className="cursor-pointer hover:shadow-lg transition-shadow"
                onClick={() =>
                  router.push(
                    `/participant-dashboard/startups/${startupId}/${section.key}`
                  )
                }
              >
                <div className="space-y-3">
                  <h3 className="font-semibold text-lg text-primary-900">
                    {section.title}
                  </h3>
                  <Progress
                    percent={section.completionPercentage}
                    strokeColor={{
                      "0%": "#26634B",
                      "100%": "#0AEBD7",
                    }}
                  />
                  <p className="text-sm text-gray-600">
                    {section.pages?.length || 0} {t("va.pages", "pages")}
                  </p>
                  <Button
                    type="primary"
                    size="small"
                    icon={<FiArrowRight size={14} />}
                    onClick={(e) => {
                      e.stopPropagation();
                      router.push(
                        `/participant-dashboard/startups/${startupId}/${section.key}`
                      );
                    }}
                  >
                    {t("continue", "Continue")}
                  </Button>
                </div>
              </Card>
            </Col>
          ))}
        </Row>
      </div>

      {/* Continue Journey Card */}
      {lowestSection && lowestSection.completionPercentage < 100 && (
        <Card className="bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-bold text-lg text-blue-900 mb-1">
                {t("va.continueJourney", "Continue Your Journey")}
              </h3>
              <p className="text-blue-700">
                {t("va.continueMessage", "Complete the")}{" "}
                <strong>{lowestSection.title}</strong> {t("va.toProgress", "to progress")}
              </p>
            </div>
            <Button
              type="primary"
              icon={<FiArrowRight size={16} />}
              onClick={() =>
                router.push(
                  `/participant-dashboard/startups/${startupId}/${lowestSection.key}`
                )
              }
            >
              {t("start", "Start")}
            </Button>
          </div>
        </Card>
      )}
    </div>
  );
}
