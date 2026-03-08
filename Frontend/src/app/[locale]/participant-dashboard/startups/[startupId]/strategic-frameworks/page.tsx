"use client";

import { Card, Row, Col, Button, Progress } from "antd";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/routing";
import { useParams } from "next/navigation";
import { FiArrowRight } from "react-icons/fi";

export default function StrategicFrameworksHub() {
  const t = useTranslations();
  const router = useRouter();
  const params = useParams();
  const startupId = params.startupId as string;

  const pages = [
    {
      key: "swot",
      label: t("va.swot", "SWOT Analysis"),
      description: "Strengths, Weaknesses, Opportunities, Threats",
      completion: 0,
      href: "strategic-frameworks/swot",
    },
    {
      key: "mvp-canvas",
      label: t("va.mvpCanvas", "MVP Canvas"),
      description: "Define your minimum viable product",
      completion: 0,
      href: "strategic-frameworks/mvp-canvas",
    },
    {
      key: "bmc",
      label: t("va.bmc", "Business Model Canvas"),
      description: "Your complete business model on one page",
      completion: 0,
      href: "strategic-frameworks/bmc",
    },
    {
      key: "business-plan",
      label: t("va.businessPlan", "Business Plan"),
      description: "Comprehensive business planning",
      completion: 0,
      href: "strategic-frameworks/business-plan",
    },
    {
      key: "gtm-overview",
      label: t("va.gtmOverview", "GTM Overview"),
      description: "Go-to-market strategy overview",
      completion: 0,
      href: "strategic-frameworks/gtm-overview",
    },
    {
      key: "pestel",
      label: t("va.pestel", "PESTEL Analysis"),
      description: "Political, Economic, Social, Technological, Environmental, Legal",
      completion: 0,
      href: "strategic-frameworks/pestel",
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-primary-900 mb-2">
          {t("va.strategicFrameworks", "Strategic Frameworks")}
        </h1>
        <p className="text-gray-600">
          {t(
            "va.strategicFrameworksDescription",
            "Develop strategic frameworks for your startup"
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
