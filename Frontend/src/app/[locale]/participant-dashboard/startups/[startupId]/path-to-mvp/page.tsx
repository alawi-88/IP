"use client";

import { Card, Row, Col, Button, Progress } from "antd";
import { useTranslations } from "next-intl";
import { useRouter, useParams } from "@/i18n/routing";
import { FiArrowRight } from "react-icons/fi";

export default function PathToMvpHub() {
  const t = useTranslations();
  const router = useRouter();
  const params = useParams();
  const startupId = params.startupId as string;

  const pages = [
    {
      key: "features",
      label: t("va.features", "Features"),
      description: "Define your product features",
      completion: 0,
      href: "path-to-mvp/features",
    },
    {
      key: "validation",
      label: t("va.validation", "Validation"),
      description: "Validate your assumptions",
      completion: 0,
      href: "path-to-mvp/validation",
    },
    {
      key: "timeline",
      label: t("va.timeline", "Timeline"),
      description: "Development timeline and milestones",
      completion: 0,
      href: "path-to-mvp/timeline",
    },
    {
      key: "marketing",
      label: t("va.marketing", "Marketing"),
      description: "Marketing and user acquisition strategy",
      completion: 0,
      href: "path-to-mvp/marketing",
    },
    {
      key: "budget",
      label: t("va.budget", "Budget"),
      description: "Budget allocation and costs",
      completion: 0,
      href: "path-to-mvp/budget",
    },
    {
      key: "metrics",
      label: t("va.metrics", "Metrics"),
      description: "Key metrics and KPIs",
      completion: 0,
      href: "path-to-mvp/metrics",
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-primary-900 mb-2">
          {t("va.pathToMvp", "Path to MVP")}
        </h1>
        <p className="text-gray-600">
          {t(
            "va.pathToMvpDescription",
            "Plan your journey to launching the MVP"
          )}
        </p>
      </div>

      <Row gutter={[16, 16]}>
        {pages.map((page) => (
          <Col key={page.key} xs={24} sm={12} lg={8}>
            <Card
              className="cursor-pointer hover:shadow-lg transition-shadow h-full"
              onClick={() =>
                router.push(
                  `/participant-dashboard/startups/${startupId}/${page.href}`
                )
              }
            >
              <div className="space-y-4">
                <div>
                  <h3 className="font-semibold text-lg text-primary-900 mb-2">
                    {page.label}
                  </h3>
                  <p className="text-sm text-gray-600">{page.description}</p>
                </div>

                <div className="space-y-2">
                  <div className="flex justify-between items-center text-xs">
                    <span className="text-gray-600">
                      {t("va.completion", "Completion")}
                    </span>
                    <span className="font-semibold">{page.completion}%</span>
                  </div>
                  <Progress
                    percent={page.completion}
                    strokeColor={{
                      "0%": "#26634B",
                      "100%": "#0AEBD7",
                    }}
                  />
                </div>

                <Button
                  type="primary"
                  block
                  icon={<FiArrowRight size={14} />}
                  onClick={(e) => {
                    e.stopPropagation();
                    router.push(
                      `/participant-dashboard/startups/${startupId}/${page.href}`
                    );
                  }}
                >
                  {t("start", "Start")}
                </Button>
              </div>
            </Card>
          </Col>
        ))}
      </Row>
    </div>
  );
}
