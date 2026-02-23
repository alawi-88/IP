"use client";

import axiosInstance from "@/axios";
import Empty from "@/components/Empty";
import { useQuery } from "@tanstack/react-query";
import { Badge, Button, Card, Spin, Tag, Modal, Upload, Input, message } from "antd";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useState } from "react";
import {
  BsCalendar3,
  BsCheckCircle,
  BsClock,
  BsExclamationCircle,
  BsFileEarmarkText,
  BsUpload,
} from "react-icons/bs";
import dayjs from "dayjs";
// Helper to extract locale value from bilingual objects {ar, en} or plain strings
function localizedValue(value: any, locale: string): string {
  if (!value) return "";
  if (typeof value === "string") return value;
  if (typeof value === "object" && value !== null) {
    return value[locale] || value["en"] || value["ar"] || "";
  }
  return String(value);
}

interface TaskAssignment {
  id: number;
  title: string | { ar: string; en: string };
  description: string | { ar: string; en: string };
  instructions: string | { ar: string; en: string };
  due_date: string | null;
  status: string;
  stage: { id: number; title: string | { ar: string; en: string } } | null;
  allowed_file_formats: string[] | null;
  max_file_size_mb: number | null;
  assignment_notes: string;
  created_at: string;
  latest_submission: {
    id: number;
    version: number;
    status: string;
    admin_feedback: string | null;
    submitted_at: string;
  } | null;
}

const statusConfig: Record<string, { color: string; icon: React.ReactNode }> = {
  not_started: { color: "default", icon: <BsClock /> },
  in_progress: { color: "processing", icon: <BsClock /> },
  submitted: { color: "warning", icon: <BsFileEarmarkText /> },
  revision_requested: { color: "error", icon: <BsExclamationCircle /> },
  approved: { color: "success", icon: <BsCheckCircle /> },
  rejected: { color: "error", icon: <BsExclamationCircle /> },
};

export default function TasksPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { id, competitionId } = useParams<{
    id: string;
    competitionId: string;
  }>();
  const [selectedTask, setSelectedTask] = useState<TaskAssignment | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [notes, setNotes] = useState("");
  const [fileList, setFileList] = useState<any[]>([]);

  const { data: tasks, isLoading, refetch } = useQuery<TaskAssignment[]>({
    queryKey: ["participant-tasks", id],
    queryFn: async () => {
      const response = await axiosInstance.get(`/participants/tasks`, {
        params: { application_id: id },
      });
      return response.data.data;
    },
  });

  const handleSubmitTask = async () => {
    if (!selectedTask) return;
    setSubmitting(true);

    try {
      const formData = new FormData();
      formData.append("notes", notes);
      fileList.forEach((file) => {
        formData.append("files[]", file.originFileObj);
      });

      await axiosInstance.post(
        `/participants/tasks/${selectedTask.id}/submit`,
        formData,
        {
          params: { application_id: id },
          headers: { "Content-Type": "multipart/form-data" },
        }
      );

      message.success(t("task-submitted-successfully") || "Task submitted successfully");
      setSelectedTask(null);
      setNotes("");
      setFileList([]);
      refetch();
    } catch (error: any) {
      message.error(
        error?.response?.data?.message || "Failed to submit task"
      );
    } finally {
      setSubmitting(false);
    }
  };

  if (isLoading) {
    return <Spin className="w-full flex justify-center mt-10" />;
  }

  if (!tasks || tasks.length === 0) {
    return <Empty description={t("no-tasks") || "No tasks assigned yet"} />;
  }

  // Separate stage-linked tasks from ad-hoc tasks
  const adHocTasks = tasks.filter((task) => !task.stage);
  const stageTasks = tasks.filter((task) => task.stage);

  return (
    <div className="flex flex-col gap-6">
      {/* Ad-hoc Tasks (not linked to any stage) */}
      {adHocTasks.length > 0 && (
        <div>
          <h3 className="text-lg font-semibold mb-4 text-foreground">
            {t("general-tasks") || "General Tasks"}
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {adHocTasks.map((task) => (
              <TaskCard
                key={task.id}
                task={task}
                locale={locale}
                t={t}
                onSubmit={() => setSelectedTask(task)}
              />
            ))}
          </div>
        </div>
      )}

      {/* Stage-linked Tasks */}
      {stageTasks.length > 0 && (
        <div>
          <h3 className="text-lg font-semibold mb-4 text-foreground">
            {t("stage-tasks") || "Stage Tasks"}
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {stageTasks.map((task) => (
              <TaskCard
                key={task.id}
                task={task}
                locale={locale}
                t={t}
                onSubmit={() => setSelectedTask(task)}
              />
            ))}
          </div>
        </div>
      )}

      {/* Submit Task Modal */}
      <Modal
        open={!!selectedTask}
        onCancel={() => {
          setSelectedTask(null);
          setNotes("");
          setFileList([]);
        }}
        title={selectedTask ? localizedValue(selectedTask.title, locale) : undefined}
        footer={[
          <Button
            key="cancel"
            onClick={() => setSelectedTask(null)}
          >
            {t("cancel") || "Cancel"}
          </Button>,
          <Button
            key="submit"
            type="primary"
            loading={submitting}
            onClick={handleSubmitTask}
            disabled={fileList.length === 0 && !notes}
          >
            {t("submit") || "Submit"}
          </Button>,
        ]}
      >
        <div className="flex flex-col gap-4">
          {selectedTask?.instructions && (
            <div className="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
              <p className="text-sm font-medium mb-1">
                {t("instructions") || "Instructions"}
              </p>
              <p className="text-sm text-gray-600 dark:text-gray-300">
                {localizedValue(selectedTask.instructions, locale)}
              </p>
            </div>
          )}

          <div>
            <p className="text-sm font-medium mb-2">
              {t("notes") || "Notes"}
            </p>
            <Input.TextArea
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder={t("add-notes") || "Add notes..."}
            />
          </div>

          <div>
            <p className="text-sm font-medium mb-2">
              {t("files") || "Files"}
            </p>
            <Upload.Dragger
              fileList={fileList}
              onChange={({ fileList }) => setFileList(fileList)}
              beforeUpload={() => false}
              multiple
            >
              <p className="text-primary">
                <BsUpload size={24} className="mx-auto mb-2" />
              </p>
              <p className="text-sm">
                {t("click-or-drag") || "Click or drag files to upload"}
              </p>
              {selectedTask?.allowed_file_formats && (
                <p className="text-xs text-gray-400 mt-1">
                  {t("allowed-formats") || "Allowed"}: {selectedTask.allowed_file_formats.join(", ")}
                </p>
              )}
            </Upload.Dragger>
          </div>

          {selectedTask?.latest_submission?.admin_feedback && (
            <div className="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
              <p className="text-sm font-medium mb-1">
                {t("admin-feedback") || "Admin Feedback"}
              </p>
              <p className="text-sm">{selectedTask.latest_submission.admin_feedback}</p>
            </div>
          )}
        </div>
      </Modal>
    </div>
  );
}

function TaskCard({
  task,
  locale,
  t,
  onSubmit,
}: {
  task: TaskAssignment;
  locale: string;
  t: (key: string) => string;
  onSubmit: () => void;
}) {
  const config = statusConfig[task.status] || statusConfig.not_started;
  const isOverdue =
    task.due_date &&
    dayjs(task.due_date).isBefore(dayjs()) &&
    !["approved", "rejected"].includes(task.status);
  const canSubmit = ["not_started", "in_progress", "revision_requested"].includes(task.status);

  return (
    <Card className="!rounded-xl" hoverable>
      <div className="flex flex-col gap-3">
        <div className="flex justify-between items-start">
          <h4 className="font-semibold text-base text-foreground">{localizedValue(task.title, locale)}</h4>
          <Tag color={config.color} icon={config.icon}>
            {t(task.status) || task.status.replace(/_/g, " ")}
          </Tag>
        </div>

        {task.description && (
          <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
            {localizedValue(task.description, locale)}
          </p>
        )}

        {task.stage && (
          <div className="text-xs text-gray-400">
            {t("stage") || "Stage"}: {localizedValue(task.stage.title, locale)}
          </div>
        )}

        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2 text-sm text-gray-500">
            {task.due_date && (
              <span className={`flex items-center gap-1 ${isOverdue ? "text-red-500" : ""}`}>
                <BsCalendar3 size={12} />
                {dayjs(task.due_date).format("MMM D, YYYY")}
                {isOverdue && <BsExclamationCircle size={12} className="text-red-500" />}
              </span>
            )}
          </div>

          {canSubmit && (
            <Button type="primary" size="small" onClick={onSubmit}>
              {task.status === "revision_requested"
                ? (t("resubmit") || "Resubmit")
                : (t("submit") || "Submit")}
            </Button>
          )}
        </div>

        {task.latest_submission && (
          <div className="text-xs text-gray-400 border-t pt-2 mt-1">
            {t("last-submission") || "Last submission"}: v{task.latest_submission.version} -{" "}
            {dayjs(task.latest_submission.submitted_at).format("MMM D, YYYY")}
          </div>
        )}
      </div>
    </Card>
  );
}
