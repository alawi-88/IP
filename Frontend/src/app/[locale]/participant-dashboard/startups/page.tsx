"use client";

import { Button, Card, Empty, Modal, Form, Input, Upload, Spin, message, Grid } from "antd";
import { useTranslations, useLocale } from "next-intl";
import Image from "next/image";
import { useRouter } from "@/i18n/routing";
import { useStartupStore } from "@/store/startup";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { FiPlus, FiTrash2, FiEdit3 } from "react-icons/fi";
import * as startupApi from "@/config/startup-api";
import ProgressBar from "@/components/va/ProgressBar";

export default function StartupsPage() {
  const t = useTranslations();
  const locale = useLocale() as "en" | "ar";
  const router = useRouter();
  const { setStartups, setCurrentStartup } = useStartupStore();
  const queryClient = useQueryClient();
  const [form] = Form.useForm();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const screens = Grid.useBreakpoint();

  // Fetch startups
  const { data: startups = [], isLoading } = useQuery({
    queryKey: ["startups"],
    queryFn: startupApi.getStartups,
    onSuccess: (data) => {
      setStartups(data);
    },
  });

  // Create startup mutation
  const createMutation = useMutation({
    mutationFn: startupApi.createStartup,
    onSuccess: (newStartup) => {
      message.success(t("va.createdSuccessfully", "Startup created successfully"));
      form.resetFields();
      setLogoFile(null);
      setIsModalOpen(false);
      queryClient.invalidateQueries({ queryKey: ["startups"] });
      setCurrentStartup(newStartup);
      router.push(`/participant-dashboard/startups/${newStartup.id}`);
    },
    onError: (error) => {
      message.error(t("va.creationFailed", "Failed to create startup"));
      console.error(error);
    },
  });

  // Delete startup mutation
  const deleteMutation = useMutation({
    mutationFn: startupApi.deleteStartup,
    onSuccess: () => {
      message.success(t("va.deletedSuccessfully", "Startup deleted successfully"));
      queryClient.invalidateQueries({ queryKey: ["startups"] });
    },
    onError: () => {
      message.error(t("va.deletionFailed", "Failed to delete startup"));
    },
  });

  const handleCreate = async (values: any) => {
    await createMutation.mutateAsync({
      name: values.name,
      tagline: values.tagline,
      description: values.description,
      logo: logoFile || undefined,
    });
  };

  const handleDelete = (startupId: string) => {
    Modal.confirm({
      title: t("confirm-delete", "Confirm Delete"),
      content: t("va.deleteConfirmation", "Are you sure you want to delete this startup?"),
      okText: t("delete", "Delete"),
      cancelText: t("cancel", "Cancel"),
      okButtonProps: { danger: true },
      onOk() {
        deleteMutation.mutate(startupId);
      },
    });
  };

  if (isLoading) {
    return <Spin />;
  }

  const gridColumns = screens.md ? 3 : screens.sm ? 2 : 1;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-primary-900">
          {t("va.startups", "Startups")}
        </h1>
        <Button
          type="primary"
          icon={<FiPlus size={18} />}
          onClick={() => setIsModalOpen(true)}
        >
          {t("va.createStartup", "Create Startup")}
        </Button>
      </div>

      {startups.length === 0 ? (
        <Empty description={t("va.noStartups", "No startups yet")}>
          <Button
            type="primary"
            icon={<FiPlus size={18} />}
            onClick={() => setIsModalOpen(true)}
          >
            {t("va.createNewStartup", "Create New Startup")}
          </Button>
        </Empty>
      ) : (
        <div className={`grid gap-6 grid-cols-${gridColumns}`}>
          {startups.map((startup) => (
            <Card
              key={startup.id}
              className="cursor-pointer hover:shadow-lg transition-shadow"
              onClick={() => {
                setCurrentStartup(startup);
                router.push(`/participant-dashboard/startups/${startup.id}`);
              }}
              cover={
                startup.logo && (
                  <div className="relative w-full h-40 bg-gray-100">
                    <Image
                      src={startup.logo}
                      alt={startup.name}
                      fill
                      className="object-cover"
                    />
                  </div>
                )
              }
            >
              <div className="space-y-3">
                <div>
                  <h3 className="font-bold text-lg text-primary-900">
                    {startup.name}
                  </h3>
                  {startup.tagline && (
                    <p className="text-sm text-gray-600">{startup.tagline}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <ProgressBar
                    percentage={startup.completionPercentage}
                    showLabel={true}
                  />
                  <p className="text-xs text-gray-500">
                    {t("va.status", "Status")}: {t(`va.${startup.status}`)}
                  </p>
                </div>

                <div className="flex gap-2 pt-2 border-t">
                  <Button
                    type="text"
                    size="small"
                    icon={<FiEdit3 size={14} />}
                    onClick={(e) => {
                      e.stopPropagation();
                      router.push(
                        `/participant-dashboard/startups/${startup.id}`
                      );
                    }}
                  >
                    {t("edit", "Edit")}
                  </Button>
                  <Button
                    type="text"
                    danger
                    size="small"
                    icon={<FiTrash2 size={14} />}
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDelete(startup.id);
                    }}
                    loading={deleteMutation.isPending}
                  >
                    {t("delete", "Delete")}
                  </Button>
                </div>
              </div>
            </Card>
          ))}
        </div>
      )}

      {/* Create Startup Modal */}
      <Modal
        title={t("va.createStartup", "Create Startup")}
        open={isModalOpen}
        onCancel={() => {
          setIsModalOpen(false);
          form.resetFields();
          setLogoFile(null);
        }}
        footer={null}
        width={600}
      >
        <Form
          form={form}
          layout="vertical"
          onFinish={handleCreate}
          className="mt-6"
        >
          <Form.Item
            name="name"
            label={t("va.startupName", "Startup Name")}
            rules={[
              {
                required: true,
                message: t("required-field", "This field is required"),
              },
            ]}
          >
            <Input placeholder="Enter startup name" />
          </Form.Item>

          <Form.Item
            name="tagline"
            label={t("va.tagline", "Tagline")}
          >
            <Input placeholder="Enter a short tagline" />
          </Form.Item>

          <Form.Item
            name="description"
            label={t("va.description", "Description")}
          >
            <Input.TextArea
              rows={4}
              placeholder="Describe your startup"
            />
          </Form.Item>

          <Form.Item
            label={t("va.uploadLogo", "Upload Logo")}
            name="logo"
          >
            <Upload
              maxCount={1}
              beforeUpload={(file) => {
                setLogoFile(file);
                return false;
              }}
              onRemove={() => setLogoFile(null)}
              accept="image/*"
            >
              <Button>
                {t("upload", "Upload")} {t("image", "Image")}
              </Button>
            </Upload>
          </Form.Item>

          <Form.Item className="mb-0">
            <Button
              type="primary"
              htmlType="submit"
              block
              loading={createMutation.isPending}
            >
              {t("va.createStartup", "Create Startup")}
            </Button>
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
