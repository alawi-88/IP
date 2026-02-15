import { Col, Card, Flex, Avatar, Divider, Typography, Button, Descriptions } from "antd";
import dayjs from "dayjs";
import { FaStar } from "react-icons/fa";
import { MdDateRange, MdAccessTime } from "react-icons/md";
import { useTranslations } from "next-intl";
import { useState } from "react";
import { IoIosArrowDown, IoIosArrowUp } from "react-icons/io";
const { Text, Title } = Typography;
export interface Session {
  id: number;
  title: string;
  description: string | null;
  scheduled_at: string;
  scheduled_at_formatted: string;
  duration_minutes: number;
  duration_formatted: string;
  end_time: string;
  end_time_formatted: string;
  status: string;
  status_display_name: string;
  video_tool: string | null;
  video_tool_display_name: string;
  meeting_id: string | null;
  join_url: string | null;
  password: string | null;
  calendar_event_id: string | null;
  declined_reason: string | null;
  cancellation_reason: string | null;
  proposed_time: string | null;
  proposed_time_formatted: string | null;
  has_proposed_time: boolean;
  is_pending_request: boolean;
  feedback: string | null;
  feedback_comments: string | null;
  feedback_strengths: string | null;
  feedback_improvements: string | null;
  rating: number | null;
  started_at: string | null;
  ended_at: string | null;
  is_upcoming: boolean;
  is_in_progress: boolean;
  is_completed: boolean;
  is_cancelled: boolean;
  mentor: {
    id: number;
    name: string;
    email: string;
    profession: string;
    experience: string;
    brief: string;
    image: string;
  };
  participant: {
    id: number;
    name: string;
    email: string;
  };
  competition: {
    id: number;
    title: string;
  };
  created_at: string;
  updated_at: string;
}

const getStatusColor = (status: string) => {
  const statusColors: Record<string, { backgroundColor: React.CSSProperties["backgroundColor"]; color: React.CSSProperties["color"] }> = {
    scheduled: {
      backgroundColor: "#FFE6D5",
      color: "#FF822C",
    },
    // in_progress: {
    //   backgroundColor: "#F59E0B",
    //   color: "#fff",
    // },
    completed: {
      backgroundColor: "#E1F7F6",
      color: "#08BCB8",
    },
    cancelled: {
      backgroundColor: "#FCD8DF",
      color: "#F13C61",
    },
    // no_show: {
    //   backgroundColor: "#FDE8EC",
    //   color: "#fff",
    // },
  };
  return statusColors[status] || { backgroundColor: "#667084", color: "#fff" };
};
const HistortRow = ({ session }: { session: Session }) => {
  const t = useTranslations();
  const [isOpen, setIsOpen] = useState(false);
  const toggle = () => setIsOpen(!isOpen);
  return (
    <Col xs={24}>
      <Card onClick={toggle}>
        <Flex vertical>
          {/* Above row */}
          <Flex gap={12} align="center" justify="space-between">
            {/* Name, avatar, title, description */}
            <Flex gap={12} align="center">
              <Avatar size={46} src={session.mentor.image} />
              <Flex vertical gap={0}>
                <Title level={5} className="!mb-0">
                  {session.mentor.name}
                </Title>
                <Text className="!mt-0">{session.mentor.profession}</Text>
              </Flex>
            </Flex>
            {/* Rate, status,  */}
            <Flex gap={12} align="center">
              <Flex gap={4} align="center">
                <FaStar size={16} className="text-yellow-500" />
                <Text>{session.rating || t("not-available")}</Text>
              </Flex>
              <Text
                style={{
                  ...getStatusColor(session.status),
                  padding: "4px 8px",
                  borderRadius: "8px",
                  fontSize: "12px",
                  fontWeight: "500",
                }}
              >
                {t(`mentor.${session.status}`)}
              </Text>
              <Text className="cursor-pointer" onClick={toggle} style={{ padding: "4px" }}>
                {isOpen ? <IoIosArrowUp size={20} /> : <IoIosArrowDown size={20} />}
              </Text>
            </Flex>
          </Flex>
          <Divider />
          <Flex vertical gap={12}>
            {/* Date, duration */}
            <Flex gap={12} align="center" className="text-gray-500" style={{ opacity: 0.8, fontSize: "12px" }}>
              <Flex gap={4} align="center">
                <MdDateRange size={20} />
                <Text style={{ fontSize: "14px" }}>{session.scheduled_at_formatted}</Text>
              </Flex>
              <Flex gap={4} align="center">
                <MdAccessTime size={20} />
                <Text style={{ fontSize: "14px" }}>
                  {t("duration")}: {session.duration_formatted}
                </Text>
              </Flex>
            </Flex>
            {/* Description */}
            {session.description && <Text>{session.description}</Text>}
            {isOpen && (
              <Card className="bg-[#F6F7F9]" size="small" style={{marginTop: "12px", borderRadius: "8px" }}>
                <Descriptions title={t("session-details")} layout="vertical" column={4} colon={false}>
                  <Descriptions.Item label={t("participant")}>{session.participant.name}</Descriptions.Item>
                  <Descriptions.Item label={t("competition")}>{session.competition.title}</Descriptions.Item>
                  <Descriptions.Item label={t("mentor.date")}>{session.scheduled_at_formatted}</Descriptions.Item>
                  <Descriptions.Item label={t("duration")}>{session.duration_formatted}</Descriptions.Item>
                </Descriptions>
              </Card>
            )}
          </Flex>
        </Flex>
      </Card>
    </Col>
  );
};

export default HistortRow;
