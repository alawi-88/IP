"use client";

import {
  Card,
  Col,
  DatePicker,
  Flex,
  Form,
  Row,
  Segmented,
  Select,
  Skeleton,
  Spin,
  Typography,
  Tag,
  Radio,
  Button,
  ConfigProvider,
} from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useInfiniteQuery, useQuery } from "@tanstack/react-query";
import axiosInstance from "@/axios";
import dayjs, { Dayjs } from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import { useState } from "react";
import Empty from "@/components/Empty";
import FilterResultsModal from "@/components/FilterResultsModal";
import HistortRow, { Session } from "./_components/HistortRow";

const { Text, Title } = Typography;
const { RangePicker } = DatePicker;
dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);

interface History {
  success: boolean;
  categories: {
    canceled: number;
    past: number;
    upcoming: number;
  };
  data: Session[];
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
}

export default function HistoryPage() {
  const t = useTranslations();
  const locale = useLocale();
  const [form] = Form.useForm();

  // 🔹 Filters state (replaces URL search params)
  const [filters, setFilters] = useState({
    status: "all",
    start_date: null as string | null,
    end_date: null as string | null,
    sort: "desc",
    category: "all",
  });

  const { data: historyStatistics, isLoading: isHistoryStatisticsLoading } =
    useQuery({
      queryKey: ["mentor-statistics"],
      queryFn: async () => {
        const response = await axiosInstance.get(`/mentors/sessions/history`);
        return response.data;
      },
    });

  const {
    data: historyData,
    isLoading,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
  } = useInfiniteQuery({
    queryKey: ["mentor-sessions-history", filters],
    queryFn: async ({ pageParam = 1 }) => {
      const params = {
        ...filters,
        page: pageParam,
        status: filters.status === "all" ? undefined : filters.status,
        category: filters.category === "all" ? undefined : filters.category,
      };

      const response = await axiosInstance.get(`/mentors/sessions/history`, {
        params,
      });
      return response.data;
    },
    getNextPageParam: (lastPage) => {
      const { current_page, last_page } = lastPage.pagination;
      return current_page < last_page ? current_page + 1 : undefined;
    },
    initialPageParam: 1,
  });

  const history = historyData?.pages.flatMap((page) => page.data) ?? [];

  // 🔹 Handle filter submit from modal
  const handleFilterSubmit = (values: any) => {
    const start_date = values.start_date
      ? values.start_date.format("YYYY-MM-DD")
      : undefined;
    const end_date = values.end_date
      ? values.end_date.format("YYYY-MM-DD")
      : undefined;

    setFilters({
      category: values.category,
      status: values.status,
      start_date,
      end_date,
      sort: values.sort,
    });
  };

  return (
    <section className="flex flex-col gap-y-6">
      <Title level={4} className="!text-primary-900 !mb-0">
        {t("session-history")}
      </Title>

      {/* 🔹 Category Cards */}
      <Row gutter={[16, 16]}>
        <Col xs={24} sm={12} lg={8}>
          <Card>
            <Flex vertical gap={8} align="center" justify="center">
              <Text className="text-3xl font-bold text-primary-900 flex items-center h-8">
                {isHistoryStatisticsLoading ? (
                  <Skeleton.Button
                    active
                    style={{ height: "32px", width: "32px" }}
                  />
                ) : (
                  historyStatistics?.categories?.upcoming || 0
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
                {isHistoryStatisticsLoading ? (
                  <Skeleton.Button
                    active
                    style={{ height: "32px", width: "32px" }}
                  />
                ) : (
                  historyStatistics?.categories?.past || 0
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
                {isHistoryStatisticsLoading ? (
                  <Skeleton.Button
                    active
                    style={{ height: "32px", width: "32px" }}
                  />
                ) : (
                  historyStatistics?.categories?.canceled || 0
                )}
              </Text>
              <Text className="text-base text-gray-600">
                {t("canceled-sessions")}
              </Text>
            </Flex>
          </Card>
        </Col>
      </Row>

      <div className="flex items-center justify-between gap-x-6 gap-y-4 flex-wrap">
        {/* Filters Section */}
        <Row gutter={[16, 16]} align="middle" justify="space-between">
          <Col>
            <Card size="small" styles={{ body: { padding: "4px" } }}>
              <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
                <Segmented
                  options={[
                    { label: t("all-sessions"), value: "all" },
                    { label: t("upcoming-sessions"), value: "upcoming" },
                    { label: t("past-sessions"), value: "past" },
                    { label: t("canceled-sessions"), value: "canceled" },
                  ]}
                  className="!bg-card !rounded-xl !p-2 !w-fit"
                  value={filters.category}
                  onChange={(value) =>
                    setFilters((prev) => ({
                      ...prev,
                      category: value,
                    }))
                  }
                />
              </ConfigProvider>
            </Card>
          </Col>
        </Row>

        {/* 🔹 Filter Modal */}
        <FilterResultsModal form={form} filterResults={handleFilterSubmit}>
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

          <Form.Item label={t("start-date")} name="start_date">
            <DatePicker
              className="w-full"
              format="YYYY-MM-DD"
              popupClassName="modal-filters-datepicker-dropdown"
              getPopupContainer={(triggerNode) =>
                triggerNode.closest(".ant-modal") as HTMLElement
              }
            />
          </Form.Item>

          <Form.Item label={t("end-date")} name="end_date">
            <DatePicker
              className="w-full"
              format="YYYY-MM-DD"
              popupClassName="modal-filters-datepicker-dropdown"
              getPopupContainer={(triggerNode) =>
                triggerNode.closest(".ant-modal") as HTMLElement
              }
              disabledDate={(current) => {
                const start = form.getFieldValue("start_date");
                return start && current && current.isSameOrBefore(start, "day");
              }}
            />
          </Form.Item>

          <Form.Item label={t("sort")} name="sort" initialValue="desc">
            <Radio.Group>
              <Radio value="desc">{t("newest-first")}</Radio>
              <Radio value="asc">{t("oldest-first")}</Radio>
            </Radio.Group>
          </Form.Item>
        </FilterResultsModal>
      </div>

      {/* 🔹 Loading State */}
      {isLoading && (
        <Flex justify="center" align="center" className="py-12">
          <Spin />
        </Flex>
      )}

      {/* 🔹 Empty State */}
      {!isLoading && !history?.length && (
        <Card>
          <Empty description={t("no-history")} />
        </Card>
      )}

      {/* 🔹 History List */}
      {!isLoading && !!history?.length && (
        <>
          <Row gutter={[16, 16]}>
            {history.map((session) => (
              <HistortRow key={session.id} session={session} />
            ))}
          </Row>
          {hasNextPage && (
            <div className="flex justify-center mt-6">
              <Button
                onClick={() => fetchNextPage()}
                loading={isFetchingNextPage}
              >
                {t("load-more")}
              </Button>
            </div>
          )}
        </>
      )}
    </section>
  );
}
