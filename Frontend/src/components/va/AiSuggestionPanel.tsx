"use client";

import { Button, Space, Spin } from "antd";
import { useState } from "react";
import { useTranslations } from "next-intl";

interface AiSuggestionPanelProps {
  isOpen: boolean;
  content: string;
  isLoading?: boolean;
  onAccept: () => void;
  onModify: (content: string) => void;
  onDismiss: () => void;
}

export default function AiSuggestionPanel({
  isOpen,
  content,
  isLoading = false,
  onAccept,
  onModify,
  onDismiss,
}: AiSuggestionPanelProps) {
  const t = useTranslations();
  const [isModifying, setIsModifying] = useState(false);
  const [modifiedContent, setModifiedContent] = useState(content);

  if (!isOpen) return null;

  const handleSaveModify = () => {
    onModify(modifiedContent);
    setIsModifying(false);
  };

  return (
    <div className="mt-4 p-4 bg-[#F0F7FF] border border-blue-300 rounded-lg">
      <h4 className="font-semibold text-blue-900 mb-3">
        {t("va.aiSuggestion", "AI Suggestion")}
      </h4>

      {isLoading ? (
        <div className="flex justify-center py-4">
          <Spin />
        </div>
      ) : isModifying ? (
        <div className="space-y-3">
          <textarea
            value={modifiedContent}
            onChange={(e) => setModifiedContent(e.target.value)}
            className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
            rows={4}
          />
          <Space>
            <Button
              type="primary"
              size="small"
              onClick={handleSaveModify}
              loading={isLoading}
            >
              {t("save", "Save")}
            </Button>
            <Button size="small" onClick={() => setIsModifying(false)}>
              {t("cancel", "Cancel")}
            </Button>
          </Space>
        </div>
      ) : (
        <div className="space-y-3">
          <p className="text-gray-700 whitespace-pre-wrap">{content}</p>
          <Space>
            <Button
              type="primary"
              size="small"
              onClick={onAccept}
              loading={isLoading}
            >
              {t("va.accept", "Accept")}
            </Button>
            <Button size="small" onClick={() => setIsModifying(true)}>
              {t("va.modify", "Modify")}
            </Button>
            <Button danger size="small" onClick={onDismiss}>
              {t("va.dismiss", "Dismiss")}
            </Button>
          </Space>
        </div>
      )}
    </div>
  );
}
