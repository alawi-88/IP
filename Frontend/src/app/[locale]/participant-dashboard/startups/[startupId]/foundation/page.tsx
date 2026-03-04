"use client";

import { Card, Row, Col, Button, Progress } from "antd";
import { useTranslations } from "next-intl";
import { useRouter, useParams } from "@/i18n/routing";
import { FiArrowRight } from "react-icons/fi";

export default function FoundationHub() {
  const t = useTranslations();
  const router = useRouter();
  const params = useParams();
  const startupId = params.startupId as string;

  const pages = [
    {
      key: "overview",
      label: t("va.overview", "Overview"),
      description: "Business fundamentals and core information",
      completion: 0,
      href: "foundation/overview",
    },
    {
      key: "market-analysis",
      label: t("va.marketAnalysis", "Market Analysis"),
      description: "TAM/SAM/SOM and market opportunities",
      completion: 0,
      href: "foundation/market-analysis",
    },
    {
      key: "financial-model",
      label: t("va.financialModel", "Financial Model"),
      description: "Revenue projections and financial planning",
      completion: 0,
      href: "foundation/financial-model",
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-primary-900 mb-2">
          {t("va.foundation", "Foundation")}
        </h1>
        <p className="text-gray-600">
          {t("va.foundationDescription", "Build the foundation of your startup with essential business information")}
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
