"use client";

import { Card, Row, Col, Button, Progress } from "antd";
import { useTranslations } from "next-intl";
import { useRouter } from "@/i18n/routing";
import { useParams } from "next/navigation";
import { FiArrowRight } from "react-icons/fi";

export default function CompetitiveAnalysisHub() {
  const t = useTranslations();
  const router = useRouter();
  const params = useParams();
  const startupId = params.startupId as string;

  const pages = [
    {
      key: "competitors",
      label: t("va.competitors", "Competitors"),
      description: "Analyze your competitors",
      completion: 0,
      href: "competitive-analysis/competitors",
    },
    {
      key: "matrix",
      label: t("va.matrix", "Competitor Matrix"),
      description: "Competitive positioning matrix",
      completion: 0,
      href: "competitive-analysis/matrix",
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-primary-900 mb-2">
          {t("va.competitiveAnalysis", "Competitive Analysis")}
        </h1>
        <p className="text-gray-600">
          {t(
            "va.competitiveAnalysisDescription",
            "Understand your competitive landscape"
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
