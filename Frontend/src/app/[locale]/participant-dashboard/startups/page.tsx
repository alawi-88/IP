"use client";

import { Button, Card, Empty, Modal, Form, Input, Upload, Spin, message, Grid, Steps, Typography, Progress } from "antd";
import { useTranslations, useLocale } from "next-intl";
import Image from "next/image";
import { useRouter } from "@/i18n/routing";
import { useStartupStore } from "@/store/startup";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { FiPlus, FiTrash2, FiEdit3, FiZap, FiFileText } from "react-icons/fi";
import * as startupApi from "@/config/startup-api";
import ProgressBar from "@/components/va/ProgressBar";

const { Paragraph } = Typography;

export default function StartupsPage() {
  const t = useTranslations();
  const locale = useLocale() as "en" | "ar";
  const router = useRouter();
  const { setStartups, setCurrentStartup } = useStartupStore();
  const queryClient = useQueryClient();
  // No useForm() — destroyOnClose handles form reset automatically
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<"ai" | "simple">("ai");
  const [aiStep, setAiStep] = useState(0);
  const [generationProgress, setGenerationProgress] = useState(0);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const screens = Grid.useBreakpoint();

  // Fetch startups
  const { data: startups = [], isLoading } = useQuery({
    queryKey: ["startups"],
    queryFn: startupApi.getStartups,
  });

  // AI generate startup mutation
  const generateMutation = useMutation({
    mutationFn: (data: { prompt: string; name: string }) =>
      startupApi.generateStartup(data.prompt, data.name),
    onSuccess: (newStartup) => {
      message.success(t("va.startupGeneratedSuccess", "Startup created with AI-generated content!"));
      setAiStep(0);
      setGenerationProgress(0);
      setIsModalOpen(false);
      queryClient.invalidateQueries({ queryKey: ["startups"] });
      setCurrentStartup(newStartup);
      router.push(`/participant-dashboard/startups/${newStartup.id}`);
    },
    onError: (error) => {
      message.error(t("va.generationFailed", "Failed to generate startup content"));
      setAiStep(0);
      setGenerationProgress(0);
      console.error(error);
    },
  });

  // Simple create startup mutation
  const createMutation = useMutation({
    mutationFn: startupApi.createStartup,
    onSuccess: (newStartup) => {
      message.success(t("va.createdSuccessfully", "Startup created successfully"));
      setLogoFile(null);
      setIsModalOpen(false);
      setModalMode("ai");
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

  const handleAiGenerate = async (values: any) => {
    setAiStep(1);
    // Simulate progress animation
    let progress = 0;
    const interval = setInterval(() => {
      progress += Math.random() * 15;
      if (progress > 90) progress = 90;
      setGenerationProgress(Math.round(progress));
    }, 500);

    try {
      await generateMutation.mutateAsync({
        prompt: values.prompt,
        name: values.name,
      });
    } finally {
      clearInterval(interval);
      setGenerationProgress(100);
    }
  };

  const handleSimpleCreate = async (values: any) => {
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

  const closeModal = () => {
    setIsModalOpen(false);
    setModalMode("ai");
    setAiStep(0);
    setGenerationProgress(0);
    setLogoFile(null);
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
                    percentage={startup.completionPercentage ?? 0}
                    showLabel={true}
                  />
                  <p className="text-xs text-gray-500">
                    {t("va.status", "Status")}: {t(`va.${startup.status}`, startup.status || "Active")}
                  </p>
                </div>
                <div className="flex gap-2 pt-2 border-t">
                  <Button
                    type="text"
                    size="small"
                    icon={<FiEdit3 size={14} />}
                    onClick={(e) => {
                      e.stopPropagation();
                      router.push(`/participant-dashboard/startups/${startup.id}`);
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

      {/* Create Startup Modal - AI-Powered */}
      <Modal
        title={
          modalMode === "ai"
            ? t("va.createWithAi", "Create Startup with AI")
            : t("va.createStartup", "Create Startup")
        }
        open={isModalOpen}
        onCancel={closeModal}
        footer={null}
        width={650}
        destroyOnClose
      >
        {modalMode === "ai" ? (
          <div className="mt-4">
            {aiStep === 0 ? (
              <>
                <div className="mb-4 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-100">
                  <div className="flex items-center gap-2 mb-2">
                    <FiZap className="text-blue-600" size={20} />
                    <span className="font-semibold text-blue-900">
                      {t("va.aiPowered", "AI-Powered Creation")}
                    </span>
                  </div>
                  <p className="text-sm text-gray-600">
                    {t("va.aiDescription", "Describe your startup idea and AI will generate content for all venture analysis sections — business overview, market analysis, SWOT, financial model, and more.")}
                  </p>
                </div>

                <Form layout="vertical" onFinish={handleAiGenerate}>
                  <Form.Item
                    name="name"
                    label={t("va.startupName", "Startup Name")}
                    rules={[{ required: true, message: t("required-field", "This field is required") }]}
                  >
                    <Input placeholder={t("va.enterStartupName", "Enter startup name")} />
                  </Form.Item>

                  <Form.Item
                    name="prompt"
                    label={t("va.describeYourIdea", "Describe Your Startup Idea")}
                    rules={[
                      { required: true, message: t("required-field", "This field is required") },
                      { min: 20, message: t("va.promptMinLength", "Please provide at least 20 characters") },
                    ]}
                  >
                    <Input.TextArea
                      rows={5}
                      maxLength={2000}
                      showCount
                      placeholder={t("va.aiPromptPlaceholder", "e.g., A platform that connects freelance designers with small businesses needing affordable branding and design services. It uses AI to match designers with projects based on style preferences and budget...")}
                    />
                  </Form.Item>

                  <div className="flex gap-3">
                    <Button type="primary" htmlType="submit" block icon={<FiZap size={16} />} loading={generateMutation.isPending}>
                      {t("va.generateWithAi", "Generate with AI")}
                    </Button>
                  </div>
                </Form>

                <div className="mt-4 text-center">
                  <Button type="link" onClick={() => setModalMode("simple")} icon={<FiFileText size={14} />}>
                    {t("va.createWithoutAi", "Create without AI")}
                  </Button>
                </div>
              </>
            ) : (
              /* Step 2: Generating Progress */
              <div className="text-center py-8">
                <div className="mb-6">
                  <Progress
                    type="circle"
                    percent={generationProgress}
                    size={120}
                    strokeColor={{ "0%": "#1677ff", "100%": "#722ed1" }}
                  />
                </div>
                <h3 className="text-lg font-semibold mb-2">
                  {t("va.generatingStartup", "Generating Your Startup...")}
                </h3>
                <p className="text-gray-500 text-sm">
                  {t("va.generatingDescription", "AI is creating content for all venture analysis sections. This may take a moment.")}
                </p>
              </div>
            )}
          </div>
        ) : (
          /* Simple creation form */
          <div className="mt-4">
            <Form layout="vertical" onFinish={handleSimpleCreate}>
              <Form.Item
                name="name"
                label={t("va.startupName", "Startup Name")}
                rules={[{ required: true, message: t("required-field", "This field is required") }]}
              >
                <Input placeholder={t("va.enterStartupName", "Enter startup name")} />
              </Form.Item>
              <Form.Item name="tagline" label={t("va.tagline", "Tagline")}>
                <Input placeholder={t("va.enterTagline", "Enter a short tagline")} />
              </Form.Item>
              <Form.Item name="description" label={t("va.description", "Description")}>
                <Input.TextArea rows={4} placeholder={t("va.describeStartup", "Describe your startup")} />
              </Form.Item>
              <Form.Item label={t("va.uploadLogo", "Upload Logo")} name="logo">
                <Upload
                  maxCount={1}
                  beforeUpload={(file) => { setLogoFile(file); return false; }}
                  onRemove={() => setLogoFile(null)}
                  accept="image/*"
                >
                  <Button>{t("upload", "Upload")} {t("image", "Image")}</Button>
                </Upload>
              </Form.Item>
              <Form.Item className="mb-2">
                <Button type="primary" htmlType="submit" block loading={createMutation.isPending}>
                  {t("va.createStartup", "Create Startup")}
                </Button>
              </Form.Item>
            </Form>
            <div className="text-center">
              <Button type="link" onClick={() => setModalMode("ai")} icon={<FiZap size={14} />}>
                {t("va.backToAiCreation", "Back to AI-powered creation")}
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
