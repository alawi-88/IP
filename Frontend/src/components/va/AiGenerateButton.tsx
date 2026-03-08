"use client";

import { Button, Input, Modal, Space, Spin, message } from "antd";
import { useState } from "react";
import { useTranslations } from "next-intl";
import { MdOutlineAutoFixHigh } from "react-icons/md";
import AiSuggestionPanel from "./AiSuggestionPanel";

interface AiGenerateButtonProps {
  fieldLabel: string;
  onGenerate: (prompt: string) => Promise<string>;
  onAccept: (content: string) => void;
  onDismiss?: () => void;
}

export default function AiGenerateButton({
  fieldLabel,
  onGenerate,
  onAccept,
  onDismiss,
}: AiGenerateButtonProps) {
  const t = useTranslations();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [prompt, setPrompt] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [generatedContent, setGeneratedContent] = useState("");
  const [showSuggestion, setShowSuggestion] = useState(false);

  const handleGenerate = async () => {
    if (!prompt.trim()) {
      message.error(t("va.enterPrompt", "Please enter a prompt"));
      return;
    }

    try {
      setIsLoading(true);
      const result = await onGenerate(prompt);
      setGeneratedContent(result);
      setShowSuggestion(true);
    } catch (error) {
      message.error(t("va.generationFailed", "Generation failed"));
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleAccept = () => {
    onAccept(generatedContent);
    handleClose();
    message.success(t("va.acceptedSuccessfully", "Accepted successfully"));
  };

  const handleModify = (content: string) => {
    onAccept(content);
    handleClose();
    message.success(t("va.savedSuccessfully", "Saved successfully"));
  };

  const handleClose = () => {
    setIsModalOpen(false);
    setPrompt("");
    setGeneratedContent("");
    setShowSuggestion(false);
  };

  return (
    <>
      <Button
        type="text"
        size="small"
        icon={<MdOutlineAutoFixHigh size={16} />}
        onClick={() => setIsModalOpen(true)}
        className="!text-blue-600 hover:!bg-blue-50"
      >
        {t("va.aiGenerate", "AI Generate")}
      </Button>

      <Modal
        title={`${t("va.aiGenerate", "AI Generate")} - ${fieldLabel}`}
        open={isModalOpen}
        onCancel={handleClose}
        footer={null}
        width={600}
      >
        {showSuggestion ? (
          <AiSuggestionPanel
            isOpen={showSuggestion}
            content={generatedContent}
            isLoading={isLoading}
            onAccept={handleAccept}
            onModify={handleModify}
            onDismiss={() => {
              setShowSuggestion(false);
              onDismiss?.();
            }}
          />
        ) : (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                {t("va.prompt", "Prompt")}
              </label>
              <Input.TextArea
                rows={4}
                placeholder={t("va.enterPromptHere", "Enter your prompt here")}
                value={prompt}
                onChange={(e) => setPrompt(e.target.value)}
                disabled={isLoading}
              />
            </div>
            <Space className="flex justify-end">
              <Button onClick={handleClose}>{t("cancel", "Cancel")}</Button>
              <Button
                type="primary"
                loading={isLoading}
                onClick={handleGenerate}
              >
                {t("generate", "Generate")}
              </Button>
            </Space>
          </div>
        )}
      </Modal>
    </>
  );
}
